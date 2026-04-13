<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    guideline: {
        type: Object,
        default: () => ({
            title: 'Garis Panduan',
            html: '',
            is_published: false,
            published_at: null,
            updated_at: null,
        }),
    },
});
</script>

<template>
    <Head :title="`Garis Panduan • ${guideline.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Garis Panduan • {{ guideline.title }}</h2>
        </template>

        <div class="mx-auto max-w-6xl space-y-4 px-4 sm:px-6 lg:px-8">
            <section class="surface-card">
                <div class="mb-4 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 font-semibold text-blue-700">{{ guideline.is_published ? 'Published' : 'Draft Preview' }}</span>
                    <span v-if="guideline.published_at">Terbit: {{ guideline.published_at }}</span>
                    <span v-if="guideline.updated_at">Kemaskini: {{ guideline.updated_at }}</span>
                </div>

                <div
                    v-if="guideline.html"
                    class="prose prose-sm max-w-none overflow-x-auto text-slate-700 lg:prose-base"
                    v-html="guideline.html"
                />

                <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
                    Kandungan garis panduan ini belum tersedia.
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
