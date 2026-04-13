<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    publishedSchemas: {
        type: Array,
        default: () => [],
    },
    schema: {
        type: Object,
        default: null,
    },
    initialData: {
        type: Object,
        default: null,
    },
    showActions: {
        type: Boolean,
        default: true,
    },
    showHeader: {
        type: Boolean,
        default: true,
    },
});

const fallbackSchemas = {
    pendidikan: {
        key: 'pendidikan',
        label: 'Pendidikan',
        sections: [
            {
                key: 'maklumat',
                title: 'Maklumat Permohonan',
                fields: [
                    {
                        id: 'pendidikan_instruction_1',
                        type: 'instruction',
                        content:
                            'Semak checklist dokumen sebelum meneruskan:\n- Kad Pengenalan pemohon\n- Surat Tawaran institusi\n- Bukti akaun bank aktif\n\nPermohonan yang tidak lengkap boleh ditangguhkan.',
                    },
                    {
                        name: 'jenis_bantuan',
                        label: 'Jenis Bantuan',
                        type: 'select',
                        required: true,
                        options: ['Kemasukan Persekolahan', 'IPT', 'Kecemerlangan'],
                    },
                    {
                        name: 'nama_institusi',
                        label: 'Nama Institusi',
                        type: 'text',
                        required: true,
                        placeholder: 'Contoh: Universiti Malaysia Sabah',
                    },
                    {
                        id: 'radio_tajaan_pendidikan',
                        name: 'menerima_tajaan_lain',
                        label: 'Adakah anda menerima tajaan lain?',
                        type: 'radio',
                        options: 'Ya, Tidak',
                        required: true,
                    },
                ],
            },
            {
                key: 'dokumen',
                title: 'Dokumen Sokongan',
                fields: [
                    {
                        name: 'surat_tawaran',
                        label: 'Surat Tawaran',
                        type: 'file',
                        required: true,
                        accept: '.pdf,.jpg,.jpeg,.png',
                    },
                ],
            },
            {
                key: 'pengesahan',
                title: 'Pengesahan & Syarat',
                fields: [
                    {
                        name: 'setuju_terma_pendidikan',
                        label: 'Saya mengesahkan maklumat pendidikan adalah benar dan bersetuju dengan syarat bantuan pendidikan BERKAT.',
                        type: 'checkbox',
                        required: true,
                    },
                ],
            },
        ],
    },
    kesihatan: {
        key: 'kesihatan',
        label: 'Kesihatan',
        sections: [
            {
                key: 'maklumat',
                title: 'Maklumat Permohonan',
                fields: [
                    {
                        name: 'jenis_bantuan',
                        label: 'Jenis Bantuan',
                        type: 'select',
                        required: true,
                        options: ['Kemalangan', 'Masuk Wad', 'Peralatan Sokongan'],
                    },
                    {
                        name: 'butiran_kesihatan',
                        label: 'Butiran Penyakit/Kecederaan',
                        type: 'textarea',
                        required: true,
                        placeholder: 'Terangkan ringkas situasi kesihatan semasa...',
                    },
                ],
            },
            {
                key: 'dokumen',
                title: 'Dokumen Sokongan',
                fields: [
                    {
                        name: 'laporan_perubatan',
                        label: 'Laporan Perubatan',
                        type: 'file',
                        required: true,
                        accept: '.pdf,.jpg,.jpeg,.png',
                    },
                ],
            },
            {
                key: 'pengesahan',
                title: 'Pengesahan & Syarat',
                fields: [
                    {
                        name: 'setuju_kebenaran_data_kesihatan',
                        label: 'Saya memberi kebenaran pemprosesan data kesihatan bagi tujuan semakan bantuan BERKAT.',
                        type: 'checkbox',
                        required: true,
                    },
                ],
            },
        ],
    },
};

const slugify = (value) =>
    String(value || 'field')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '_')
        .replace(/_+/g, '_');

const normalizeFieldName = (field, index) => {
    if (field?.name) {
        return field.name;
    }

    const baseLabel = field?.label || field?.type || 'field';
    return `${field?.type || 'field'}_${index + 1}_${slugify(baseLabel)}`;
};

const normalizeAdminSchema = (schema) => {
    if (!schema) {
        return {
            key: 'default',
            label: 'Borang Bantuan',
            sections: [
                { key: 'maklumat', title: 'Maklumat Permohonan', fields: [] },
                { key: 'dokumen', title: 'Dokumen Sokongan', fields: [] },
                { key: 'pengesahan', title: 'Pengesahan & Syarat', fields: [] },
            ],
        };
    }

    const fields = Array.isArray(schema?.fields) ? [...schema.fields] : [];
    fields.sort((left, right) => (left.order || 0) - (right.order || 0));

    const sections = {
        maklumat: [],
        dokumen: [],
        pengesahan: [],
    };

    fields.forEach((field, index) => {
        if (!field || !field.type) {
            return;
        }

        if (field.type === 'instruction') {
            sections.maklumat.push({
                id: `instruction_${index + 1}`,
                type: 'instruction',
                content: field.content || '',
            });
            return;
        }

        const normalized = {
            id: field.id || `${field.type}_${index + 1}`,
            name: normalizeFieldName(field, index),
            type: field.type,
            label: field.label || 'Soalan',
            required: !!field.required,
            placeholder: field.placeholder || '',
            options: Array.isArray(field.options)
                ? field.options
                : typeof field.options === 'string'
                  ? field.options.split(',').map((opt) => opt.trim()).filter(Boolean)
                  : [],
            accept: field.type === 'file' ? '.pdf,.jpg,.jpeg,.png' : undefined,
        };

        if (field.type === 'file') {
            sections.dokumen.push(normalized);
            return;
        }

        if (field.type === 'checkbox') {
            sections.pengesahan.push(normalized);
            return;
        }

        sections.maklumat.push(normalized);
    });

    const categoryKey = schema?.category_key || 'default';
    const builtSections = [
        {
            key: 'maklumat',
            title: 'Maklumat Permohonan',
            fields: sections.maklumat,
        },
        {
            key: 'dokumen',
            title: 'Dokumen Sokongan',
            fields: sections.dokumen,
        },
        {
            key: 'pengesahan',
            title: 'Pengesahan & Syarat',
            fields: sections.pengesahan,
        },
    ].filter((section) => Array.isArray(section.fields) && section.fields.length > 0);

    return {
        key: categoryKey,
        label: schema?.category_name || categoryKey,
        sections: builtSections.length
            ? builtSections
            : [
                {
                    key: 'maklumat',
                    title: 'Maklumat Permohonan',
                    fields: [],
                },
            ],
    };
};

const adminSchemas = computed(() => {
    const mapped = {};

    props.publishedSchemas.forEach((publishedSchema) => {
        if (!publishedSchema?.category_key) {
            return;
        }

        mapped[publishedSchema.category_key] = normalizeAdminSchema(publishedSchema);
    });

    return mapped;
});

const singleSchemaMode = computed(() => !!props.schema?.category_key);

const singleSchema = computed(() => {
    if (!singleSchemaMode.value) {
        return null;
    }

    const normalized = normalizeAdminSchema(props.schema);
    return normalized && normalized.sections ? normalized : null;
});

const schemas = computed(() => {
    if (singleSchemaMode.value) {
        if (singleSchema.value) {
            return {
                [singleSchema.value.key]: singleSchema.value,
            };
        }
        // Fallback if schema normalization fails
        return {
            default: normalizeAdminSchema(props.schema),
        };
    }

    return {
        ...fallbackSchemas,
        ...adminSchemas.value,
    };
});

const selectedCategory = ref(props.schema?.category_key || 'default' || 'pendidikan');

const categoryOptions = computed(() => {
    return Object.values(schemas.value).map((schema) => ({
        key: schema.key,
        label: schema.label,
    }));
});

watch(
    schemas,
    (nextSchemas) => {
        if (nextSchemas[selectedCategory.value]) {
            return;
        }

        const [firstKey] = Object.keys(nextSchemas);
        selectedCategory.value = firstKey || 'pendidikan';
    },
    { immediate: true },
);

const activeSchema = computed(() => {
    const schema = schemas.value[selectedCategory.value];
    if (!schema || !schema.sections) {
        return {
            key: 'empty',
            label: 'Borang',
            sections: [
                { key: 'maklumat', title: 'Maklumat Permohonan', fields: [] },
                { key: 'dokumen', title: 'Dokumen Sokongan', fields: [] },
                { key: 'pengesahan', title: 'Pengesahan & Syarat', fields: [] },
            ],
        };
    }
    return schema;
});

const form = useForm({
    status: 'submitted',
    requested_amount: null,
    triage_answers: {},
    dynamic_payload: {},
    category_tags: [],
});

const hasAppliedInitialData = ref(false);

const getFieldKey = (field) => field.name || field.id;

const getFieldOptions = (field) => {
    if (Array.isArray(field.options)) {
        return field.options;
    }

    if (typeof field.options === 'string') {
        return field.options
            .split(',')
            .map((option) => option.trim())
            .filter(Boolean);
    }

    return [];
};

const defaultValueByField = (field) => {
    if (field.type === 'checkbox') {
        return false;
    }

    if (field.type === 'file') {
        return null;
    }

    return '';
};

const initializeFormFromSchema = () => {
    const dynamicPayload = {};
    const triageAnswers = {};

    activeSchema.value.sections.forEach((section) => {
        section.fields.forEach((field) => {
            if (field.type === 'instruction') {
                return;
            }

            const fieldKey = getFieldKey(field);
            const defaultValue = defaultValueByField(field);

            if (section.key === 'pengesahan') {
                triageAnswers[fieldKey] = triageAnswers[fieldKey] ?? defaultValue;
                return;
            }

            if (field.type === 'radio') {
                form[fieldKey] = form[fieldKey] ?? defaultValue;
                dynamicPayload[fieldKey] = form[fieldKey];
                return;
            }

            dynamicPayload[fieldKey] = dynamicPayload[fieldKey] ?? defaultValue;
        });
    });

    if (props.initialData && !hasAppliedInitialData.value) {
        const initialDynamicPayload = props.initialData.dynamic_payload || {};
        const initialTriageAnswers = props.initialData.triage_answers || {};

        Object.keys(dynamicPayload).forEach((fieldKey) => {
            if (initialDynamicPayload[fieldKey] !== undefined) {
                dynamicPayload[fieldKey] = initialDynamicPayload[fieldKey];
            }
        });

        Object.keys(triageAnswers).forEach((fieldKey) => {
            if (initialTriageAnswers[fieldKey] !== undefined) {
                triageAnswers[fieldKey] = initialTriageAnswers[fieldKey];
            }
        });

        activeSchema.value.sections.forEach((section) => {
            section.fields.forEach((field) => {
                if (field.type !== 'radio') {
                    return;
                }

                const fieldKey = getFieldKey(field);
                if (initialDynamicPayload[fieldKey] !== undefined) {
                    form[fieldKey] = initialDynamicPayload[fieldKey];
                    dynamicPayload[fieldKey] = initialDynamicPayload[fieldKey];
                }
            });
        });

        hasAppliedInitialData.value = true;
    }

    form.dynamic_payload = dynamicPayload;
    form.triage_answers = triageAnswers;
    form.category_tags = props.initialData?.category_tags?.length ? props.initialData.category_tags : [selectedCategory.value];
    form.wallet_document_ids = props.initialData?.wallet_document_ids?.length ? props.initialData.wallet_document_ids : [];
}

watch(
    activeSchema,
    () => {
        initializeFormFromSchema();
    },
    { immediate: true },
);

const handleFileChange = (event, fieldName) => {
    const [file] = event.target.files || [];
    form.dynamic_payload[fieldName] = file || null;
};

const buildSubmissionPayload = () => {
    const dynamicPayload = {};
    const triageAnswers = {};

    activeSchema.value.sections.forEach((section) => {
        section.fields.forEach((field) => {
            if (field.type === 'instruction') {
                return;
            }

            const fieldKey = getFieldKey(field);

            if (section.key === 'pengesahan') {
                triageAnswers[fieldKey] = form.triage_answers[fieldKey] ?? false;
                return;
            }

            if (field.type === 'radio') {
                dynamicPayload[fieldKey] = form[fieldKey] ?? '';
                return;
            }

            dynamicPayload[fieldKey] = form.dynamic_payload[fieldKey] ?? '';
        });
    });

    return {
        requested_amount: form.requested_amount,
        triage_answers: triageAnswers,
        dynamic_payload: dynamicPayload,
        category_tags: [selectedCategory.value],
        wallet_document_ids: form.wallet_document_ids || [],
        form_id: props.schema?.id || null,
    };
};

const getSubmissionPayload = () => buildSubmissionPayload();

const submitDynamicForm = () => {
    const payload = buildSubmissionPayload();

    form.dynamic_payload = payload.dynamic_payload;
    form.triage_answers = payload.triage_answers;
    form.category_tags = payload.category_tags;

    form.post(route('applications.store'), {
        forceFormData: true,
        data: {
            ...payload,
            is_draft: false,
        },
    });
};

defineExpose({
    getSubmissionPayload,
    initializeFormFromSchema,
    isProcessing: computed(() => form.processing),
});
</script>

<template>
    <div class="mx-auto w-full max-w-5xl space-y-6">
        <div v-if="showHeader || !singleSchemaMode" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div v-if="showHeader">
                    <h2 class="text-xl font-semibold text-slate-900">Dynamic Application Form Engine</h2>
                    <p class="mt-1 text-sm text-slate-500">Satu komponen, pelbagai struktur borang mengikut kategori bantuan.</p>
                </div>
                <div v-if="!singleSchemaMode" class="w-full md:w-72">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Pilih Kategori</label>
                    <select
                        v-model="selectedCategory"
                        class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option v-for="option in categoryOptions" :key="option.key" :value="option.key">{{ option.label }}</option>
                    </select>
                </div>
            </div>
        </div>

        <form class="space-y-5" @submit.prevent="submitDynamicForm">
            <section
                v-for="section in activeSchema.sections"
                :key="section.key"
                class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100"
            >
                <h3 class="text-base font-semibold text-slate-900">{{ section.title }}</h3>

                <div v-if="!section.fields || section.fields.length === 0" class="mt-4 text-sm text-slate-500">
                    Tiada medan dalam bahagian ini.
                </div>

                <div v-else class="mt-4 space-y-4">
                    <div v-for="field in section.fields" :key="field.name || field.id" class="space-y-2">
                        <div
                            v-if="field.type === 'instruction'"
                            class="bg-blue-50 border border-blue-100 p-4 rounded-md mb-6"
                        >
                            <p class="whitespace-pre-wrap text-sm text-gray-700">{{ field.content }}</p>
                        </div>

                        <template v-else>
                            <label v-if="field.type !== 'checkbox'" :for="field.name || field.id" class="block text-sm font-medium text-slate-700">
                                {{ field.label }}
                                <span v-if="field.required" class="text-rose-500">*</span>
                            </label>

                            <select
                                v-if="field.type === 'select'"
                                :id="field.name || field.id"
                                v-model="form.dynamic_payload[getFieldKey(field)]"
                                :required="field.required"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option disabled value="">Sila pilih</option>
                                <option v-for="option in getFieldOptions(field)" :key="option" :value="option">{{ option }}</option>
                            </select>

                            <input
                                v-else-if="field.type === 'text'"
                                :id="field.name || field.id"
                                v-model="form.dynamic_payload[getFieldKey(field)]"
                                type="text"
                                :required="field.required"
                                :placeholder="field.placeholder || ''"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />

                            <textarea
                                v-else-if="field.type === 'textarea'"
                                :id="field.name || field.id"
                                v-model="form.dynamic_payload[getFieldKey(field)]"
                                :required="field.required"
                                :placeholder="field.placeholder || ''"
                                rows="4"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>

                            <div v-else-if="field.type === 'radio'" class="space-y-2">
                                <label
                                    v-for="(option, index) in getFieldOptions(field)"
                                    :key="`${field.name || field.id}-${index}`"
                                    class="flex gap-2 items-center"
                                >
                                    <input
                                        v-model="form[field.name || field.id]"
                                        type="radio"
                                        :name="field.name || field.id"
                                        :value="option"
                                        :required="field.required"
                                        class="border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-sm text-slate-700">{{ option }}</span>
                                </label>
                            </div>

                            <div
                                v-else-if="field.type === 'file'"
                                class="rounded-xl border border-dashed border-indigo-300 bg-indigo-50/50 p-4"
                            >
                                <label
                                    :for="getFieldKey(field)"
                                    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg bg-white p-4 text-center transition hover:bg-indigo-50"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <span class="text-sm font-medium text-slate-700">Klik untuk muat naik atau seret fail ke sini</span>
                                    <span class="text-xs text-slate-500">Format disokong: PDF, JPG, PNG</span>
                                    <input
                                        :id="getFieldKey(field)"
                                        type="file"
                                        class="hidden"
                                        :required="field.required"
                                        :accept="field.accept || ''"
                                        @change="handleFileChange($event, getFieldKey(field))"
                                    />
                                </label>

                                <p class="mt-3 inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                    Dokumen ini akan disimpan secara automatik ke dalam Dompet Dokumen anda untuk kegunaan masa depan.
                                </p>

                                <p v-if="form.dynamic_payload[getFieldKey(field)]" class="mt-2 text-xs text-slate-600">
                                    Fail dipilih: {{ form.dynamic_payload[getFieldKey(field)]?.name }}
                                </p>
                            </div>

                            <label
                                v-else-if="field.type === 'checkbox'"
                                class="inline-flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3"
                            >
                                <input
                                    :id="field.name"
                                    v-model="form.triage_answers[getFieldKey(field)]"
                                    type="checkbox"
                                    :required="field.required"
                                    class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="text-sm text-slate-700">{{ field.label }}</span>
                            </label>
                        </template>
                    </div>
                </div>
            </section>

            <div v-if="showActions" class="flex items-center justify-end gap-3">
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    @click="initializeFormFromSchema"
                >
                    Set Semula Nilai
                </button>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500"
                >
                    {{ form.processing ? 'Menghantar...' : 'Hantar Permohonan Dinamik' }}
                </button>
            </div>
        </form>
    </div>
</template>
