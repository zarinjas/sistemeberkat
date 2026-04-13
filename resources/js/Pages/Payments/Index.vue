<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, reactive } from 'vue';

const props = defineProps({
    applications: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    categories: {
        type: Array,
        default: () => [],
    },
    canManagePayments: {
        type: Boolean,
        default: false,
    },
});

const filterForm = reactive({
    q: props.filters?.q || '',
    status: props.filters?.status || '',
    category: props.filters?.category || '',
    action: props.filters?.action || '',
    urgent_first: Boolean(props.filters?.urgent_first),
});

const storageKey = 'payments:index:filters';

const saveFilters = () => {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(storageKey, JSON.stringify({
        q: filterForm.q,
        status: filterForm.status,
        category: filterForm.category,
        action: filterForm.action,
        urgent_first: filterForm.urgent_first,
    }));
};

const hasIncomingFilters = () => Boolean(
    props.filters?.q
    || props.filters?.status
    || props.filters?.category
    || props.filters?.action
    || props.filters?.urgent_first,
);

onMounted(() => {
    if (typeof window === 'undefined' || hasIncomingFilters()) {
        return;
    }

    const raw = window.localStorage.getItem(storageKey);
    if (!raw) {
        return;
    }

    try {
        const saved = JSON.parse(raw);
        filterForm.q = saved?.q || '';
        filterForm.status = saved?.status || '';
        filterForm.category = saved?.category || '';
        filterForm.action = saved?.action || '';
        filterForm.urgent_first = Boolean(saved?.urgent_first);

        if (filterForm.q || filterForm.status || filterForm.category || filterForm.action || filterForm.urgent_first) {
            applyFilters();
        }
    } catch {
        window.localStorage.removeItem(storageKey);
    }
});

const applyFilters = () => {
    saveFilters();

    router.get(route('admin.payments.index'), {
        q: filterForm.q || undefined,
        status: filterForm.status || undefined,
        category: filterForm.category || undefined,
        action: filterForm.action || undefined,
        urgent_first: filterForm.urgent_first ? 1 : undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.q = '';
    filterForm.status = '';
    filterForm.category = '';
    filterForm.action = '';
    filterForm.urgent_first = false;
    saveFilters();
    applyFilters();
};

const setActionFilter = (value) => {
    filterForm.action = value;
    applyFilters();
};

const goToPage = (url) => {
    if (!url) {
        return;
    }

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
    });
};

const openDetail = (applicationId) => {
    router.visit(route('admin.payments.show', applicationId));
};

const statusBadgeClass = (status) => {
    if (status === 'approved') {
        return 'bg-blue-100 text-blue-700';
    }

    if (status === 'disbursed') {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (status === 'rejected') {
        return 'bg-rose-100 text-rose-700';
    }

    if (status === 'under_review') {
        return 'bg-amber-100 text-amber-700';
    }

    if (status === 'kuiri') {
        return 'bg-orange-100 text-orange-700';
    }

    return 'bg-slate-100 text-slate-700';
};

const statusLabel = (status) => {
    const labels = {
        submitted: 'Submitted',
        under_review: 'Under Review',
        kuiri: 'Kuiri',
        approved: 'Approved',
        disbursed: 'Disbursed',
        rejected: 'Rejected',
    };

    return labels[status] || status || '-';
};

const actionChip = (item) => {
    if (item.can_disburse) {
        return { label: 'Sedia Sahkan', className: 'bg-indigo-100 text-indigo-700' };
    }

    if (item.can_prepare) {
        return { label: 'Perlu Tindakan', className: 'bg-amber-100 text-amber-700' };
    }

    if (item.status === 'disbursed') {
        return { label: 'Selesai', className: 'bg-emerald-100 text-emerald-700' };
    }

    if (['submitted', 'under_review', 'kuiri'].includes(item.status)) {
        return { label: 'Menunggu Kelulusan', className: 'bg-slate-100 text-slate-700' };
    }

    return null;
};
</script>

<template>
    <Head title="Modul Bayaran Permohonan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Modul Bayaran Permohonan</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Senarai Ringkas Permohonan</h3>
                            <p class="mt-1 text-sm text-slate-500">Klik mana-mana rekod untuk lihat detail penuh dan tindakan transaksi.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Link
                                :href="route('admin.payments.export')"
                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Muat Turun CSV Bayaran
                            </Link>
                            <Link
                                :href="route('admin.audit.index')"
                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Lihat Audit Operasi
                            </Link>
                        </div>
                    </div>

                    <div class="mt-5 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-4">
                            <div class="lg:col-span-2">
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Carian No Permohonan / Nama</label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="filterForm.q"
                                        type="text"
                                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Contoh: APP-1001 atau nama pemohon"
                                        @keyup.enter="applyFilters"
                                    >
                                    <button
                                        type="button"
                                        class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-500"
                                        @click="applyFilters"
                                    >
                                        Search
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Status</label>
                                <select v-model="filterForm.status" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" @change="applyFilters">
                                    <option value="">Semua status</option>
                                    <option value="submitted">submitted</option>
                                    <option value="under_review">under_review</option>
                                    <option value="kuiri">kuiri</option>
                                    <option value="approved">approved</option>
                                    <option value="disbursed">disbursed</option>
                                    <option value="rejected">rejected</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Kategori Form</label>
                                <select v-model="filterForm.category" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" @change="applyFilters">
                                    <option value="">Semua kategori</option>
                                    <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold"
                                :class="filterForm.action === '' ? 'bg-slate-800 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-100'"
                                @click="setActionFilter('')"
                            >
                                Semua
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold"
                                :class="filterForm.action === 'needs_action' ? 'bg-amber-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-100'"
                                @click="setActionFilter('needs_action')"
                            >
                                Perlu Rekod Bayaran
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold"
                                :class="filterForm.action === 'ready_to_disburse' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-100'"
                                @click="setActionFilter('ready_to_disburse')"
                            >
                                Sedia Sahkan
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold"
                                :class="filterForm.action === 'completed' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-100'"
                                @click="setActionFilter('completed')"
                            >
                                Selesai
                            </button>
                            <button
                                type="button"
                                class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-100"
                                @click="resetFilters"
                            >
                                Reset
                            </button>

                            <label class="ml-auto inline-flex items-center gap-2 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-300">
                                <input
                                    v-model="filterForm.urgent_first"
                                    type="checkbox"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    @change="applyFilters"
                                >
                                Perlu Tindakan Dahulu
                            </label>
                        </div>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">No Permohonan</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Nama</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-if="!applications?.data?.length">
                                    <td colspan="4" class="px-4 py-6 text-center text-slate-500">Tiada rekod padanan untuk penapis semasa.</td>
                                </tr>
                                <tr
                                    v-for="item in applications.data || []"
                                    :key="item.id"
                                    class="hover:bg-slate-50"
                                    @click="openDetail(item.id)"
                                >
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ item.reference_no }}</td>
                                    <td class="px-4 py-3 text-slate-700">
                                        <p>{{ item.applicant_name }}</p>
                                        <p class="text-xs text-slate-500">{{ item.category }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusBadgeClass(item.status)">
                                            {{ statusLabel(item.status) }}
                                        </span>
                                        <span
                                            v-if="actionChip(item)"
                                            class="ml-2 inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                            :class="actionChip(item).className"
                                        >
                                            {{ actionChip(item).label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                                            @click.stop="openDetail(item.id)"
                                        >
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M10 3c4.393 0 8.048 2.9 9.25 6.875a1 1 0 010 .25C18.048 14.1 14.393 17 10 17s-8.048-2.9-9.25-6.875a1 1 0 010-.25C1.952 5.9 5.607 3 10 3zm0 2C6.72 5 3.954 7.09 2.83 10 3.954 12.91 6.72 15 10 15s6.046-2.09 7.17-5C16.046 7.09 13.28 5 10 5zm0 2.5a2.5 2.5 0 110 5 2.5 2.5 0 010-5z" />
                                            </svg>
                                            Lihat Detail
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="applications?.links?.length > 3" class="mt-4 flex flex-wrap gap-2">
                        <button
                            v-for="(link, index) in applications.links"
                            :key="index"
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-xs font-semibold"
                            :class="link.active ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50'"
                            :disabled="!link.url"
                            v-html="link.label"
                            @click="goToPage(link.url)"
                        ></button>
                    </div>

                    <p v-if="!canManagePayments" class="mt-4 rounded-xl bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700 ring-1 ring-amber-200">
                        Pegawai penyemak hanya boleh lihat rekod transaksi. Rekod / sahkan bayaran hanya untuk superadmin.
                    </p>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
