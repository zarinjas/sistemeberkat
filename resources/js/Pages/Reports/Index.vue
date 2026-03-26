<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { Bar, Doughnut } from 'vue-chartjs';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Title,
    Tooltip,
} from 'chart.js';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    ArcElement,
    CategoryScale,
    LinearScale,
    BarElement,
);

const props = defineProps({
    categoryTotals: {
        type: Array,
        default: () => [],
    },
    branchVolume: {
        type: Array,
        default: () => [],
    },
    totalApplications: {
        type: Number,
        default: 0,
    },
    filters: {
        type: Object,
        default: () => ({
            branch: '',
            category: '',
            fromDate: '',
            toDate: '',
        }),
    },
    options: {
        type: Object,
        default: () => ({
            branches: [],
            categories: [],
        }),
    },
});

const report = reactive({
    categoryTotals: props.categoryTotals,
    branchVolume: props.branchVolume,
    totalApplications: props.totalApplications,
});

const filters = reactive({
    branch: props.filters.branch || '',
    category: props.filters.category || '',
    fromDate: props.filters.fromDate || '',
    toDate: props.filters.toDate || '',
});

const loading = ref(false);
const errorMessage = ref('');
const lastUpdated = ref(new Date());
let pollingTimer = null;

const queryParams = computed(() => ({
    ...(filters.branch ? { branch: filters.branch } : {}),
    ...(filters.category ? { category: filters.category } : {}),
    ...(filters.fromDate ? { from_date: filters.fromDate } : {}),
    ...(filters.toDate ? { to_date: filters.toDate } : {}),
}));

const categoryChartData = computed(() => ({
    labels: report.categoryTotals.map((row) => row.category),
    datasets: [
        {
            label: 'Fund Distribution (RM)',
            data: report.categoryTotals.map((row) => row.amount),
        },
    ],
}));

const branchChartData = computed(() => ({
    labels: report.branchVolume.map((row) => row.branch),
    datasets: [
        {
            label: 'Applications',
            data: report.branchVolume.map((row) => row.count),
            borderRadius: 6,
        },
    ],
}));

const refreshData = async () => {
    loading.value = true;
    errorMessage.value = '';

    try {
        const { data } = await window.axios.get(route('admin.reports.data'), {
            params: queryParams.value,
        });

        report.categoryTotals = data.categoryTotals;
        report.branchVolume = data.branchVolume;
        report.totalApplications = data.totalApplications;
        lastUpdated.value = new Date();
    } catch {
        errorMessage.value = 'Unable to refresh reporting data at the moment.';
    } finally {
        loading.value = false;
    }
};

const applyFilters = async () => {
    await refreshData();
};

const resetFilters = async () => {
    filters.branch = '';
    filters.category = '';
    filters.fromDate = '';
    filters.toDate = '';
    await refreshData();
};

onMounted(() => {
    pollingTimer = setInterval(() => {
        refreshData();
    }, 30000);
});

onBeforeUnmount(() => {
    if (pollingTimer) {
        clearInterval(pollingTimer);
    }
});
</script>

<template>
    <Head title="Reporting" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Real-Time Reporting (Polling 30s)</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:px-8 lg:grid-cols-2">
                <div class="rounded-lg bg-white p-5 shadow-sm lg:col-span-2">
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Branch</label>
                            <select v-model="filters.branch" class="rounded border-gray-300 text-sm">
                                <option value="">All branches</option>
                                <option v-for="branch in options.branches" :key="branch" :value="branch">{{ branch }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Category</label>
                            <select v-model="filters.category" class="rounded border-gray-300 text-sm">
                                <option value="">All categories</option>
                                <option v-for="category in options.categories" :key="category" :value="category">{{ category }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">From</label>
                            <input v-model="filters.fromDate" type="date" class="rounded border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">To</label>
                            <input v-model="filters.toDate" type="date" class="rounded border-gray-300 text-sm" />
                        </div>
                        <button type="button" class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500" @click="applyFilters">
                            Apply
                        </button>
                        <button type="button" class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="resetFilters">
                            Reset
                        </button>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Applications</p>
                            <p class="text-3xl font-semibold text-gray-900">{{ report.totalApplications }}</p>
                        </div>
                        <div class="text-right text-xs text-gray-500">
                            <p>Auto-refresh: every 30 seconds</p>
                            <p>Last updated: {{ lastUpdated.toLocaleTimeString() }}</p>
                        </div>
                    </div>

                    <p v-if="loading" class="mt-2 text-xs text-indigo-600">Refreshing data...</p>
                    <p v-if="errorMessage" class="mt-2 text-xs text-red-600">{{ errorMessage }}</p>
                </div>

                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Fund Distribution by Category</h3>
                    <div class="mt-4">
                        <Doughnut :data="categoryChartData" :options="{ responsive: true, maintainAspectRatio: false }" class="h-64" />
                    </div>
                    <div class="mt-4 space-y-2">
                        <div v-for="row in report.categoryTotals" :key="row.category" class="flex items-center justify-between rounded border border-gray-200 px-3 py-2 text-sm">
                            <span class="text-gray-700">{{ row.category }}</span>
                            <span class="font-semibold text-gray-900">RM {{ row.amount }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900">Application Volume by Branch</h3>
                    <div class="mt-4">
                        <Bar
                            :data="branchChartData"
                            :options="{ responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }"
                            class="h-64"
                        />
                    </div>
                    <div class="mt-4 space-y-2">
                        <div v-for="row in report.branchVolume" :key="row.branch" class="flex items-center justify-between rounded border border-gray-200 px-3 py-2 text-sm">
                            <span class="text-gray-700">{{ row.branch }}</span>
                            <span class="font-semibold text-gray-900">{{ row.count }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
