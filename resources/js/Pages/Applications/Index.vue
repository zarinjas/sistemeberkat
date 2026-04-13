<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    applications: {
        type: Array,
        default: () => [],
    },
    drafts: {
        type: Array,
        default: () => [],
    },
    availableForms: {
        type: Array,
        default: () => [],
    },
});

const categoryThemeMap = {
    pendidikan: 'bg-gradient-to-br from-white to-blue-50 border border-blue-100 hover:border-blue-300 ring-1 ring-black/5 hover:ring-blue-500/20 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300',
    kesihatan: 'bg-gradient-to-br from-white to-rose-50 border border-rose-100 hover:border-rose-300 ring-1 ring-black/5 hover:ring-rose-500/20 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300',
    kebajikan: 'bg-gradient-to-br from-white to-emerald-50 border border-emerald-100 hover:border-emerald-300 ring-1 ring-black/5 hover:ring-emerald-500/20 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300',
    kecemasan: 'bg-gradient-to-br from-white to-amber-50 border border-amber-100 hover:border-amber-300 ring-1 ring-black/5 hover:ring-amber-500/20 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300',
    bantuan_khas: 'bg-gradient-to-br from-white to-violet-50 border border-violet-100 hover:border-violet-300 ring-1 ring-black/5 hover:ring-violet-500/20 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300',
};

const fallbackThemes = [
    'bg-gradient-to-br from-white to-cyan-50 border border-cyan-100 hover:border-cyan-300 ring-1 ring-black/5 hover:ring-cyan-500/20 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300',
    'bg-gradient-to-br from-white to-indigo-50 border border-indigo-100 hover:border-indigo-300 ring-1 ring-black/5 hover:ring-indigo-500/20 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300',
    'bg-gradient-to-br from-white to-fuchsia-50 border border-fuchsia-100 hover:border-fuchsia-300 ring-1 ring-black/5 hover:ring-fuchsia-500/20 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300',
    'bg-gradient-to-br from-white to-lime-50 border border-lime-100 hover:border-lime-300 ring-1 ring-black/5 hover:ring-lime-500/20 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300',
];

const resolveThemeClass = (categoryKey) => {
    const key = String(categoryKey || '').toLowerCase();

    if (categoryThemeMap[key]) {
        return categoryThemeMap[key];
    }

    // Stable fallback for unknown categories.
    let hash = 0;
    for (let index = 0; index < key.length; index += 1) {
        hash = ((hash << 5) - hash) + key.charCodeAt(index);
        hash |= 0;
    }

    const pick = Math.abs(hash) % fallbackThemes.length;
    return fallbackThemes[pick];
};

const formCards = computed(() => (props.availableForms || []).map((form) => {
    let themeClass = resolveThemeClass(form.category_key);
    let style = {};
    if (form.card_color && /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(form.card_color)) {
        style = { 
            borderLeftColor: form.card_color, 
            borderLeftWidth: '6px',
            background: `linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, ${form.card_color}18 100%)`,
            borderColor: `${form.card_color}33`,
        };
        themeClass = 'ring-1 ring-black/5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300';
    }
    return {
        ...form,
        themeClass,
        cardStyle: style,
    };
}));

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
    <Head title="Permohonan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Permohonan</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Pilih Borang Permohonan</h3>
                    </div>

                    <div v-if="!formCards.length" class="mb-8 rounded border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-500">
                        Tiada borang aktif buat masa ini.
                    </div>

                    <div v-else class="mb-8 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="form in formCards"
                            :key="`form-${form.id}`"
                            class="relative flex flex-col justify-between overflow-hidden rounded-2xl p-5"
                            :class="form.themeClass"
                            :style="form.cardStyle"
                        >
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white p-2 shadow-sm ring-1 ring-slate-900/5">
                                    <img v-if="$page.props.branding?.logo_url" :src="$page.props.branding.logo_url" alt="Logo" class="h-full w-full object-contain" />
                                    <ApplicationLogo v-else class="h-6 w-6 text-slate-700" />
                                </div>
                                <div class="flex-1 pt-1">
                                    <h4 class="text-base font-bold leading-tight text-slate-900 pr-6">{{ form.title }}</h4>
                                    <p class="mt-1 text-xs font-medium text-slate-600 whitespace-pre-line">{{ form.description || 'Borang permohonan bantuan' }}</p>
                                </div>
                            </div>

                            <div class="mt-6 flex items-center justify-between border-t border-slate-900/5 pt-4">
                                <Link
                                    v-if="form.has_draft"
                                    :href="route('applications.create', { draft_id: form.draft_id, form_id: form.id })"
                                    class="inline-flex w-full items-center justify-center rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500/50"
                                >
                                    Sambung Draf
                                </Link>
                                <Link
                                    v-else
                                    :href="route('applications.create', { form_id: form.id })"
                                    class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600/50"
                                >
                                    Isi Borang
                                </Link>
                            </div>

                            <div v-if="form.has_draft" class="absolute right-3 top-3 flex items-center gap-1.5 rounded-full bg-amber-100 px-2 py-1 shadow-sm ring-1 ring-amber-500/20">
                                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"></span>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Draf</span>
                            </div>
                        </article>
                    </div>

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
