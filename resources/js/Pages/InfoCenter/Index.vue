<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    adminPosters: {
        type: Array,
        default: () => [],
    },
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
    ajkContent: {
        type: Object,
        default: () => ({
            image_url: null,
            updated_at: null,
            updated_by: '',
        }),
    },
});

const page = usePage();
const isSuperadmin = Boolean(page.props.auth?.user?.is_superadmin);
const isAdmin = page.props.auth?.user?.role === 'admin';
const isAdminOrSuperadmin = isAdmin || isSuperadmin;

const openSections = ref(['infografik']);

const toggleSection = (key) => {
    if (openSections.value.includes(key)) {
        openSections.value = openSections.value.filter((item) => item !== key);
        return;
    }

    openSections.value.push(key);
};

const isOpen = (key) => openSections.value.includes(key);

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

const posterForm = useForm({
    title: '',
    sort_order: 0,
    aspect_ratio: '1:1',
    image: null,
});

const onPosterImageChange = (event) => {
    posterForm.image = event.target.files?.[0] ?? null;
};

const submitPoster = () => {
    posterForm.post(route('admin.system.dashboard-posters.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            posterForm.reset();
            posterForm.aspect_ratio = '1:1';
            posterForm.sort_order = 0;
        },
    });
};

const togglePosterStatus = (poster) => {
    router.patch(route('admin.system.dashboard-posters.update', poster.id), {
        title: poster.title,
        sort_order: poster.sort_order,
        aspect_ratio: poster.aspect_ratio || '1:1',
        is_active: !poster.is_active,
    }, {
        preserveScroll: true,
    });
};

const deletePoster = (posterId) => {
    if (!window.confirm('Padam infografik ini?')) {
        return;
    }

    router.delete(route('admin.system.dashboard-posters.destroy', posterId), {
        preserveScroll: true,
    });
};

const infographicPosters = computed(() => props.adminPosters || []);
const ajkImage = computed(() => props.ajkContent?.image_url || null);

const ajkForm = useForm({
    image: null,
});

const onAjkImageChange = (event) => {
    ajkForm.image = event.target.files?.[0] ?? null;
};

const uploadAjkImage = () => {
    ajkForm.post(route('info-center.ajk.upload'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            ajkForm.reset();
        },
    });
};

const removeAjkImage = () => {
    if (!window.confirm('Padam imej Senarai AJK semasa?')) {
        return;
    }

    router.delete(route('info-center.ajk.remove'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pusat Info BERKAT" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Pusat Info BERKAT</h2>
        </template>

        <div class="page-shell px-4 sm:px-6 lg:px-8">
            <section class="surface-card">
                <h3 class="section-title">Modul Pusat Info (Fasa B: Infografik)</h3>
                <p class="section-subtitle">Panel Infografik telah diaktifkan. Dua panel lain kekal scaffold sementara menunggu fasa seterusnya.</p>
            </section>

            <section id="poster-dashboard-section" class="surface-card p-0">
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-3xl px-6 py-5 text-left"
                    @click="toggleSection('infografik')"
                >
                    <div>
                        <h3 class="section-title">1. Infografik</h3>
                        <p class="section-subtitle">Upload poster 1:1, 4:5, 16:9 dengan paparan dashboard yang seragam.</p>
                    </div>
                    <span class="text-sm font-semibold text-slate-500">{{ isOpen('infografik') ? 'Tutup' : 'Buka' }}</span>
                </button>

                <div v-if="isOpen('infografik')" class="border-t border-slate-100 px-6 pb-6">
                    <div v-if="isAdminOrSuperadmin" class="mt-5 space-y-5">
                        <form class="grid grid-cols-1 gap-3 lg:grid-cols-12" @submit.prevent="submitPoster">
                            <input
                                v-model="posterForm.title"
                                type="text"
                                required
                                class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:col-span-4"
                                placeholder="Tajuk infografik"
                            >

                            <select
                                v-model="posterForm.aspect_ratio"
                                class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:col-span-2"
                            >
                                <option value="1:1">1:1</option>
                                <option value="4:5">4:5</option>
                                <option value="16:9">16:9</option>
                            </select>

                            <input
                                v-model.number="posterForm.sort_order"
                                type="number"
                                min="0"
                                class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:col-span-2"
                                placeholder="Urutan"
                            >

                            <input
                                type="file"
                                accept="image/png,image/jpeg,image/jpg,image/webp"
                                required
                                class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 lg:col-span-3"
                                @change="onPosterImageChange"
                            >

                            <button
                                type="submit"
                                :disabled="posterForm.processing"
                                class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60 lg:col-span-1"
                            >
                                {{ posterForm.processing ? '...' : 'Tambah' }}
                            </button>
                        </form>

                        <p class="text-xs text-slate-500">Syarat upload: hanya nisbah 1:1, 4:5, atau 16:9. Paparan di dashboard ahli akan diseragamkan dalam frame tetap (mobile-first).</p>

                        <p v-if="posterForm.errors.title || posterForm.errors.image || posterForm.errors.aspect_ratio || posterForm.errors.sort_order" class="text-sm text-rose-600">
                            {{ posterForm.errors.title || posterForm.errors.image || posterForm.errors.aspect_ratio || posterForm.errors.sort_order }}
                        </p>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Poster</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Tajuk</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Ratio</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Urutan</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                        <th class="px-4 py-3 text-left font-semibold text-slate-600">Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr v-if="!infographicPosters.length">
                                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada infografik dimuat naik.</td>
                                    </tr>
                                    <tr v-for="poster in infographicPosters" :key="poster.id">
                                        <td class="px-4 py-3">
                                            <img :src="poster.image_url" :alt="poster.title" class="h-14 w-14 rounded-lg border border-slate-200 object-cover">
                                        </td>
                                        <td class="px-4 py-3 font-medium text-slate-900">{{ poster.title }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ poster.aspect_ratio || '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ poster.sort_order }}</td>
                                        <td class="px-4 py-3">
                                            <span class="status-pill" :class="poster.is_active ? 'bg-emerald-100 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200'">
                                                {{ poster.is_active ? 'Aktif' : 'Tidak Aktif' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <button type="button" class="inline-flex items-center rounded-xl bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-600" @click="togglePosterStatus(poster)">
                                                    {{ poster.is_active ? 'Nyahaktif' : 'Aktifkan' }}
                                                </button>
                                                <button type="button" class="inline-flex items-center rounded-xl bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500" @click="deletePoster(poster.id)">
                                                    Padam
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-else class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
                        Panel ini untuk admin/superadmin mengurus infografik. Ahli hanya melihat hasil paparan pada dashboard pengguna.
                    </div>
                </div>
            </section>

            <section class="surface-card p-0">
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-3xl px-6 py-5 text-left"
                    @click="toggleSection('perlembagaan')"
                >
                    <div>
                        <h3 class="section-title">2. Undang-Undang & Perlembagaan</h3>
                        <p class="section-subtitle">Kandungan rich text untuk diurus superadmin dan dipaparkan kepada ahli.</p>
                    </div>
                    <span class="text-sm font-semibold text-slate-500">{{ isOpen('perlembagaan') ? 'Tutup' : 'Buka' }}</span>
                </button>

                <div v-if="isOpen('perlembagaan')" class="border-t border-slate-100 px-6 pb-6">
                    <div v-if="isSuperadmin" class="mt-5 space-y-4">
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

                        <p class="text-xs text-slate-500">Tip: hanya kandungan yang diterbitkan akan dipaparkan kepada pengguna.</p>
                    </div>

                    <div v-else-if="legalContent.published_html" class="mt-5 rounded-2xl border border-slate-200 bg-white p-5">
                        <h4 class="text-base font-semibold text-slate-900">{{ legalContent.title || 'Undang-Undang & Perlembagaan BERKAT' }}</h4>
                        <div class="prose prose-sm mt-4 max-w-none text-slate-700" v-html="legalContent.published_html" />
                        <p v-if="legalContent.published_at" class="mt-4 text-xs text-slate-500">
                            Dikemaskini: {{ legalContent.published_at }}
                            <span v-if="legalContent.published_by">oleh {{ legalContent.published_by }}</span>
                        </p>
                    </div>

                    <div v-else class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
                        Kandungan Undang-Undang & Perlembagaan belum diterbitkan lagi.
                    </div>
                </div>
            </section>

            <section class="surface-card p-0">
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-3xl px-6 py-5 text-left"
                    @click="toggleSection('ajk')"
                >
                    <div>
                        <h3 class="section-title">3. Senarai AJK BERKAT</h3>
                        <p class="section-subtitle">Panel upload imej PNG untuk organisasi AJK.</p>
                    </div>
                    <span class="text-sm font-semibold text-slate-500">{{ isOpen('ajk') ? 'Tutup' : 'Buka' }}</span>
                </button>

                <div v-if="isOpen('ajk')" class="border-t border-slate-100 px-6 pb-6">
                    <div v-if="isSuperadmin" class="mt-5 space-y-4">
                        <form class="grid grid-cols-1 gap-3 md:grid-cols-12" @submit.prevent="uploadAjkImage">
                            <input
                                type="file"
                                accept="image/png"
                                required
                                class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 md:col-span-9"
                                @change="onAjkImageChange"
                            >
                            <button
                                type="submit"
                                :disabled="ajkForm.processing"
                                class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60 md:col-span-3"
                            >
                                {{ ajkForm.processing ? 'Upload...' : 'Upload PNG' }}
                            </button>
                        </form>

                        <p v-if="ajkForm.errors.image" class="text-sm text-rose-600">{{ ajkForm.errors.image }}</p>
                        <p class="text-xs text-slate-500">Format dibenarkan: PNG sahaja.</p>

                        <div v-if="ajkImage" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4">
                            <img :src="ajkImage" alt="Senarai AJK BERKAT" class="w-full rounded-xl border border-slate-200 object-contain">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs text-slate-500">
                                    Dikemaskini: {{ props.ajkContent?.updated_at || '-' }}
                                    <span v-if="props.ajkContent?.updated_by">oleh {{ props.ajkContent.updated_by }}</span>
                                </p>
                                <button
                                    type="button"
                                    class="rounded-xl bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500"
                                    @click="removeAjkImage"
                                >
                                    Padam Imej
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="ajkImage" class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                        <img :src="ajkImage" alt="Senarai AJK BERKAT" class="w-full rounded-xl border border-slate-200 object-contain">
                    </div>

                    <div v-else class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
                        Senarai AJK belum dimuat naik lagi.
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
