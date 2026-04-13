<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    applications: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const filterForm = reactive({
    q: props.filters?.q || '',
    status: props.filters?.status || '',
    category: props.filters?.category || '',
    sort: props.filters?.sort || 'newest',
    scope: props.filters?.scope || 'queue',
});

const applyFilters = () => {
    router.get(route('admin.approvals.index'), {
        q: filterForm.q || undefined,
        status: filterForm.status || undefined,
        category: filterForm.category || undefined,
        sort: filterForm.sort || undefined,
        scope: filterForm.scope || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.q = '';
    filterForm.status = '';
    filterForm.category = '';
    filterForm.sort = 'newest';
    filterForm.scope = 'queue';
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

const statusBadgeClass = (status) => {
    if (status === 'approved') {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (status === 'rejected') {
        return 'bg-rose-100 text-rose-700';
    }

    if (status === 'kuiri') {
        return 'bg-amber-100 text-amber-700';
    }

    if (status === 'under_review') {
        return 'bg-blue-100 text-blue-700';
    }

    return 'bg-slate-100 text-slate-700';
};

const statusLabel = (status) => {
    const labels = {
        submitted: 'Dihantar',
        under_review: 'Sedang Disemak',
        kuiri: 'Kuiri',
        approved: 'Diluluskan',
        rejected: 'Ditolak',
        disbursed: 'Selesai Bayaran',
    };

    return labels[status] || status || '-';
};
</script>

<template>
    <Head title="Senarai Semakan Permohonan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Senarai Semakan Permohonan</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Queue Kelulusan</h3>
                            <p class="mt-1 text-sm text-gray-500">Default: hanya permohonan yang perlu tindakan jawatankuasa dipaparkan (Dihantar, Sedang Disemak, Kuiri).</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold"
                                :class="filterForm.scope === 'queue' ? 'bg-slate-800 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-100'"
                                @click="filterForm.scope = 'queue'; applyFilters()"
                            >
                                Dalam Queue
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold"
                                :class="filterForm.scope === 'all' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-100'"
                                @click="filterForm.scope = 'all'; applyFilters()"
                            >
                                Semua Rekod
                            </button>
                        </div>
                    </div>

                    <div class="mb-4 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-4">
                            <div class="lg:col-span-2">
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Carian No Permohonan / Nama / ID Ahli</label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="filterForm.q"
                                        type="text"
                                        class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Contoh: APP-1001 / Ali / MBR-0001"
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
                                    <option value="rejected">rejected</option>
                                    <option value="disbursed">disbursed</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Kategori Bantuan</label>
                                <select v-model="filterForm.category" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" @change="applyFilters">
                                    <option value="">Semua kategori</option>
                                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-semibold text-slate-600">Sort</label>
                                <select v-model="filterForm.sort" class="rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500" @change="applyFilters">
                                    <option value="newest">Terkini</option>
                                    <option value="oldest">Paling Lama</option>
                                    <option value="applicant_az">Nama A-Z</option>
                                    <option value="applicant_za">Nama Z-A</option>
                                </select>
                            </div>

                            <button
                                type="button"
                                class="ml-auto rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-100"
                                @click="resetFilters"
                            >
                                Reset
                            </button>
                        </div>
                    </div>

                    <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-700">
                        Peringatan: Rekod berstatus <strong>Diluluskan</strong> atau <strong>Ditolak</strong> tidak dipaparkan dalam queue default. Tukar ke <strong>Semua Rekod</strong> jika perlu semakan semula atau audit.
                    </div>

                    <div class="mb-4 rounded-lg border border-slate-200 bg-white px-4 py-3">
                        <p class="text-xs font-semibold text-slate-600">Petunjuk Status</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">Dihantar</span>
                            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700">Sedang Disemak</span>
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">Kuiri</span>
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">Diluluskan</span>
                            <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700">Ditolak</span>
                            <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700">Selesai Bayaran</span>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <Link
                            v-for="item in applications.data"
                            :key="item.id"
                            :href="route('admin.approvals.show', item.id)"
                            class="block rounded border border-gray-200 p-4 hover:bg-gray-50"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900">{{ item.reference_no || `Application #${item.id}` }}</p>
                                    <p class="mt-1 text-xs text-gray-600">Pemohon: {{ item.user?.name || 'Tidak dinyatakan' }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-lg border border-gray-300 bg-white px-2 py-1 text-xs font-semibold text-gray-700">Lihat Detail</span>
                                    <span class="rounded-full px-2 py-1 text-xs font-medium" :class="statusBadgeClass(item.status)">{{ statusLabel(item.status) }}</span>
                                    <span
                                        v-if="item.category_tags?.length"
                                        class="rounded-lg bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm"
                                    >
                                        {{ item.category_tags[0] }}
                                    </span>
                                </div>
                            </div>
                            <p class="mt-2 text-xs font-medium text-indigo-700" v-if="item.category_tags?.length">Kategori Utama: {{ item.category_tags[0] }}</p>
                        </Link>

                        <div v-if="!applications.data.length" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                            Tiada permohonan yang mematuhi kriteria carian semasa.
                        </div>
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
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
