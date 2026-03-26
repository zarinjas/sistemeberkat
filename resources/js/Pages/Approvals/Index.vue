<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

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

const onStatusFilter = (event) => {
    const params = { status: event.target.value };
    if (props.filters.category) {
        params.category = props.filters.category;
    }
    router.get(route('admin.approvals.index'), params, { preserveState: true, replace: true });
};

const onCategoryFilter = (event) => {
    const params = { category: event.target.value };
    if (props.filters.status) {
        params.status = props.filters.status;
    }
    router.get(route('admin.approvals.index'), params, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Approval Queue" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Approval Queue</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Applications</h3>
                        <div class="flex flex-wrap gap-3">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-600">Kategori Bantuan</label>
                                <select class="rounded border-gray-300 text-sm" :value="filters.category || ''" @change="onCategoryFilter">
                                    <option value="">Semua kategori</option>
                                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-600">Status</label>
                                <select class="rounded border-gray-300 text-sm" :value="filters.status || ''" @change="onStatusFilter">
                                    <option value="">Semua status</option>
                                    <option value="submitted">submitted</option>
                                    <option value="under_review">under_review</option>
                                    <option value="approved">approved</option>
                                    <option value="disbursed">disbursed</option>
                                    <option value="rejected">rejected</option>
                                </select>
                            </div>
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
                                <div class="flex flex-col items-end gap-1">
                                    <span class="rounded-full bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700">{{ item.status }}</span>
                                    <span v-if="item.category_tags?.length" class="rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">{{ item.category_tags[0] }}</span>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Prioriti: {{ item.priority_label || 'Standard' }} ({{ item.priority_score }})</p>
                        </Link>

                        <div v-if="!applications.data.length" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                            Tiada permohonan yang mematuhi kriteria penapis.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
