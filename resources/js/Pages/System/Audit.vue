<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    logs: {
        type: Object,
        default: () => ({ data: [] }),
    },
    memberLogs: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({ q: '' }),
    },
});

const memberAction = ref(props.filters?.member_action || '');
const memberPageInput = ref('');

const memberActionOptions = [
    { value: '', label: 'Semua Aksi Ahli' },
    { value: 'member_manual_create', label: 'Tambah Ahli Manual' },
    { value: 'member_manual_update', label: 'Kemaskini Ahli Manual' },
    { value: 'member_csv_import', label: 'Import CSV Ahli' },
    { value: 'member_csv_export', label: 'Export CSV Ahli' },
];

const actionLabelMap = {
    member_manual_create: 'Tambah Ahli Manual',
    member_manual_update: 'Kemaskini Ahli Manual',
    member_csv_import: 'Import CSV Ahli',
    member_csv_export: 'Export CSV Ahli',
};

const normalizedMemberLogs = computed(() => {
    return (props.memberLogs?.data || []).map((log) => ({
        ...log,
        action_label: actionLabelMap[log.action] || log.action,
    }));
});

const onSearch = (event) => {
    router.get(route('admin.audit.index'), {
        q: event.target.value,
        member_action: memberAction.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const onMemberActionFilter = () => {
    router.get(route('admin.audit.index'), {
        q: props.filters?.q || undefined,
        member_action: memberAction.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const goToMemberPage = (url) => {
    if (!url) {
        return;
    }

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
    });
};

const jumpToMemberPage = () => {
    const lastPage = Number(props.memberLogs?.last_page || 1);
    const parsedPage = Number(memberPageInput.value);

    if (!Number.isInteger(parsedPage) || parsedPage < 1 || parsedPage > lastPage) {
        return;
    }

    router.get(route('admin.audit.index'), {
        q: props.filters?.q || undefined,
        member_action: memberAction.value || undefined,
        page: props.logs?.current_page || undefined,
        member_page: parsedPage,
    }, {
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
                    <p class="text-sm font-medium text-slate-700">Tujuan Audit Operasi (Superadmin): rekod ini menjadi rujukan rasmi untuk semakan pematuhan proses, siasatan isu, dan pengesahan siapa melakukan tindakan pada sesuatu masa.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-slate-600">
                        <li>Jejak perubahan status permohonan (maker/checker/kelulusan).</li>
                        <li>Jejak tindakan pengurusan ahli (tambah manual, kemaskini, import, export).</li>
                        <li>Bukti audit untuk semakan dalaman dan rekonsiliasi operasi.</li>
                    </ul>

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

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <p class="text-sm font-medium text-slate-700">Audit Pengurusan Ahli: rekod tindakan tambah manual, kemaskini ahli, import CSV, dan export CSV oleh superadmin.</p>

                    <div class="mt-4 max-w-xs">
                        <select
                            v-model="memberAction"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @change="onMemberActionFilter"
                        >
                            <option v-for="option in memberActionOptions" :key="option.value || 'all'" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Masa</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Aksi</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Pelaksana</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Ahli</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">No. Ahli</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Ringkasan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-if="!normalizedMemberLogs.length">
                                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">Tiada audit pengurusan ahli dijumpai.</td>
                                </tr>
                                <tr v-for="log in normalizedMemberLogs" :key="`member-${log.id}`">
                                    <td class="px-4 py-3 text-slate-600">{{ log.created_at }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex whitespace-nowrap rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ log.action_label }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ log.actor_name }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ log.member_name }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ log.member_no }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        <span v-if="log.action === 'member_csv_import'">
                                            Diproses: {{ log.context?.processed || 0 }}, Cipta: {{ log.context?.created || 0 }}, Kemaskini: {{ log.context?.updated || 0 }}, Langkau: {{ log.context?.skipped || 0 }}
                                        </span>
                                        <span v-else-if="log.action === 'member_csv_export'">
                                            Rekod dieksport: {{ log.context?.record_count || 0 }}
                                        </span>
                                        <span v-else-if="log.action === 'member_manual_update'">
                                            Field diubah: {{ (log.context?.changed_fields || []).join(', ') || '-' }}
                                        </span>
                                        <span v-else>
                                            {{ log.context?.member_no || '-' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="memberLogs?.links?.length > 3" class="mt-4 flex flex-wrap items-center gap-2">
                        <button
                            v-for="(link, index) in memberLogs.links"
                            :key="`member-page-${index}`"
                            type="button"
                            :class="[
                                'whitespace-nowrap rounded-lg border px-3 py-1.5 text-xs font-semibold',
                                link.active
                                    ? 'border-indigo-600 bg-indigo-600 text-white'
                                    : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
                                !link.url ? 'pointer-events-none opacity-50' : '',
                            ]"
                            :disabled="!link.url"
                            v-html="link.label"
                            @click="goToMemberPage(link.url)"
                        ></button>

                        <div class="ml-auto flex items-center gap-2">
                            <label class="text-xs font-semibold text-slate-600">Pergi Halaman</label>
                            <input
                                v-model="memberPageInput"
                                type="number"
                                min="1"
                                :max="memberLogs?.last_page || 1"
                                class="w-20 rounded-lg border-slate-300 px-2 py-1 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <button
                                type="button"
                                class="whitespace-nowrap rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                                @click="jumpToMemberPage"
                            >
                                Pergi
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
