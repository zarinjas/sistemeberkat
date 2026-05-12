<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import DynamicApplicationForm from '@/Components/DynamicApplicationForm.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    formSchema: {
        type: Object,
        default: null,
    },
    formTitle: {
        type: String,
        default: 'Permohonan Baharu',
    },
    draftApplication: {
        type: Object,
        default: null,
    },
});

const formEngineRef = ref(null);
const page = usePage();
const actionError = ref('');
const showPdfPreview = ref(false);
const previewSections = ref([]);

const flashSuccess = computed(() => page.props.flash?.success || '');
const appLogoUrl = computed(() => page.props.branding?.logo_url || '');
const officialFormTitle = computed(() => props.formSchema?.category_name || props.formTitle || 'Borang Permohonan');
const formContextLabel = computed(() => (props.draftApplication ? 'Sambung draf permohonan rasmi' : 'Permohonan rasmi BERKAT'));
const formVersionLabel = computed(() => {
    const version = props.formSchema?.version;

    return version ? `Versi ${version}` : 'Versi semasa';
});
const formReferenceLabel = computed(() => {
    const formId = props.formSchema?.id;

    return formId ? `Form ID #${formId}` : 'Form aktif';
});
const firstValidationError = computed(() => {
    const entries = Object.entries(submitForm.errors || {});
    return entries.length ? entries[0][1] : '';
});

const submitForm = useForm({
    draft_application_id: props.draftApplication?.id || null,
    form_id: props.formSchema?.id || null,
    requested_amount: null,
    triage_answers: {},
    dynamic_payload: {},
    category_tags: [],
    is_draft: false,
});

const canRenderForm = computed(() => !!props.formSchema);

const normalizeFieldName = (field, index) => {
    if (field?.name) {
        return field.name;
    }

    const baseLabel = String(field?.label || field?.type || 'field')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '_')
        .replace(/_+/g, '_');

    return `${field?.type || 'field'}_${index + 1}_${baseLabel}`;
};

const formatPreviewValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    if (typeof value === 'boolean') {
        return value ? 'Ya' : 'Tidak';
    }

    if (Array.isArray(value)) {
        return value.length ? value.join(', ') : '-';
    }

    if (typeof value === 'object') {
        if (value.original_name) {
            return `Fail: ${value.original_name}`;
        }

        return JSON.stringify(value);
    }

    return String(value);
};

const openPdfPreview = () => {
    if (!formEngineRef.value) {
        return;
    }

    const payload = formEngineRef.value.getSubmissionPayload();
    const fields = [...(props.formSchema?.fields || [])].sort((left, right) => (left.order || 0) - (right.order || 0));
    const sections = {
        maklumat: {
            title: 'Maklumat Permohonan',
            rows: [],
        },
        dokumen: {
            title: 'Dokumen Sokongan',
            rows: [],
        },
        pengesahan: {
            title: 'Pengesahan & Syarat',
            rows: [],
        },
    };

    fields.forEach((field, index) => {
        if (!field || field.type === 'instruction') {
            return;
        }

        const fieldName = normalizeFieldName(field, index);
        const label = field.label || 'Soalan';
        const type = field.type || 'text';
        const rawValue = type === 'checkbox'
            ? payload.triage_answers?.[fieldName]
            : payload.dynamic_payload?.[fieldName];

        const sectionKey = type === 'file'
            ? 'dokumen'
            : (type === 'checkbox' ? 'pengesahan' : 'maklumat');

        sections[sectionKey].rows.push({
            label,
            value: formatPreviewValue(rawValue),
        });
    });

    previewSections.value = Object.values(sections).filter((section) => section.rows.length > 0);
    showPdfPreview.value = true;
};

const closePdfPreview = () => {
    showPdfPreview.value = false;
};

const printPdfPreview = () => {
    window.print();
};

const postApplication = (isDraft) => {
    actionError.value = '';

    if (!formEngineRef.value) {
        actionError.value = 'Borang belum dimuatkan. Sila cuba lagi.';
        return;
    }

    const payload = formEngineRef.value.getSubmissionPayload();

    submitForm.form_id = props.formSchema?.id || null;
    submitForm.draft_application_id = props.draftApplication?.id || null;
    submitForm.requested_amount = payload.requested_amount;
    submitForm.triage_answers = payload.triage_answers;
    submitForm.dynamic_payload = payload.dynamic_payload;
    submitForm.category_tags = payload.category_tags;
    submitForm.is_draft = isDraft;
    submitForm.post(route('applications.store'), {
        forceFormData: true,
        onError: () => {
            actionError.value = 'Permohonan tidak dapat dihantar. Sila semak medan wajib.';
        },
    });
};

const saveDraft = () => postApplication(true);
const submitFinal = () => postApplication(false);
</script>

<template>
    <Head :title="officialFormTitle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-center gap-2 py-4">
                <div class="flex items-center justify-center">
                    <img
                        v-if="appLogoUrl"
                        :src="appLogoUrl"
                        alt="Logo e-BERKAT"
                        class="mb-2 h-14 w-auto object-contain"
                    />
                    <ApplicationLogo v-else class="mb-2 h-14 w-auto" />
                </div>
                <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-600 shadow-sm">
                    Dokumen Rasmi e-BERKAT
                </span>
                <h2 class="text-center text-2xl font-bold leading-tight tracking-tight text-slate-900">{{ officialFormTitle }}</h2>
                <p class="text-sm text-slate-500">{{ formContextLabel }}</p>
                <div class="mt-2 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-500">
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">{{ formVersionLabel }}</span>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">{{ formReferenceLabel }}</span>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                <div v-if="!canRenderForm" class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                    Borang yang dipilih tidak ditemui atau belum diterbitkan. Sila kembali ke Dashboard dan pilih borang aktif.
                </div>

                <div v-else class="space-y-6">
                    <div class="rounded-2xl border border-slate-200 bg-white/80 shadow-md p-6">
                        <div v-if="flashSuccess" class="mb-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                            {{ flashSuccess }}
                        </div>
                        <div v-if="actionError || firstValidationError" class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                            {{ firstValidationError || actionError }}
                        </div>
                        <div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-b from-slate-50 to-white">
                            <div class="border-b border-slate-200 px-5 py-4 text-center">
                                <img
                                    v-if="appLogoUrl"
                                    :src="appLogoUrl"
                                    alt="Logo e-BERKAT"
                                    class="mx-auto mb-3 h-12 w-auto object-contain"
                                />
                                <ApplicationLogo v-else class="mx-auto mb-3 h-12 w-auto" />
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Borang Permohonan Rasmi</p>
                                <h3 class="mt-2 text-xl font-bold text-slate-900">{{ officialFormTitle }}</h3>
                                <p class="mt-1 text-xs text-slate-500">Sila lengkapkan maklumat di bawah dengan tepat dan teliti.</p>
                            </div>
                            <div class="flex flex-wrap items-center justify-center gap-2 px-5 py-3 text-xs text-slate-500 sm:justify-between">
                                <span class="font-medium text-slate-600">Permohonan diproses secara digital melalui platform e-BERKAT</span>
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">{{ formVersionLabel }}</span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">{{ formReferenceLabel }}</span>
                                </div>
                            </div>
                        </div>
                        <DynamicApplicationForm
                            ref="formEngineRef"
                            :schema="formSchema"
                            :show-actions="false"
                            :show-header="false"
                        />
                        <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                            <button
                                type="button"
                                class="inline-flex items-center rounded-lg border border-indigo-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="submitForm.processing"
                                @click="openPdfPreview"
                            >
                                Preview PDF
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center rounded-lg border border-slate-300 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="submitForm.processing"
                                @click="saveDraft"
                            >
                                ⏸️ Simpan Sebagai Draf
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="submitForm.processing"
                                @click="submitFinal"
                            >
                                {{ submitForm.processing ? 'Menghantar...' : 'Hantar' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showPdfPreview"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 px-4 print:static print:bg-transparent"
            @click.self="closePdfPreview"
        >
            <div class="w-full max-w-4xl rounded-2xl bg-white p-5 shadow-2xl print:max-w-none print:rounded-none print:shadow-none">
                <div class="mb-4 flex items-center justify-between gap-2 print:hidden">
                    <h3 class="text-base font-semibold text-slate-900">Preview Borang (PDF)</h3>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                            @click="closePdfPreview"
                        >
                            Tutup
                        </button>
                        <button
                            type="button"
                            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                            @click="printPdfPreview"
                        >
                            Cetak / Simpan PDF
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <img
                            v-if="appLogoUrl"
                            :src="appLogoUrl"
                            alt="Logo e-BERKAT"
                            class="h-7 w-auto object-contain"
                        />
                        <ApplicationLogo v-else class="h-7 w-7" />
                        <h4 class="text-lg font-bold text-slate-900">{{ officialFormTitle }}</h4>
                    </div>
                    <section
                        v-for="section in previewSections"
                        :key="section.title"
                        class="rounded-xl border border-slate-200 p-4"
                    >
                        <h5 class="text-sm font-semibold text-slate-800">{{ section.title }}</h5>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <article v-for="row in section.rows" :key="`${section.title}-${row.label}`" class="rounded-lg bg-slate-50 p-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ row.label }}</p>
                                <p class="mt-1 text-sm text-slate-800">{{ row.value }}</p>
                            </article>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
