<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    pages: {
        type: Array,
        default: () => [],
    },
});

const selectedId = ref(props.pages[0]?.id ?? null);
const editorRef = ref(null);
const showPreviewModal = ref(false);
const draggingPageId = ref(null);
const localPages = ref([]);

const createForm = useForm({
    title: '',
    draft_html: '<p></p>',
});

const editForm = useForm({
    title: '',
    draft_html: '<p></p>',
});

watch(() => props.pages, (newPages) => {
    localPages.value = [...(newPages || [])];

    if (localPages.value.length && !localPages.value.find((item) => item.id === selectedId.value)) {
        selectedId.value = localPages.value[0].id;
    }

    if (!localPages.value.length) {
        selectedId.value = null;
    }
}, { immediate: true });

const pages = computed(() => localPages.value || []);
const selectedPage = computed(() => pages.value.find((item) => item.id === selectedId.value) || null);
const strippedDraftText = computed(() => {
    const rawHtml = String(editForm.draft_html || '');

    return rawHtml
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/gi, ' ')
        .replace(/\s+/g, ' ')
        .trim();
});

const canPublish = computed(() => {
    return String(editForm.title || '').trim().length > 0 && strippedDraftText.value.length > 0;
});

const hasUnsavedChanges = computed(() => {
    if (!selectedPage.value) {
        return false;
    }

    const normalize = (value) => String(value || '')
        .replace(/\s+/g, ' ')
        .trim();

    return normalize(editForm.title) !== normalize(selectedPage.value.title)
        || normalize(editForm.draft_html) !== normalize(selectedPage.value.draft_html);
});

const syncEditorFromSelected = () => {
    if (!selectedPage.value) {
        return;
    }

    editForm.title = selectedPage.value.title;
    editForm.draft_html = selectedPage.value.draft_html || '<p></p>';

    if (editorRef.value) {
        editorRef.value.innerHTML = editForm.draft_html;
    }
};

const selectPage = (pageId) => {
    if (hasUnsavedChanges.value && !window.confirm('Ada perubahan belum disimpan. Teruskan tukar halaman?')) {
        return;
    }

    selectedId.value = pageId;
    syncEditorFromSelected();
};

const updateDraftFromEditor = () => {
    editForm.draft_html = editorRef.value?.innerHTML || '';
};

const runEditorCommand = (command, value = null) => {
    document.execCommand(command, false, value);
    updateDraftFromEditor();
};

const insertTableTemplate = () => {
    const template = '<table border="1" style="width:100%; border-collapse:collapse;"><thead><tr><th>Tajuk</th><th>Perincian</th></tr></thead><tbody><tr><td>Item 1</td><td>Maklumat</td></tr></tbody></table><p><br></p>';

    runEditorCommand('insertHTML', template);
};

const insertLink = () => {
    const link = window.prompt('Masukkan URL pautan:');

    if (!link) {
        return;
    }

    runEditorCommand('createLink', link);
};

const createPage = () => {
    createForm.post(route('guidelines.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
        },
    });
};

const saveDraft = () => {
    if (!selectedPage.value) {
        return;
    }

    updateDraftFromEditor();

    editForm.patch(route('guidelines.update', selectedPage.value.id), {
        preserveScroll: true,
    });
};

const publishPage = () => {
    if (!selectedPage.value || !canPublish.value) {
        return;
    }

    updateDraftFromEditor();

    router.post(route('guidelines.publish', selectedPage.value.id), {
        title: editForm.title,
        draft_html: editForm.draft_html,
    }, {
        preserveScroll: true,
    });
};

const previewDraft = () => {
    updateDraftFromEditor();
    showPreviewModal.value = true;
};

const closePreview = () => {
    showPreviewModal.value = false;
};

const handleBeforeUnload = (event) => {
    if (!hasUnsavedChanges.value) {
        return;
    }

    event.preventDefault();
    event.returnValue = '';
};

const unpublishPage = () => {
    if (!selectedPage.value) {
        return;
    }

    router.post(route('guidelines.unpublish', selectedPage.value.id), {}, {
        preserveScroll: true,
    });
};

const deletePage = () => {
    if (!selectedPage.value) {
        return;
    }

    if (!window.confirm(`Padam halaman "${selectedPage.value.title}"?`)) {
        return;
    }

    router.delete(route('guidelines.destroy', selectedPage.value.id), {
        preserveScroll: true,
    });
};

const persistOrder = () => {
    router.post(route('guidelines.reorder'), {
        ordered_ids: localPages.value.map((item) => item.id),
    }, {
        preserveScroll: true,
        preserveState: true,
    });
};

const movePage = (index, direction) => {
    const target = index + direction;

    if (target < 0 || target >= localPages.value.length) {
        return;
    }

    const cloned = [...localPages.value];
    const [moved] = cloned.splice(index, 1);
    cloned.splice(target, 0, moved);
    localPages.value = cloned;
    persistOrder();
};

const dragStart = (id) => {
    draggingPageId.value = id;
};

const dropOn = (targetId) => {
    if (!draggingPageId.value || draggingPageId.value === targetId) {
        return;
    }

    const fromIndex = localPages.value.findIndex((item) => item.id === draggingPageId.value);
    const toIndex = localPages.value.findIndex((item) => item.id === targetId);

    if (fromIndex === -1 || toIndex === -1) {
        return;
    }

    const cloned = [...localPages.value];
    const [moved] = cloned.splice(fromIndex, 1);
    cloned.splice(toIndex, 0, moved);
    localPages.value = cloned;
    draggingPageId.value = null;
    persistOrder();
};

onMounted(() => {
    syncEditorFromSelected();
    window.addEventListener('beforeunload', handleBeforeUnload);
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
});
</script>

<template>
    <Head title="Urus Garis Panduan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Urus Garis Panduan</h2>
        </template>

        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <section class="surface-card">
                <h3 class="section-title">Cipta Halaman Baru</h3>
                <p class="section-subtitle">Tambah sub page baru. Ia akan muncul dalam menu Garis Panduan selepas diterbitkan.</p>

                <form class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto]" @submit.prevent="createPage">
                    <input
                        v-model="createForm.title"
                        type="text"
                        required
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Contoh: Panduan Tuntutan Kecemasan"
                    >
                    <button
                        type="submit"
                        :disabled="createForm.processing"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
                    >
                        Tambah Halaman
                    </button>
                </form>
                <p v-if="createForm.errors.title || createForm.errors.draft_html" class="mt-2 text-sm text-rose-600">
                    {{ createForm.errors.title || createForm.errors.draft_html }}
                </p>
            </section>

            <section class="grid grid-cols-1 gap-4 lg:grid-cols-[320px_1fr]">
                <aside class="surface-card overflow-hidden">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <h4 class="text-sm font-semibold text-slate-800">Senarai Halaman</h4>
                    </div>
                    <div class="max-h-[65vh] overflow-y-auto">
                        <button
                            v-for="(page, index) in pages"
                            :key="page.id"
                            type="button"
                            class="w-full border-b border-slate-100 px-4 py-3 text-left transition hover:bg-slate-50"
                            :class="selectedId === page.id ? 'bg-blue-50' : ''"
                            draggable="true"
                            @dragstart="dragStart(page.id)"
                            @dragover.prevent
                            @drop.prevent="dropOn(page.id)"
                            @click="selectPage(page.id)"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">{{ page.title }}</p>
                                    <p class="mt-1 text-xs text-slate-500">/{{ page.slug }}</p>
                                    <p class="mt-1 text-[11px]" :class="page.is_published ? 'text-emerald-600' : 'text-amber-600'">
                                        {{ page.is_published ? 'Published' : 'Draft' }}
                                    </p>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <button
                                        type="button"
                                        class="rounded border border-slate-300 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 hover:bg-slate-100"
                                        @click.stop="movePage(index, -1)"
                                    >
                                        ↑
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded border border-slate-300 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 hover:bg-slate-100"
                                        @click.stop="movePage(index, 1)"
                                    >
                                        ↓
                                    </button>
                                </div>
                            </div>
                        </button>
                    </div>
                </aside>

                <div class="surface-card" v-if="selectedPage">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <input
                            v-model="editForm.title"
                            type="text"
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-md"
                            placeholder="Tajuk garis panduan"
                        >
                        <span class="text-xs text-slate-500">Paparan terbaik mobile dan desktop disokong.</span>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white">
                        <div class="flex flex-wrap gap-2 border-b border-slate-200 p-3">
                            <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="runEditorCommand('bold')">Bold</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="runEditorCommand('italic')">Italic</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="runEditorCommand('insertUnorderedList')">Bullet</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="runEditorCommand('insertOrderedList')">Numbered</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="runEditorCommand('formatBlock', '<h2>')">H2</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="runEditorCommand('formatBlock', '<p>')">Paragraph</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="insertTableTemplate">Table</button>
                            <button type="button" class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="insertLink">Link</button>
                        </div>

                        <div
                            ref="editorRef"
                            contenteditable="true"
                            class="min-h-[320px] p-4 text-sm text-slate-700 focus:outline-none"
                            @input="updateDraftFromEditor"
                        ></div>
                    </div>

                    <p v-if="editForm.errors.title || editForm.errors.draft_html" class="mt-2 text-sm text-rose-600">
                        {{ editForm.errors.title || editForm.errors.draft_html }}
                    </p>
                    <p v-if="!canPublish" class="mt-2 text-xs font-medium text-amber-700">
                        Isi tajuk dan kandungan draf terlebih dahulu sebelum terbitkan.
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            @click="previewDraft"
                        >
                            Preview
                        </button>
                        <button
                            type="button"
                            :disabled="editForm.processing"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-60"
                            @click="saveDraft"
                        >
                            Simpan Draf
                        </button>
                        <button
                            type="button"
                            :disabled="editForm.processing || !canPublish"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                            @click="publishPage"
                        >
                            Terbitkan
                        </button>
                        <button
                            v-if="selectedPage.is_published"
                            type="button"
                            class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100"
                            @click="unpublishPage"
                        >
                            Nyahterbit
                        </button>
                        <button
                            type="button"
                            class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100"
                            @click="deletePage"
                        >
                            Padam
                        </button>
                    </div>
                </div>

                <div v-else class="surface-card text-sm text-slate-600">
                    Tiada halaman garis panduan. Sila tambah halaman baru.
                </div>
            </section>
        </div>

        <div
            v-if="showPreviewModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 px-4"
            @click.self="closePreview"
        >
            <div class="w-full max-w-4xl rounded-2xl bg-white p-4 shadow-2xl sm:p-5">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h4 class="text-base font-semibold text-slate-900">Preview Garis Panduan</h4>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                        @click="closePreview"
                    >
                        Tutup
                    </button>
                </div>
                <div class="max-h-[72vh] overflow-auto rounded-xl border border-slate-200 p-4">
                    <div class="prose prose-sm max-w-none text-slate-700 lg:prose-base" v-html="editForm.draft_html" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
