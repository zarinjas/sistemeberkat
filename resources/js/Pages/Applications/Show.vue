<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    application: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Application Detail" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Application Detail</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:px-8 lg:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow-sm lg:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-900">{{ application.reference_no }}</h3>
                    <p class="mt-1 text-sm text-gray-500">Status: {{ application.status }}</p>
                    <p class="mt-2 text-sm text-gray-700">Priority: {{ application.priority_label }} ({{ application.priority_score }})</p>
                    <p v-if="application.priority_reason" class="mt-1 text-sm text-red-600">{{ application.priority_reason }}</p>

                    <h4 class="mt-5 text-sm font-semibold text-gray-900">Timeline</h4>
                    <div class="mt-2 space-y-2">
                        <div v-for="entry in application.status_histories" :key="entry.id" class="rounded border border-gray-200 p-3 text-sm">
                            <p class="font-medium text-gray-900">{{ entry.to_status }}</p>
                            <p class="text-xs text-gray-500">{{ entry.changed_at }}</p>
                            <p v-if="entry.notes" class="mt-1 text-gray-600">{{ entry.notes }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-gray-900">Linked Wallet Documents</h3>
                    <div class="mt-3 space-y-2">
                        <div v-for="doc in application.wallet_documents" :key="doc.id" class="rounded border border-gray-200 p-2 text-sm text-gray-700">
                            {{ doc.label || doc.type }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
