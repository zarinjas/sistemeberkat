<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    forms: {
        type: Array,
        default: () => [],
    },
});

const previewOpen = ref(false);
const selectedSchema = ref(null);

const openPreview = (form) => {
    selectedSchema.value = form;
    previewOpen.value = true;
};

const deleteForm = (formId) => {
    if (confirm('Adakah anda pasti ingin memadam borang ini?')) {
        router.delete(route('admin.forms.destroy', formId), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Senarai Borang Diterbitkan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Senarai Borang Diterbitkan</h2>
                <Link
                    :href="route('forms.builder')"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                >
                    + Bina Borang Baru
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <div v-if="!props.forms.length" class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2">Tiada borang diterbitkan lagi.</p>
                        <Link
                            :href="route('forms.builder')"
                            class="mt-3 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            Mulai Bina Borang
                        </Link>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Tajuk</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Kod</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Versi</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Medan</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Tarikh Diterbitkan</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white text-slate-700">
                                <tr v-for="form in props.forms" :key="form.id">
                                    <td class="whitespace-nowrap px-4 py-3 font-medium">{{ form.category_name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ form.category_key }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ form.version }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ form.fields_count }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        {{
                                            form.published_at ? new Date(form.published_at).toLocaleString('ms-MY') : '-'
                                        }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="form.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'"
                                        >
                                            {{ form.is_active ? 'Aktif' : 'Arkib' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                @click="openPreview(form)"
                                            >
                                                Lihat
                                            </button>
                                            <button
                                                v-if="!form.is_active"
                                                type="button"
                                                class="rounded-lg border border-rose-300 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                                                @click="deleteForm(form.id)"
                                            >
                                                Padam
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
