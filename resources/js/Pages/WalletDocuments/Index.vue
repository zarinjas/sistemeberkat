<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    documents: {
        type: Array,
        default: () => [],
    },
    types: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    type: 'nric',
    label: '',
    document: null,
});

const submit = () => {
    form.post(route('wallet-documents.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset('label', 'document');
        },
    });
};
</script>

<template>
    <Head title="Document Wallet" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Document Wallet</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:px-8 lg:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow-sm lg:col-span-1">
                    <h3 class="text-lg font-semibold text-gray-900">Upload Document</h3>
                    <form class="mt-4 space-y-3" @submit.prevent="submit">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Type</label>
                            <select v-model="form.type" class="w-full rounded border-gray-300 text-sm">
                                <option v-for="type in types" :key="type" :value="type">{{ type }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Label</label>
                            <input v-model="form.label" type="text" class="w-full rounded border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">File</label>
                            <input type="file" class="w-full rounded border-gray-300 text-sm" @input="form.document = $event.target.files[0]" />
                        </div>
                        <PrimaryButton :disabled="form.processing">Upload</PrimaryButton>
                    </form>
                </div>

                <div class="rounded-lg bg-white p-5 shadow-sm lg:col-span-2">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">My Documents</h3>
                        <Link :href="route('applications.create')" class="text-sm text-indigo-600 hover:text-indigo-800">Use in application</Link>
                    </div>
                    <div v-if="!documents.length" class="text-sm text-gray-500">No wallet documents uploaded.</div>
                    <div v-else class="space-y-3">
                        <div v-for="doc in documents" :key="doc.id" class="flex items-center justify-between rounded border border-gray-200 p-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ doc.label || doc.type }}</p>
                                <p class="text-xs text-gray-500">{{ doc.type }} • {{ doc.mime_type || 'file' }}</p>
                            </div>
                            <Link
                                as="button"
                                method="delete"
                                :href="route('wallet-documents.destroy', doc.id)"
                                class="text-xs font-medium text-red-600 hover:text-red-800"
                            >
                                Remove
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
