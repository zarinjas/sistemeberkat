<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    applications: {
        type: Array,
        default: () => [],
    },
    drafts: {
        type: Array,
        default: () => [],
    },
});

const deleteDraft = (draftId) => {
    if (!confirm('Padam draf ini? Tindakan ini tidak boleh dibatalkan.')) {
        return;
    }

    router.delete(route('applications.destroy-draft', draftId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Applications" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Applications</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">My Drafts</h3>
                        <p class="mt-1 text-sm text-gray-500">Sambung permohonan yang disimpan sebelum ini.</p>
                    </div>

                    <div v-if="!drafts.length" class="mb-8 rounded border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-500">
                        Tiada draf disimpan.
                    </div>
                    <div v-else class="mb-8 space-y-3">
                        <div
                            v-for="item in drafts"
                            :key="`draft-${item.id}`"
                            class="rounded border border-amber-200 bg-amber-50 p-4"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ item.reference_no || `Draft #${item.id}` }}</p>
                                    <p class="mt-1 text-xs text-gray-600">Terakhir dikemaskini: {{ item.updated_at }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Link
                                        :href="route('applications.create', { draft_id: item.id, form_id: item.dynamic_payload?._form_id })"
                                        class="inline-flex items-center rounded bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-500"
                                    >
                                        Sambung Draf
                                    </Link>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded border border-rose-300 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                                        @click="deleteDraft(item.id)"
                                    >
                                        Padam Draf
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">My Submissions</h3>
                        <Link :href="route('applications.create')" class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">
                            New Application
                        </Link>
                    </div>

                    <div v-if="!applications.length" class="text-sm text-gray-500">No applications submitted yet.</div>
                    <div v-else class="space-y-3">
                        <Link
                            v-for="item in applications"
                            :key="item.id"
                            :href="route('applications.show', item.id)"
                            class="block rounded border border-gray-200 p-4 hover:bg-gray-50"
                        >
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-gray-900">{{ item.reference_no || `Application #${item.id}` }}</p>
                                <span class="rounded-full bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700">{{ item.status }}</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Priority: {{ item.priority_label }} ({{ item.priority_score }})</p>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
