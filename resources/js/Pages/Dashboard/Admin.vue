<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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

const page = usePage();

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
const activeQueueFilter = ref('all');
const bentoCardClass = 'surface-card';
const sectionTitleClass = 'section-title';
const normalizeStatus = (status) => String(status || '').toLowerCase().replace(/\s+/g, '_');
const isSuperadmin = computed(() => Boolean(page.props.auth?.user?.is_superadmin));

const getKpi = (key) => kpiCards.value.find((item) => item.key === key);

const newCount = computed(() => getKpi('new')?.value ?? 0);
const pendingCount = computed(() => getKpi('pending')?.value ?? 0);
const approvedCount = computed(() => getKpi('approved')?.value ?? 0);
const totalInQueue = computed(() => generalQueue.value.length);
const pendingQueueCount = computed(() => generalQueue.value.filter((item) => normalizeStatus(item.status) === 'pending_approval').length);
const approvedQueueCount = computed(() => generalQueue.value.filter((item) => normalizeStatus(item.status) === 'approved').length);

const completionRate = computed(() => {
    const total = Number(newCount.value) + Number(pendingCount.value);

    if (total <= 0) {
        return '0%';
    }

    return `${Math.round((Number(approvedCount.value) / total) * 100)}%`;
});

const approvalRateValue = computed(() => {
    const total = Number(newCount.value) + Number(pendingCount.value);

    if (total <= 0) {
        return 0;
    }

    return Math.max(0, Math.min(100, Math.round((Number(approvedCount.value) / total) * 100)));
});

const queuePressure = computed(() => {
    if (totalInQueue.value >= 15) {
        return {
            label: 'Tinggi',
            className: 'bg-rose-100 text-rose-700 ring-rose-200',
        };
    }

    if (totalInQueue.value >= 8) {
        return {
            label: 'Sederhana',
            className: 'bg-amber-100 text-amber-700 ring-amber-200',
        };
    }

    return {
        label: 'Terkawal',
        className: 'bg-emerald-100 text-emerald-700 ring-emerald-200',
    };
});

const firstName = computed(() => {
    const fullName = String(page.props.auth?.user?.name || '').trim();

    if (!fullName) {
        return 'Pegawai';
    }

    return fullName.split(/\s+/)[0];
});

const greetingLabel = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Selamat pagi';
    }

    if (hour < 18) {
        return 'Selamat petang';
    }

    return 'Selamat malam';
});

const trendValue = (trend) => {
    const match = String(trend || '').match(/[-+]?\d+/);
    return match ? Number(match[0]) : 0;
};

const trendClass = (trend) => {
    const value = trendValue(trend);

    if (value > 0) {
        return 'text-emerald-700 bg-emerald-50 ring-emerald-200';
    }

    if (value < 0) {
        return 'text-rose-700 bg-rose-50 ring-rose-200';
    }

    return 'text-slate-600 bg-slate-100 ring-slate-200';
};

const kpiAccentClass = (key) => {
    if (key === 'new') {
        return 'from-sky-500 to-indigo-500';
    }

    if (key === 'pending') {
        return 'from-amber-400 to-orange-500';
    }

    if (key === 'approved') {
        return 'from-emerald-500 to-teal-500';
    }

    return 'from-slate-500 to-slate-600';
};

const queuePriority = (item, index) => {
    const status = normalizeStatus(item.status);

    if (status === 'pending_approval' && index <= 2) {
        return {
            label: 'Kritikal',
            badgeClass: 'bg-rose-100 text-rose-700 ring-rose-200',
            rowClass: 'bg-rose-50/30',
            stripClass: 'bg-rose-500',
            pulse: true,
        };
    }

    if (status === 'pending_approval') {
        return {
            label: 'Tinggi',
            badgeClass: 'bg-amber-100 text-amber-700 ring-amber-200',
            rowClass: 'bg-amber-50/30',
            stripClass: 'bg-amber-500',
            pulse: false,
        };
    }

    if (status === 'approved') {
        return {
            label: 'Bayaran',
            badgeClass: 'bg-sky-100 text-sky-700 ring-sky-200',
            rowClass: 'bg-sky-50/20',
            stripClass: 'bg-sky-500',
            pulse: false,
        };
    }

    return {
        label: 'Normal',
        badgeClass: 'bg-slate-100 text-slate-700 ring-slate-200',
        rowClass: '',
        stripClass: 'bg-slate-300',
        pulse: false,
    };
};

const queueItems = computed(() => generalQueue.value.map((item, index) => ({
    ...item,
    sourceIndex: index,
    priority: queuePriority(item, index),
})));

const criticalQueueCount = computed(() => queueItems.value.filter((item) => item.priority.label === 'Kritikal').length);

const filteredQueue = computed(() => {
    if (activeQueueFilter.value === 'critical') {
        return queueItems.value.filter((item) => item.priority.label === 'Kritikal');
    }

    if (activeQueueFilter.value === 'pending') {
        return queueItems.value.filter((item) => normalizeStatus(item.status) === 'pending_approval');
    }

    if (activeQueueFilter.value === 'payment') {
        return queueItems.value.filter((item) => normalizeStatus(item.status) === 'approved');
    }

    return queueItems.value;
});

const queueFilterButtonClass = (value) => {
    if (activeQueueFilter.value === value) {
        return 'bg-indigo-600 text-white ring-indigo-600';
    }

    return 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50';
};

const activeFilterLabel = computed(() => {
    if (activeQueueFilter.value === 'critical') {
        return 'Kritikal';
    }

    if (activeQueueFilter.value === 'pending') {
        return 'Pending Approval';
    }

    if (activeQueueFilter.value === 'payment') {
        return 'Menunggu Bayaran';
    }

    return 'Semua';
});

const statusBadgeClass = (status) => {
    const normalized = normalizeStatus(status);

    if (normalized === 'pending_approval') {
        return 'bg-amber-100 text-amber-700';
    }

    if (normalized === 'approved') {
        return 'bg-emerald-100 text-emerald-700';
    }

    return 'bg-slate-100 text-slate-700';
};

const statusLabel = (status) => {
    const normalized = normalizeStatus(status);

    if (normalized === 'pending_approval') {
        return 'Pending Approval';
    }

    if (normalized === 'approved') {
        return 'Approved';
    }

    return status || '-';
};

const approveApplication = (applicationId) => {
    router.patch(route('admin.approvals.status', applicationId), {
        status: 'approved',
    }, {
        preserveScroll: true,
    });
};

const recordTransaction = (applicationId) => {
    router.visit(route('admin.payments.show', applicationId));
};

</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Command Center Pengurusan Sistem E-Berkat</h2>
        </template>

        <div class="mx-auto max-w-7xl">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3 lg:grid-cols-4">
                <section class="relative isolate col-span-1 overflow-hidden rounded-3xl border border-indigo-100 bg-gradient-to-r from-sky-50 via-indigo-50 to-cyan-50 p-6 shadow-sm transition hover:shadow-md motion-safe:animate-[fadeIn_.5s_ease-out] md:col-span-2 lg:col-span-3">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-sky-200/35 blur-2xl" />
                    <div class="pointer-events-none absolute -bottom-10 left-1/3 h-32 w-32 rounded-full bg-indigo-200/35 blur-2xl" />

                    <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-indigo-600">{{ greetingLabel }}, {{ firstName }}.</p>
                            <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Pengurusan Operasi E-Berkat</h3>
                            <p class="mt-2 max-w-2xl text-sm text-slate-600">Pantau permohonan berkeutamaan, status kelulusan, dan tindakan operasi harian dalam satu paparan premium yang ringkas.</p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <Link :href="route('admin.approvals.index')" class="inline-flex items-center rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-indigo-500">Semak Kelulusan</Link>
                                <Link :href="route('admin.notifications.index')" class="inline-flex items-center rounded-xl border border-slate-300 bg-white/80 px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:bg-white">Pusat Notifikasi</Link>
                            </div>
                        </div>

                        <div class="grid w-full max-w-full grid-cols-3 items-stretch gap-3 md:gap-3.5 lg:w-auto lg:justify-end">
                            <div class="surface-card-soft h-full bg-white/85 text-center ring-sky-100 transition hover:-translate-y-0.5 md:flex md:flex-col md:items-center md:justify-center md:px-4 md:py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-sky-500">Baharu</p>
                                <p class="mt-1 text-2xl font-bold tabular-nums text-sky-600">{{ newCount }}</p>
                            </div>
                            <div class="surface-card-soft h-full bg-white/85 text-center ring-indigo-100 transition hover:-translate-y-0.5 md:flex md:flex-col md:items-center md:justify-center md:px-4 md:py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending</p>
                                <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ pendingCount }}</p>
                            </div>
                            <div class="surface-card-soft h-full bg-white/85 text-center ring-emerald-100 transition hover:-translate-y-0.5 md:flex md:flex-col md:items-center md:justify-center md:px-4 md:py-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-500">Lulus</p>
                                <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-600">{{ approvedCount }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="col-span-1 rounded-3xl border border-blue-500 bg-blue-600 p-6 text-white shadow-sm transition hover:shadow-md motion-safe:animate-[fadeIn_.6s_ease-out]">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-100">Quick Access</p>
                    <h3 class="mt-2 text-xl font-bold">Tindakan Penting</h3>
                    <p class="mt-2 text-sm text-blue-100">Akses modul teras pengurusan dengan segera.</p>

                    <div class="mt-4 space-y-2">
                        <Link :href="route('admin.approvals.index')" class="block rounded-xl bg-white/15 px-3 py-2 text-sm font-semibold transition hover:-translate-y-0.5 hover:bg-white/25">Semakan Kelulusan</Link>
                        <Link :href="route('admin.payments.index')" class="block rounded-xl bg-white/15 px-3 py-2 text-sm font-semibold transition hover:-translate-y-0.5 hover:bg-white/25">Bayaran</Link>
                        <Link :href="route('admin.system.index')" class="block rounded-xl bg-white/15 px-3 py-2 text-sm font-semibold transition hover:-translate-y-0.5 hover:bg-white/25">Pengurusan Ahli BERKAT</Link>
                        <Link :href="route('info-center.infographics')" class="block rounded-xl bg-white/15 px-3 py-2 text-sm font-semibold transition hover:-translate-y-0.5 hover:bg-white/25">Upload Poster Dashboard</Link>
                    </div>
                </section>

                <section :class="`${bentoCardClass} col-span-1 md:col-span-2 motion-safe:animate-[fadeIn_.65s_ease-out]`">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 :class="sectionTitleClass">Metrik Utama</h3>
                        <span class="text-xs font-medium text-slate-500">Live snapshot</span>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <article
                            v-for="card in kpiCards"
                            :key="card.key"
                            class="surface-card-soft group relative overflow-hidden"
                        >
                            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r" :class="kpiAccentClass(card.key)" />
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ card.title }}</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ card.value }}</p>

                            <div class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1" :class="trendClass(card.trend)">
                                {{ card.trend }}
                            </div>
                        </article>
                    </div>

                    <div v-if="isSuperadmin" class="mt-4 flex flex-wrap gap-2">
                        <Link :href="route('forms.manage')" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Urus Semua Borang</Link>
                        <Link :href="route('forms.builder')" class="rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500">Buka Form Builder</Link>
                    </div>
                </section>

                <section :class="`${bentoCardClass} col-span-1 md:col-span-1 lg:col-span-2 motion-safe:animate-[fadeIn_.7s_ease-out]`">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 :class="sectionTitleClass">Ringkasan Operasi</h3>
                        <span class="text-xs font-medium text-slate-500">Hari ini</span>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <article class="surface-card-soft">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dalam Giliran</p>
                                    <p class="mt-2 text-2xl font-bold text-slate-900">{{ totalInQueue }}</p>
                                </div>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1" :class="queuePressure.className">
                                    {{ queuePressure.label }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Permohonan menunggu semakan</p>
                        </article>
                        <article class="surface-card-soft">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kadar Lulus</p>
                            <p class="mt-2 text-2xl font-bold text-emerald-700">{{ completionRate }}</p>
                            <div class="mt-2 h-2 rounded-full bg-slate-200">
                                <div class="h-2 rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500" :style="{ width: `${approvalRateValue}%` }" />
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Nisbah lulus berbanding baharu + pending</p>
                        </article>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <Link :href="route('admin.approvals.index')" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Semak Kelulusan</Link>
                        <Link :href="route('admin.notifications.index')" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Buka Notifikasi</Link>
                    </div>
                </section>

                <section :class="`${bentoCardClass} col-span-1 md:col-span-3 lg:col-span-4 motion-safe:animate-[fadeIn_.75s_ease-out]`">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 :class="sectionTitleClass">Giliran Permohonan Menunggu Semakan</h3>
                        <Link :href="route('admin.reports.index')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Lihat laporan</Link>
                    </div>

                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800 ring-1 ring-rose-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500" />
                            Kritikal {{ criticalQueueCount }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500" />
                            Pending {{ pendingQueueCount }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800 ring-1 ring-sky-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-sky-500" />
                            Menunggu Bayaran {{ approvedQueueCount }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400" />
                            Jumlah {{ totalInQueue }}
                        </span>
                    </div>

                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        <button type="button" class="inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold ring-1 transition" :class="queueFilterButtonClass('all')" @click="activeQueueFilter = 'all'">Semua</button>
                        <button type="button" class="inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold ring-1 transition" :class="queueFilterButtonClass('critical')" @click="activeQueueFilter = 'critical'">Kritikal</button>
                        <button type="button" class="inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold ring-1 transition" :class="queueFilterButtonClass('pending')" @click="activeQueueFilter = 'pending'">Pending</button>
                        <button type="button" class="inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold ring-1 transition" :class="queueFilterButtonClass('payment')" @click="activeQueueFilter = 'payment'">Menunggu Bayaran</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Applicant Name</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Aid Type</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status Badge</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Keutamaan</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white text-slate-600">
                                <tr v-if="!filteredQueue.length">
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">Tiada permohonan untuk tapisan {{ activeFilterLabel }}.</td>
                                </tr>
                                <tr v-for="item in filteredQueue" :key="item.id" class="transition duration-200 hover:bg-slate-50" :class="item.priority.rowClass">
                                    <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">
                                        <span class="mr-2 inline-block h-2.5 w-2.5 rounded-full" :class="[item.priority.stripClass, item.priority.pulse ? 'animate-pulse' : '']" />
                                        {{ item.applicantName || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ item.category || '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="statusBadgeClass(item.status)"
                                        >
                                            {{ statusLabel(item.status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1" :class="item.priority.badgeClass">
                                            {{ item.priority.label }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <button
                                            v-if="normalizeStatus(item.status) === 'pending_approval'"
                                            type="button"
                                            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:-translate-y-0.5 hover:bg-emerald-500"
                                            @click="approveApplication(item.id)"
                                        >
                                            Approve
                                        </button>
                                        <button
                                            v-else-if="normalizeStatus(item.status) === 'approved' && isSuperadmin"
                                            type="button"
                                            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:-translate-y-0.5 hover:bg-indigo-500"
                                            @click="recordTransaction(item.id)"
                                        >
                                            Record Transaction
                                        </button>
                                        <span v-else-if="normalizeStatus(item.status) === 'approved'" class="text-xs font-medium text-slate-500">Superadmin sahaja</span>
                                        <span v-else class="text-xs text-slate-400">-</span>
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
