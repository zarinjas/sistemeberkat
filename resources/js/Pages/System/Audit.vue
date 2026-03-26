<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    logs: {
        type: Object,
        default: () => ({ data: [] }),
    },
    filters: {
        type: Object,
        default: () => ({ q: '' }),
    },
});

const onSearch = (event) => {
    router.get(route('admin.audit.index'), { q: event.target.value }, {
        preserveState: true,
        replace: true,
    });
};
</script>

<template>
    <Head title="Audit Operasi" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Audit Operasi</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <p class="text-sm font-medium text-slate-700">Arahan Admin (BM): Gunakan carian untuk semak jejak tindakan pengguna (maker/checker/kelulusan) mengikut rujukan atau catatan.</p>

                    <div class="mt-4 max-w-md">
                        <input
                            type="text"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Cari rujukan, status, nama pegawai atau nota"
                            :value="filters?.q || ''"
                            @input="onSearch"
                        >
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Rujukan</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Pemohon</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Dari</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Ke</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Diubah Oleh</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Catatan</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Masa</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-if="!logs?.data?.length">
                                    <td colspan="7" class="px-4 py-6 text-center text-slate-500">Tiada log audit dijumpai.</td>
                                </tr>
                                <tr v-for="log in logs.data || []" :key="log.id">
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ log.reference_no }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ log.applicant_name }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ log.from_status }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ log.to_status }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ log.changed_by }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ log.notes }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ log.changed_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
