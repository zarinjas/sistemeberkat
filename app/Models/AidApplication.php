<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AidApplication extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_KUIRI = 'kuiri';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DISBURSED = 'disbursed';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'reference_no',
        'status',
        'triage_answers',
        'dynamic_payload',
        'category_tags',
        'requested_amount',
        'paid_amount',
        'transaction_ref',
        'payment_receipt_path',
        'submission_pdf_path',
        'payment_prepared_by_user_id',
        'payment_approved_by_user_id',
        'priority_score',
        'priority_label',
        'priority_reason',
        'submitted_at',
        'reviewed_at',
        'decided_at',
        'paid_at',
        'payment_prepared_at',
        'payment_approved_at',
        'submission_pdf_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'triage_answers' => 'array',
            'dynamic_payload' => 'array',
            'category_tags' => 'array',
            'requested_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'decided_at' => 'datetime',
            'paid_at' => 'datetime',
            'payment_prepared_at' => 'datetime',
            'payment_approved_at' => 'datetime',
            'submission_pdf_generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentPreparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_prepared_by_user_id');
    }

    public function paymentApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_approved_by_user_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->latest('changed_at');
    }

    public function buildFormPreview(): array
    {
        $dynamicPayload = $this->dynamic_payload ?: [];
        $triageAnswers = $this->triage_answers ?: [];
        $formId = (int) data_get($dynamicPayload, '_form_id');
        $schema = $formId ? FormSchema::query()->find($formId) : null;

        $sections = [
            'maklumat' => [
                'key' => 'maklumat',
                'title' => 'Maklumat Permohonan',
                'rows' => [],
            ],
            'dokumen' => [
                'key' => 'dokumen',
                'title' => 'Dokumen Sokongan',
                'rows' => [],
            ],
            'pengesahan' => [
                'key' => 'pengesahan',
                'title' => 'Pengesahan & Syarat',
                'rows' => [],
            ],
        ];

        $consumedKeys = [];

        if ($schema) {
            $fields = collect(data_get($schema->schema_json, 'fields', []))
                ->sortBy(fn ($field) => (int) ($field['order'] ?? 0))
                ->values();

            foreach ($fields as $index => $field) {
                if (!is_array($field) || ($field['type'] ?? null) === 'instruction') {
                    continue;
                }

                $fieldName = $this->normalizePreviewFieldName($field, $index);
                $fieldLabel = $field['label'] ?? $this->formatPreviewLabel($fieldName);
                $fieldType = (string) ($field['type'] ?? 'text');

                $rawValue = $fieldType === 'checkbox'
                    ? ($triageAnswers[$fieldName] ?? null)
                    : ($dynamicPayload[$fieldName] ?? null);

                $sectionKey = $fieldType === 'file'
                    ? 'dokumen'
                    : ($fieldType === 'checkbox' ? 'pengesahan' : 'maklumat');

                $sections[$sectionKey]['rows'][] = [
                    'label' => (string) $fieldLabel,
                    'value' => $this->formatPreviewValue($rawValue),
                ];

                $consumedKeys[$fieldName] = true;
            }
        }

        foreach ($dynamicPayload as $key => $value) {
            if (str_starts_with((string) $key, '_') || isset($consumedKeys[$key])) {
                continue;
            }

            $sections['maklumat']['rows'][] = [
                'label' => $this->formatPreviewLabel((string) $key),
                'value' => $this->formatPreviewValue($value),
            ];
        }

        foreach ($triageAnswers as $key => $value) {
            if (isset($consumedKeys[$key])) {
                continue;
            }

            $sections['pengesahan']['rows'][] = [
                'label' => $this->formatPreviewLabel((string) $key),
                'value' => $this->formatPreviewValue($value),
            ];
        }

        return [
            'title' => $schema?->category_name ?: 'Borang Permohonan',
            'sections' => collect($sections)
                ->filter(fn ($section) => count($section['rows']) > 0)
                ->values()
                ->all(),
        ];
    }

    private function normalizePreviewFieldName(array $field, int $index): string
    {
        if (!empty($field['name'])) {
            return (string) $field['name'];
        }

        $baseLabel = (string) ($field['label'] ?? $field['type'] ?? 'field');
        $slug = (string) Str::of($baseLabel)
            ->lower()
            ->replaceMatches('/[^a-z0-9\s-]/', '')
            ->trim()
            ->replaceMatches('/\s+/', '_')
            ->replaceMatches('/_+/', '_');

        return ($field['type'] ?? 'field').'_'.($index + 1).'_'.$slug;
    }

    private function formatPreviewLabel(string $key): string
    {
        return Str::of($key)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    private function formatPreviewValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        if (is_array($value)) {
            if (isset($value['original_name'])) {
                return 'Fail: '.((string) $value['original_name']);
            }

            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '-';
        }

        return (string) $value;
    }
}
