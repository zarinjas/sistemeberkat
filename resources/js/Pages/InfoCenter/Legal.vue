<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({
    legalContent: {
        type: Object,
        default: () => ({
            title: 'Undang-Undang & Perlembagaan BERKAT',
            draft_html: '',
            published_html: '',
            published_at: null,
            published_by: '',
        }),
    },
});

const page = usePage();
const isSuperadmin = Boolean(page.props.auth?.user?.is_superadmin);

const legalForm = useForm({
    title: props.legalContent?.title || 'Undang-Undang & Perlembagaan BERKAT',
    draft_html: props.legalContent?.draft_html || '',
});

const legalEditorRef = ref(null);

const syncLegalEditor = () => {
    if (legalEditorRef.value) {
        legalEditorRef.value.innerHTML = legalForm.draft_html || '<p></p>';
    }
};

const updateLegalDraftFromEditor = () => {
    legalForm.draft_html = legalEditorRef.value?.innerHTML || '';
};

const runEditorCommand = (command, value = null) => {
    document.execCommand(command, false, value);
    updateLegalDraftFromEditor();
};

const saveLegalDraft = () => {
    updateLegalDraftFromEditor();

    legalForm.post(route('info-center.legal.draft'), {
        preserveScroll: true,
    });
};

const publishLegalContent = () => {
    updateLegalDraftFromEditor();

    legalForm.post(route('info-center.legal.publish'), {
        preserveScroll: true,
    });
};

onMounted(() => {
    syncLegalEditor();
});
</script>

<template>
    <Head title="Pusat Info • Undang-Undang" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Pusat Info • Undang-Undang & Perlembagaan</h2>
        </template>

        <div :class="isSuperadmin ? 'page-shell px-4 sm:px-6 lg:px-8' : 'mx-auto max-w-7xl -mt-1 space-y-4 px-4 sm:px-6 lg:px-8'">
            <section v-if="isSuperadmin" class="surface-card">
                <h3 class="section-title">Undang-Undang & Perlembagaan</h3>
                <p class="section-subtitle" v-if="isSuperadmin">Superadmin boleh simpan draf dan terbitkan kandungan rich text.</p>
            </section>

            <section class="surface-card">
                <div v-if="isSuperadmin" class="space-y-4">
                    <input
                        v-model="legalForm.title"
                        type="text"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Tajuk dokumen"
                    >

                    <div class="rounded-2xl border border-slate-200 bg-white">
                        <div class="flex flex-wrap gap-2 border-b border-slate-200 p-3">
                            <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="runEditorCommand('bold')">Bold</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="runEditorCommand('italic')">Italic</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="runEditorCommand('insertUnorderedList')">Bullet</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="runEditorCommand('insertOrderedList')">Numbered</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="runEditorCommand('formatBlock', '<h2>')">H2</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="runEditorCommand('formatBlock', '<p>')">Paragraph</button>
                        </div>

                        <div
                            ref="legalEditorRef"
                            contenteditable="true"
                            class="min-h-[280px] p-4 text-sm text-slate-700 focus:outline-none"
                            @input="updateLegalDraftFromEditor"
                        ></div>
                    </div>

                    <p v-if="legalForm.errors.title || legalForm.errors.draft_html" class="text-sm text-rose-600">
                        {{ legalForm.errors.title || legalForm.errors.draft_html }}
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            :disabled="legalForm.processing"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-60"
                            @click="saveLegalDraft"
                        >
                            Simpan Draf
                        </button>
                        <button
                            type="button"
                            :disabled="legalForm.processing"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
                            @click="publishLegalContent"
                        >
                            Terbitkan
                        </button>
                    </div>
                </div>

                <div v-else-if="legalContent.published_html" class="rounded-2xl border border-slate-200 bg-white p-5">
                    <h4 class="text-base font-semibold text-slate-900">{{ legalContent.title || 'Undang-Undang & Perlembagaan BERKAT' }}</h4>
                    <div class="prose prose-sm mt-4 max-w-none text-slate-700" v-html="legalContent.published_html" />
                    <p v-if="legalContent.published_at" class="mt-4 text-xs text-slate-500">
                        Dikemaskini: {{ legalContent.published_at }}
                        <span v-if="legalContent.published_by">oleh {{ legalContent.published_by }}</span>
                    </p>
                </div>

                <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
                    Kandungan Undang-Undang & Perlembagaan belum diterbitkan lagi.
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
