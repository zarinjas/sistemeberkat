<?php

namespace App\Http\Controllers;

use App\Models\AidApplication;
use App\Models\FormSchema;
use App\Models\ApplicationStatusHistory;
use App\Services\ApplicationPdfService;
use App\Services\ApplicationSubmissionNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ApplicationPreScoringService;
use Inertia\Inertia;
use Inertia\Response;

class AidApplicationController extends Controller
{
    private function normalizeFieldName(array $field, int $index): string
    {
        if (!empty($field['name'])) {
            return (string) $field['name'];
        }

        $baseLabel = $field['label'] ?? $field['type'] ?? 'field';
        $slug = (string) Str::of((string) $baseLabel)
            ->lower()
            ->replaceMatches('/[^a-z0-9\s-]/', '')
            ->trim()
            ->replaceMatches('/\s+/', '_')
            ->replaceMatches('/_+/', '_');

        return ($field['type'] ?? 'field').'_'.($index + 1).'_'.$slug;
    }

    public function index(Request $request): Response
    {
        $allApplications = $request->user()
            ->aidApplications()
            ->latest()
            ->get();

        $drafts = $allApplications
            ->filter(fn (AidApplication $application) => $application->status === AidApplication::STATUS_DRAFT)
            ->values();

        $applications = $allApplications
            ->reject(fn (AidApplication $application) => $application->status === AidApplication::STATUS_DRAFT)
            ->values();


        $availableForms = FormSchema::query()
            ->where('lifecycle_status', FormSchema::STATUS_PUBLISHED)
            ->where('is_active', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (FormSchema $schema) use ($drafts) {
                $linkedDraft = $drafts->first(function (AidApplication $draft) use ($schema) {
                    return (int) data_get($draft->dynamic_payload, '_form_id') === (int) $schema->id;
                });

                return [
                    'id' => $schema->id,
                    'title' => $schema->category_name,
                    'category_key' => $schema->category_key,
                    'card_color' => $schema->card_color,
                    'description' => data_get($schema->schema_json, 'description', 'Borang permohonan bantuan BERKAT.'),
                    'version' => $schema->version,
                    'has_draft' => (bool) $linkedDraft,
                    'draft_id' => $linkedDraft?->id,
                ];
            })
            ->values();

        return Inertia::render('Applications/Index', [
            'applications' => $applications,
            'drafts' => $drafts,
            'availableForms' => $availableForms,
        ]);
    }

    public function create(Request $request): Response
    {
        $draftId = $request->integer('draft_id');
        $requestedFormId = $request->integer('form_id');

        $draftApplication = $request->user()
            ->aidApplications()
            ->where('status', AidApplication::STATUS_DRAFT)
            ->when($draftId, fn ($query) => $query->whereKey($draftId))
            ->latest()
            ->first();

        $draftFormId = (int) data_get($draftApplication, 'dynamic_payload._form_id');
        $resolvedFormId = $requestedFormId ?: ($draftFormId ?: null);

            $selectedForm = FormSchema::query()
                ->where('lifecycle_status', FormSchema::STATUS_PUBLISHED)
            ->when(
                $resolvedFormId,
                fn ($query) => $query->whereKey($resolvedFormId),
                fn ($query) => $query->where('is_active', true)->orderByDesc('published_at')->orderByDesc('id'),
            )
            ->first();

        $formSchema = $selectedForm
            ? [
                'id' => $selectedForm->id,
                'category_key' => $selectedForm->category_key,
                'category_name' => $selectedForm->category_name,
                'version' => $selectedForm->version,
                'fields' => data_get($selectedForm->schema_json, 'fields', []),
            ]
            : null;

        $formTitle = $selectedForm
            ? ($draftApplication ? 'Sambung Draf: '.$selectedForm->category_name : 'Mohon: '.$selectedForm->category_name)
            : 'Mohon: Borang Bantuan';

        $draftPayload = $draftApplication
            ? [
                'id' => $draftApplication->id,
                'requested_amount' => $draftApplication->requested_amount,
                'triage_answers' => $draftApplication->triage_answers,
                'dynamic_payload' => $draftApplication->dynamic_payload,
                'category_tags' => $draftApplication->category_tags,
                'form_id' => $draftFormId ?: $selectedForm?->id,
            ]
            : null;

        return Inertia::render('Applications/Create', [
            'formSchema' => $formSchema,
            'formTitle' => $formTitle,
            'draftApplication' => $draftPayload,
        ]);
    }

    public function store(Request $request, ApplicationPreScoringService $preScoringService, ApplicationSubmissionNotificationService $applicationSubmissionNotificationService): RedirectResponse
    {
        $isDraft = $request->boolean('is_draft');

        $validated = $request->validate([
            'draft_application_id' => ['nullable', 'integer', 'exists:aid_applications,id'],
            'form_id' => ['required', 'integer', 'exists:form_schemas,id'],
            'requested_amount' => ['nullable', 'numeric', 'min:0'],
            'triage_answers' => ['nullable', 'array'],
            'dynamic_payload' => [$isDraft ? 'nullable' : 'required', 'array'],
            'category_tags' => ['nullable', 'array'],
            'category_tags.*' => ['string', 'max:60'],
        ]);

            $selectedForm = FormSchema::query()
                ->where('lifecycle_status', FormSchema::STATUS_PUBLISHED)
                ->where('is_active', true)
                ->findOrFail($validated['form_id']);
        $status = $isDraft ? AidApplication::STATUS_DRAFT : AidApplication::STATUS_SUBMITTED;

        $categoryTags = collect($validated['category_tags'] ?? [])
            ->filter()
            ->whenEmpty(fn ($tags) => $tags->push($selectedForm->category_key))
            ->values()
            ->all();

        $dynamicPayload = $validated['dynamic_payload'] ?? [];
        $dynamicPayload['_form_id'] = $selectedForm->id;

        $fields = collect(data_get($selectedForm->schema_json, 'fields', []))
            ->sortBy(fn ($field) => (int) ($field['order'] ?? 0))
            ->values();

        $fileFields = $fields
            ->map(function ($field, $index) {
                if (($field['type'] ?? null) !== 'file') {
                    return null;
                }

                return [
                    'name' => $this->normalizeFieldName($field, $index),
                    'label' => $field['label'] ?? 'Dokumen Sokongan',
                    'required' => (bool) ($field['required'] ?? false),
                ];
            })
            ->filter()
            ->values();

        $uploadedDynamicFiles = data_get($request->allFiles(), 'dynamic_payload', []);

        if (is_array($uploadedDynamicFiles)) {
            foreach ($uploadedDynamicFiles as $fieldName => $uploadedFile) {
                if (!$uploadedFile instanceof UploadedFile || !$uploadedFile->isValid()) {
                    continue;
                }

                $path = $uploadedFile->store('application-documents');

                $dynamicPayload[$fieldName] = [
                    'file_path' => $path,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'mime_type' => $uploadedFile->getMimeType(),
                    'file_size' => $uploadedFile->getSize(),
                ];
            }
        }

        foreach ($fileFields as $fileField) {
            $inputPath = 'dynamic_payload.'.$fileField['name'];

            if (
                !$isDraft
                && $fileField['required']
                && empty(data_get($dynamicPayload, $fileField['name'].'.file_path'))
            ) {
                return back()->withErrors([
                    $inputPath => 'Sila muat naik dokumen wajib: '.$fileField['label'],
                ])->withInput();
            }
        }

        $scored = $isDraft
            ? [
                'priority_score' => 0,
                'priority_label' => null,
                'priority_reason' => null,
            ]
            : $preScoringService->score(
                $validated['triage_answers'] ?? [],
                $dynamicPayload,
                $categoryTags,
            );

        $applicationId = DB::transaction(function () use ($request, $validated, $status, $scored, $isDraft, $dynamicPayload, $categoryTags) {
            $existingDraft = null;

            if (!empty($validated['draft_application_id'])) {
                $existingDraft = $request->user()
                    ->aidApplications()
                    ->whereKey($validated['draft_application_id'])
                    ->where('status', AidApplication::STATUS_DRAFT)
                    ->first();
            }

            $applicationAttributes = [
                'status' => $status,
                'triage_answers' => $validated['triage_answers'] ?? null,
                'dynamic_payload' => $dynamicPayload,
                'category_tags' => $categoryTags,
                'requested_amount' => $validated['requested_amount'] ?? null,
                'priority_score' => $scored['priority_score'],
                'priority_label' => $scored['priority_label'],
                'priority_reason' => $scored['priority_reason'],
                'submitted_at' => $isDraft ? null : now(),
            ];

            if ($existingDraft) {
                $application = tap($existingDraft)->update($applicationAttributes);
                $application = $existingDraft->fresh();
            } else {
                $application = $request->user()->aidApplications()->create([
                    ...$applicationAttributes,
                    'reference_no' => 'BERKAT-'.now()->format('Ymd').'-'.Str::upper(Str::random(4)),
                ]);
            }

            ApplicationStatusHistory::create([
                'aid_application_id' => $application->id,
                'from_status' => $existingDraft?->status,
                'to_status' => $application->status,
                'changed_by_user_id' => $request->user()->id,
                'notes' => $isDraft ? 'Application saved as draft.' : 'Application submitted.',
                'changed_at' => now(),
            ]);

            return $application->id;
        });

        if (! $isDraft && $applicationId) {
            try {
                $application = AidApplication::query()
                    ->with('user')
                    ->find($applicationId);

                if ($application) {
                    $applicationSubmissionNotificationService->sendSubmissionEmails($application);
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return redirect()
            ->route('applications.index')
            ->with('success', $isDraft ? 'Draf berjaya disimpan.' : 'Permohonan berjaya dihantar.');
    }

    public function show(Request $request, AidApplication $application): Response
    {
        abort_if($application->user_id !== $request->user()->id, 403);

        $application->load(['statusHistories.changedBy']);

        return Inertia::render('Applications/Show', [
            'application' => $application,
            'submittedForm' => $application->buildFormPreview(),
        ]);
    }

    public function downloadPdf(Request $request, AidApplication $application, ApplicationPdfService $applicationPdfService)
    {
        $user = $request->user();
        $isOwner = (int) $application->user_id === (int) $user->id;
        $isReviewer = $user?->isAdmin() || $user?->isSuperAdmin();

        abort_unless($isOwner || $isReviewer, 403);

        $pdfPath = $application->submission_pdf_path;

        if (! $pdfPath || ! Storage::disk('local')->exists($pdfPath)) {
            $pdfPath = $applicationPdfService->generateAndStore($application);

            $application->update([
                'submission_pdf_path' => $pdfPath,
                'submission_pdf_generated_at' => now(),
            ]);
        }

        $filename = 'borang-'.$application->reference_no.'.pdf';

        return Storage::disk('local')->download($pdfPath, $filename);
    }

    public function destroyDraft(Request $request, AidApplication $application): RedirectResponse
    {
        abort_if($application->user_id !== $request->user()->id, 403);
        abort_if($application->status !== AidApplication::STATUS_DRAFT, 422, 'Only draft applications can be deleted.');

        DB::transaction(function () use ($application, $request) {
            ApplicationStatusHistory::create([
                'aid_application_id' => $application->id,
                'from_status' => $application->status,
                'to_status' => 'deleted',
                'changed_by_user_id' => $request->user()->id,
                'notes' => 'Draft deleted by applicant.',
                'changed_at' => now(),
            ]);

            $application->delete();
        });

        return redirect()->route('applications.index')->with('success', 'Draf berjaya dipadam.');
    }
}
