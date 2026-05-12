<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    application: {
        type: Object,
        required: true,
    },
    submittedForm: {
        type: Object,
        default: () => ({ sections: [] }),
    },
});

const printPage = () => {
    window.print();
};
</script>

<template>
    <Head title="Detail Permohonan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Detail Permohonan</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-2 print:hidden">
                        <h3 class="text-lg font-semibold text-gray-900">{{ application.reference_no }}</h3>
                        <div class="flex items-center gap-2">
                            <a
                                :href="route('applications.pdf', application.id)"
                                target="_blank"
                                class="rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                            >
                                Muat Turun PDF Rasmi
                            </a>
                            <button
                                type="button"
                                class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500"
                                @click="printPage"
                            >
                                Cetak / Simpan PDF
                            </button>
                        </div>
                    </div>

                    <p class="mt-1 text-sm text-gray-500">Status: {{ application.status }}</p>
                    <p class="mt-2 text-sm text-gray-700">Priority: {{ application.priority_label }} ({{ application.priority_score }})</p>
                    <p v-if="application.priority_reason" class="mt-1 text-sm text-red-600">{{ application.priority_reason }}</p>

                    <h4 class="mt-6 text-sm font-semibold text-gray-900">Paparan Borang Dihantar</h4>
                    <div v-if="!submittedForm?.sections?.length" class="mt-2 rounded border border-dashed border-gray-300 bg-gray-50 p-3 text-sm text-gray-500">
                        Data borang tidak tersedia.
                    </div>
                    <div v-else class="mt-3 space-y-3">
                        <section
                            v-for="section in submittedForm.sections"
                            :key="section.key"
                            class="rounded border border-gray-200 p-3"
                        >
                            <p class="text-sm font-semibold text-gray-900">{{ section.title }}</p>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                <article v-for="row in section.rows" :key="`${section.key}-${row.label}`" class="rounded bg-gray-50 p-2.5">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ row.label }}</p>
                                    <p class="mt-1 text-sm text-gray-800">{{ row.value }}</p>
                                </article>
                            </div>
                        </section>
                    </div>

                    <h4 class="mt-5 text-sm font-semibold text-gray-900">Timeline</h4>
                    <div class="mt-2 space-y-2">
                        <div v-for="entry in application.status_histories" :key="entry.id" class="rounded border border-gray-200 p-3 text-sm">
                            <p class="font-medium text-gray-900">{{ entry.to_status }}</p>
                            <p class="text-xs text-gray-500">{{ entry.changed_at }}</p>
                            <p v-if="entry.notes" class="mt-1 text-gray-600">{{ entry.notes }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
