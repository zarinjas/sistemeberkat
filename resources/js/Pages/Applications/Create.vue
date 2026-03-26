<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
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

const flashSuccess = computed(() => page.props.flash?.success || '');
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
    submitForm.wallet_document_ids = payload.wallet_document_ids;
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
    <Head title="Permohonan Baharu" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ formTitle }}</h2>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto w-full max-w-6xl px-4 sm:px-6 lg:px-8">
                <div v-if="!canRenderForm" class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                    Borang yang dipilih tidak ditemui atau belum diterbitkan. Sila kembali ke Dashboard dan pilih borang aktif.
                </div>

                <div v-else class="space-y-4">
                    <div v-if="flashSuccess" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ flashSuccess }}
                    </div>

                    <div v-if="actionError || firstValidationError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                        {{ firstValidationError || actionError }}
                    </div>

                    <DynamicApplicationForm
                        ref="formEngineRef"
                        :schema="formSchema"
                        :show-actions="false"
                    />

                    <div class="flex flex-wrap items-center justify-end gap-3">
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
    </AuthenticatedLayout>
</template>
