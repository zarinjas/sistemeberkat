<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    kpiCards: {
        type: Array,
        default: () => [],
    },
    generalQueue: {
        type: Array,
        default: () => [],
    },
});

const defaultKpiCards = [
    {
        key: 'new',
        title: 'Jumlah Permohonan Baharu',
        value: 42,
        trend: '+5% dari minggu lepas',
        cardClass: 'bg-white ring-1 ring-slate-100',
        valueClass: 'text-slate-900',
        trendClass: 'text-slate-500',
        iconBg: 'bg-slate-100 text-slate-700',
    },
    {
        key: 'pending',
        title: 'Menunggu Kelulusan (Pending)',
        value: 18,
        trend: '+2% dari minggu lepas',
        cardClass: 'bg-amber-50 ring-1 ring-amber-200',
        valueClass: 'text-amber-700',
        trendClass: 'text-amber-700',
        iconBg: 'bg-amber-100 text-amber-700',
    },
    {
        key: 'approved',
        title: 'Lulus Bulan Ini',
        value: 77,
        trend: '+12% dari bulan lepas',
        cardClass: 'bg-emerald-50 ring-1 ring-emerald-200',
        valueClass: 'text-emerald-700',
        trendClass: 'text-emerald-700',
        iconBg: 'bg-emerald-100 text-emerald-700',
    },
];

const kpiCards = computed(() => (props.kpiCards.length ? props.kpiCards : defaultKpiCards));
const generalQueue = computed(() => props.generalQueue || []);
const bentoCardClass = 'surface-card';
const sectionTitleClass = 'section-title';

const getKpi = (key) => kpiCards.value.find((item) => item.key === key);

const newCount = computed(() => getKpi('new')?.value ?? 0);
const pendingCount = computed(() => getKpi('pending')?.value ?? 0);
const approvedCount = computed(() => getKpi('approved')?.value ?? 0);
const totalInQueue = computed(() => generalQueue.value.length);

const completionRate = computed(() => {
    const total = Number(newCount.value) + Number(pendingCount.value);

    if (total <= 0) {
        return '0%';
    }

    return `${Math.round((Number(approvedCount.value) / total) * 100)}%`;
});

</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Command Center Pengurusan JANM</h2>
        </template>

        <div class="mx-auto max-w-7xl">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3 lg:grid-cols-4">
                <section class="col-span-1 rounded-3xl border border-indigo-100 bg-gradient-to-r from-indigo-50 to-sky-50 p-6 shadow-sm transition hover:shadow-md md:col-span-2 lg:col-span-3">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Command Center</p>
                            <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Pengurusan Operasi JANM</h3>
                            <p class="mt-2 text-sm text-slate-600">Pantau permohonan berkeutamaan, status kelulusan, dan tindakan operasi harian dalam satu paparan premium yang ringkas.</p>
                        </div>

                        <div class="grid w-full max-w-full grid-cols-3 items-stretch gap-3 md:gap-3.5 lg:w-auto lg:justify-end">
                            <div class="surface-card-soft h-full bg-white/80 text-center ring-sky-100 md:flex md:flex-col md:items-center md:justify-center md:px-4 md:py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-sky-500">Baharu</p>
                                <p class="mt-1 text-2xl font-bold tabular-nums text-sky-600">{{ newCount }}</p>
                            </div>
                            <div class="surface-card-soft h-full bg-white/80 text-center ring-indigo-100 md:flex md:flex-col md:items-center md:justify-center md:px-4 md:py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending</p>
                                <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ pendingCount }}</p>
                            </div>
                            <div class="surface-card-soft h-full bg-white/80 text-center ring-emerald-100 md:flex md:flex-col md:items-center md:justify-center md:px-4 md:py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-500">Lulus</p>
                                <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-600">{{ approvedCount }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="col-span-1 rounded-3xl border border-blue-500 bg-blue-600 p-6 text-white shadow-sm transition hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-100">Quick Access</p>
                    <h3 class="mt-2 text-xl font-bold">Tindakan Penting</h3>
                    <p class="mt-2 text-sm text-blue-100">Akses modul teras pengurusan dengan segera.</p>

                    <div class="mt-4 space-y-2">
                        <Link :href="route('admin.approvals.index')" class="block rounded-xl bg-white/15 px-3 py-2 text-sm font-semibold hover:bg-white/25">Semakan Kelulusan</Link>
                        <Link :href="route('admin.payments.index')" class="block rounded-xl bg-white/15 px-3 py-2 text-sm font-semibold hover:bg-white/25">Bayaran</Link>
                        <Link :href="route('admin.system.index')" class="block rounded-xl bg-white/15 px-3 py-2 text-sm font-semibold hover:bg-white/25">Pengurusan Sistem</Link>
                        <Link :href="`${route('info-center.index')}#poster-dashboard-section`" class="block rounded-xl bg-white/15 px-3 py-2 text-sm font-semibold hover:bg-white/25">Upload Poster Dashboard</Link>
                    </div>
                </section>

                <section :class="`${bentoCardClass} col-span-1 md:col-span-2`">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 :class="sectionTitleClass">Metrik Utama</h3>
                        <span class="text-xs font-medium text-slate-500">Live snapshot</span>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <article
                            v-for="card in kpiCards"
                            :key="card.key"
                            class="surface-card-soft"
                        >
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ card.title }}</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ card.value }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ card.trend }}</p>
                        </article>
                    </div>

                    <div v-if="$page.props.auth.user?.is_superadmin" class="mt-4 flex flex-wrap gap-2">
                        <Link :href="route('forms.manage')" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Urus Semua Borang</Link>
                        <Link :href="route('forms.builder')" class="rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500">Buka Form Builder</Link>
                    </div>
                </section>

                <section :class="`${bentoCardClass} col-span-1 md:col-span-1 lg:col-span-2`">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 :class="sectionTitleClass">Ringkasan Operasi</h3>
                        <span class="text-xs font-medium text-slate-500">Hari ini</span>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <article class="surface-card-soft">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dalam Giliran</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ totalInQueue }}</p>
                            <p class="mt-1 text-xs text-slate-500">Permohonan menunggu semakan</p>
                        </article>
                        <article class="surface-card-soft">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kadar Lulus</p>
                            <p class="mt-2 text-2xl font-bold text-emerald-700">{{ completionRate }}</p>
                            <p class="mt-1 text-xs text-slate-500">Nisbah lulus berbanding baharu + pending</p>
                        </article>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <Link :href="route('admin.approvals.index')" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Semak Kelulusan</Link>
                        <Link :href="route('admin.notifications.index')" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Buka Notifikasi</Link>
                    </div>
                </section>

                <section :class="`${bentoCardClass} col-span-1 md:col-span-3 lg:col-span-4`">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 :class="sectionTitleClass">Giliran Permohonan Menunggu Semakan</h3>
                        <Link :href="route('admin.reports.index')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Lihat laporan</Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">ID</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Nama Pemohon</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Kategori</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Tarikh Mohon</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white text-slate-600">
                                <tr v-if="!generalQueue.length">
                                    <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">Tiada permohonan dalam giliran semakan.</td>
                                </tr>
                                <tr v-for="item in generalQueue" :key="item.id" class="hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">{{ item.referenceNo || `APP-${item.id}` }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ item.applicantName }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ item.category }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ item.submittedAt }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="item.status === 'Pending' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'"
                                        >
                                            {{ item.status }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <select class="rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                            <option>Tindakan</option>
                                            <option>Semak</option>
                                            <option>Lulus</option>
                                            <option>Tolak</option>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
