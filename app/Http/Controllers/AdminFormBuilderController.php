<?php

namespace App\Http\Controllers;

use App\Models\FormSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminFormBuilderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeSuperAdmin($request);

        $allCategories = FormSchema::query()
            ->select(['category_key', 'category_name'])
            ->orderBy('category_name')
            ->get()
            ->unique('category_key')
            ->values()
            ->map(fn (FormSchema $schema) => [
                'key' => $schema->category_key,
                'label' => $schema->category_name,
                'persisted' => true,
            ]);

        $latestSchemasByCategory = FormSchema::query()
            ->where('is_active', true)
            ->where('lifecycle_status', FormSchema::STATUS_PUBLISHED)
            ->orderByDesc('published_at')
            ->get()
            ->unique('category_key')
            ->values()
            ->map(fn (FormSchema $schema) => [
                'id' => $schema->id,
                'category_key' => $schema->category_key,
                'category_name' => $schema->category_name,
                'version' => $schema->version,
                'schema_json' => $schema->schema_json,
                'lifecycle_status' => $schema->lifecycle_status,
                'published_at' => optional($schema->published_at)?->toISOString(),
            ]);

        $editingSchema = null;
        if ($request->filled('form_schema_id')) {
            $schema = FormSchema::query()->find($request->integer('form_schema_id'));

            if ($schema) {
                $editingSchema = [
                    'id' => $schema->id,
                    'category_key' => $schema->category_key,
                    'category_name' => $schema->category_name,
                    'version' => $schema->version,
                    'schema_json' => $schema->schema_json,
                    'lifecycle_status' => $schema->lifecycle_status,
                    'is_active' => (bool) $schema->is_active,
                ];
            }
        }

        return Inertia::render('AdminFormBuilder', [
            'latestSchemasByCategory' => $latestSchemasByCategory,
            'editingSchema' => $editingSchema,
            'allCategories' => $allCategories,
        ]);
    }

    public function saveDraft(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $this->validateFormPayload($request);
        $formSchemaId = $request->integer('form_schema_id');

        $schemaData = [
            'category_key' => $validated['category_key'],
            'category_name' => $validated['category_name'],
            'card_color' => $validated['card_color'] ?? null,
            'version' => $validated['version'] ?? $this->nextVersion($validated['category_key']),
            'schema_json' => [
                'category_key' => $validated['category_key'],
                'category_name' => $validated['category_name'],
                'description' => $validated['description'] ?? null,
                'version' => $validated['version'] ?? $this->nextVersion($validated['category_key']),
                'fields' => $validated['fields'],
            ],
            'published_at' => null,
            'published_by_user_id' => $request->user()->id,
            'is_active' => false,
            'lifecycle_status' => FormSchema::STATUS_DRAFT,
        ];

        if ($formSchemaId) {
            $formSchema = FormSchema::query()->findOrFail($formSchemaId);

            if ($formSchema->lifecycle_status === FormSchema::STATUS_PUBLISHED) {
                return back()->with('error', 'Borang published tidak boleh diedit terus. Duplikasi dahulu ke draft.');
            }

            $formSchema->update($schemaData);

            return back()->with('success', 'Draft borang berjaya dikemaskini.');
        }

        FormSchema::create($schemaData);

        return back()->with('success', 'Draft borang berjaya disimpan.');
    }

    public function publish(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $this->validateFormPayload($request);
        $formSchemaId = $request->integer('form_schema_id');

        $version = $validated['version'] ?? $this->nextVersion($validated['category_key']);

        $schemaData = [
            'category_key' => $validated['category_key'],
            'category_name' => $validated['category_name'],
            'version' => $version,
            'schema_json' => [
                'category_key' => $validated['category_key'],
                'category_name' => $validated['category_name'],
                'description' => $validated['description'] ?? null,
                'version' => $version,
                'fields' => $validated['fields'],
            ],
            'published_at' => $validated['published_at'] ?? now(),
            'published_by_user_id' => $request->user()->id,
            'is_active' => true,
            'lifecycle_status' => FormSchema::STATUS_PUBLISHED,
        ];

        DB::transaction(function () use ($formSchemaId, $schemaData) {
            FormSchema::query()
                ->where('category_key', $schemaData['category_key'])
                ->where('lifecycle_status', FormSchema::STATUS_PUBLISHED)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            if ($formSchemaId) {
                $formSchema = FormSchema::query()->findOrFail($formSchemaId);
                $formSchema->update($schemaData);

                return;
            }

            FormSchema::create($schemaData);
        });

        return redirect()->route('admin.form-builder')->with('success', 'Borang berjaya diterbitkan.');
    }

    public function manage(Request $request): Response
    {
        $this->authorizeSuperAdmin($request);

        $forms = FormSchema::query()
            ->with('publisher:id,name,email')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (FormSchema $schema) => [
                'id' => $schema->id,
                'category_key' => $schema->category_key,
                'category_name' => $schema->category_name,
                'version' => $schema->version,
                'is_active' => (bool) $schema->is_active,
                'lifecycle_status' => $schema->lifecycle_status,
                'published_at' => optional($schema->published_at)?->toISOString(),
                'published_by' => $schema->publisher?->name,
                'published_by_email' => $schema->publisher?->email,
                'fields_count' => count(data_get($schema->schema_json, 'fields', [])),
                'schema_json' => $schema->schema_json,
            ])
            ->values();

        return Inertia::render('AdminFormsManage', [
            'forms' => $forms,
        ]);
    }

    public function activate(Request $request, FormSchema $formSchema): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        if ($formSchema->lifecycle_status !== FormSchema::STATUS_PUBLISHED) {
            return back()->with('error', 'Hanya borang published boleh diaktifkan.');
        }

        DB::transaction(function () use ($formSchema) {
            FormSchema::query()
                ->where('category_key', $formSchema->category_key)
                ->where('lifecycle_status', FormSchema::STATUS_PUBLISHED)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $formSchema->update([
                'is_active' => true,
            ]);
        });

        return redirect()->route('forms.manage')->with('success', 'Versi borang berjaya dijadikan aktif.');
    }

    public function archive(Request $request, FormSchema $formSchema): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        if ($formSchema->lifecycle_status !== FormSchema::STATUS_PUBLISHED) {
            return back()->with('error', 'Hanya borang published boleh diarkibkan.');
        }

        $formSchema->update([
            'is_active' => false,
            'lifecycle_status' => FormSchema::STATUS_ARCHIVED,
        ]);

        return back()->with('success', 'Borang berjaya diarkibkan.');
    }

    public function duplicate(Request $request, FormSchema $formSchema): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        FormSchema::create([
            'category_key' => $formSchema->category_key,
            'category_name' => $formSchema->category_name,
            'version' => $this->nextVersion($formSchema->category_key),
            'schema_json' => $formSchema->schema_json,
            'published_at' => null,
            'published_by_user_id' => $request->user()->id,
            'is_active' => false,
            'lifecycle_status' => FormSchema::STATUS_DRAFT,
        ]);

        return back()->with('success', 'Borang berjaya diduplikasi ke draft baharu.');
    }

    public function destroy(Request $request, FormSchema $formSchema): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        if ($formSchema->lifecycle_status === FormSchema::STATUS_PUBLISHED || $formSchema->is_active) {
            return back()->with('error', 'Borang published/aktif tidak boleh dipadam. Arkibkan dahulu.');
        }

        $formSchema->delete();

        return back()->with('success', 'Borang berjaya dipadam.');
    }

    public function updateCategory(Request $request, string $categoryKey): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'category_name' => ['required', 'string', 'max:120'],
        ]);

        $normalizedCategoryKey = $this->normalizeCategoryKey($categoryKey);
        $newCategoryName = trim($validated['category_name']);
        $newCategoryKey = $this->normalizeCategoryKey($newCategoryName);

        if ($newCategoryKey === '') {
            return back()->with('error', 'Nama kategori tidak sah untuk dikemaskini.');
        }

        $existsSourceCategory = FormSchema::query()
            ->where('category_key', $normalizedCategoryKey)
            ->exists();

        if (! $existsSourceCategory) {
            return back()->with('error', 'Kategori asal tidak dijumpai.');
        }

        if ($newCategoryKey !== $normalizedCategoryKey) {
            $targetExists = FormSchema::query()
                ->where('category_key', $newCategoryKey)
                ->exists();

            if ($targetExists) {
                return back()->with('error', 'Nama kategori baharu bertindih dengan kategori sedia ada.');
            }
        }

        DB::transaction(function () use ($normalizedCategoryKey, $newCategoryKey, $newCategoryName) {
            $schemas = FormSchema::query()
                ->where('category_key', $normalizedCategoryKey)
                ->get();

            foreach ($schemas as $schema) {
                $schemaJson = $schema->schema_json ?: [];
                $schemaJson['category_key'] = $newCategoryKey;
                $schemaJson['category_name'] = $newCategoryName;

                $schema->update([
                    'category_key' => $newCategoryKey,
                    'category_name' => $newCategoryName,
                    'schema_json' => $schemaJson,
                ]);
            }
        });

        return back()->with('success', 'Kategori berjaya dikemaskini.');
    }

    public function destroyCategory(Request $request, string $categoryKey): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);

        $normalizedCategoryKey = $this->normalizeCategoryKey($categoryKey);

        $schemas = FormSchema::query()
            ->where('category_key', $normalizedCategoryKey)
            ->get();

        if ($schemas->isEmpty()) {
            return back()->with('error', 'Kategori tidak dijumpai.');
        }

        $hasPublished = $schemas->contains(fn (FormSchema $schema) => $schema->lifecycle_status === FormSchema::STATUS_PUBLISHED);
        if ($hasPublished) {
            return back()->with('error', 'Kategori ini masih ada borang published. Arkibkan semua dahulu sebelum buang kategori.');
        }

        FormSchema::query()
            ->where('category_key', $normalizedCategoryKey)
            ->delete();

        return back()->with('success', 'Kategori berjaya dibuang.');
    }

    private function validateFormPayload(Request $request): array
    {
        $request->merge([
            'category_key' => $this->normalizeCategoryKey((string) $request->input('category_key')),
        ]);

        return $request->validate([
            'category_key' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/'],
            'category_name' => ['required', 'string', 'max:120'],
            'card_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'version' => ['nullable', 'string', 'max:20'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.order' => ['required', 'integer', 'min:1'],
            'fields.*.type' => ['required', Rule::in(['text', 'textarea', 'instruction', 'select', 'radio', 'file', 'checkbox', 'profile_field'])],
            'fields.*.label' => ['nullable', 'string', 'max:255'],
            'fields.*.content' => ['nullable', 'string'],
            'fields.*.required' => ['nullable', 'boolean'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.options.*' => ['string', 'max:120'],
            'fields.*.profile_key' => ['nullable', 'string', 'max:50'],
            'fields.*.readonly' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);
    }

    private function normalizeCategoryKey(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/\s+/', '_', $normalized) ?? '';
        $normalized = preg_replace('/[^a-z0-9_-]/', '', $normalized) ?? '';

        return $normalized;
    }

    private function nextVersion(string $categoryKey): string
    {
        $latest = FormSchema::query()
            ->where('category_key', $categoryKey)
            ->pluck('version')
            ->map(function ($version) {
                $value = strtolower((string) $version);
                $numeric = preg_replace('/[^0-9]/', '', $value);

                return (int) ($numeric ?: 0);
            })
            ->max();

        return 'v'.(((int) $latest) + 1);
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Akses hanya untuk superadmin.');
    }
}
