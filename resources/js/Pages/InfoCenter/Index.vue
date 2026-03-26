<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    documents: {
        type: Array,
        default: () => [],
    },
    adminDocuments: {
        type: Array,
        default: () => [],
    },
    adminPosters: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const isSuperadmin = page.props.auth?.user?.is_superadmin;
const isAdminOrSuperadmin = page.props.auth?.user?.role === 'admin' || page.props.auth?.user?.is_superadmin;

const query = ref('');

const uploadForm = useForm({
    title: '',
    document_date: '',
    file: null,
});

const posterForm = useForm({
    title: '',
    sort_order: 0,
    image: null,
});

const onUploadFileChange = (event) => {
    uploadForm.file = event.target.files?.[0] ?? null;
};

const submitUpload = () => {
    uploadForm.post(route('admin.system.info-documents.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset();
        },
    });
};

const deleteDocument = (documentId) => {
    if (!window.confirm('Padam dokumen ini?')) {
        return;
    }

    router.delete(route('admin.system.info-documents.destroy', documentId), {
        preserveScroll: true,
    });
};

const toggleDocumentStatus = (doc) => {
    router.patch(route('admin.system.info-documents.update', doc.id), {
        title: doc.title,
        document_date: doc.document_date,
        is_active: !doc.is_active,
    }, {
        preserveScroll: true,
    });
};

const onPosterImageChange = (event) => {
    posterForm.image = event.target.files?.[0] ?? null;
};

const submitPoster = () => {
    posterForm.post(route('admin.system.dashboard-posters.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            posterForm.reset();
        },
    });
};

const deletePoster = (posterId) => {
    if (!window.confirm('Padam poster ini?')) {
        return;
    }

    router.delete(route('admin.system.dashboard-posters.destroy', posterId), {
        preserveScroll: true,
    });
};

const togglePosterStatus = (poster) => {
    router.patch(route('admin.system.dashboard-posters.update', poster.id), {
        title: poster.title,
        sort_order: poster.sort_order,
        is_active: !poster.is_active,
    }, {
        preserveScroll: true,
    });
};

const filteredDocuments = computed(() => {
    const keyword = query.value.trim().toLowerCase();

    if (!keyword) {
        return props.documents;
    }

    return props.documents.filter((document) =>
        [document.title, document.category]
            .filter(Boolean)
            .some((value) => String(value).toLowerCase().includes(keyword)),
    );
});

const groupedDocuments = computed(() => {
    const grouped = {};

    for (const document of filteredDocuments.value) {
        const category = document.category || 'Lain-lain';
        if (!grouped[category]) {
            grouped[category] = [];
        }
        grouped[category].push(document);
    }

    return Object.entries(grouped);
});
</script>

<template>
    <Head title="Pusat Info BERKAT" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Pusat Info BERKAT</h2>
        </template>

        <div class="page-shell px-4 sm:px-6 lg:px-8">
            <section v-if="isAdminOrSuperadmin" id="poster-dashboard-section" class="surface-card">
                <h3 class="section-title">Poster Dashboard Ahli (1:1)</h3>
                <p class="section-subtitle">Upload poster perayaan/info untuk dipaparkan sebagai carousel di Dashboard Ahli.</p>

                <form class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-12" @submit.prevent="submitPoster">
                    <input
                        v-model="posterForm.title"
                        type="text"
                        required
                        class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:col-span-4"
                        placeholder="Tajuk poster"
                    >
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
                        class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 lg:col-span-4"
                        @change="onPosterImageChange"
                    >
                    <button
                        type="submit"
                        :disabled="posterForm.processing"
                        class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60 lg:col-span-2"
                    >
                        {{ posterForm.processing ? 'Menyimpan...' : 'Tambah Poster' }}
                    </button>
                </form>

                <p v-if="posterForm.errors.title || posterForm.errors.image || posterForm.errors.sort_order" class="mt-2 text-sm text-rose-600">
                    {{ posterForm.errors.title || posterForm.errors.image || posterForm.errors.sort_order }}
                </p>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Poster</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Tajuk</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Urutan</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-if="!adminPosters.length">
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada poster dimuat naik.</td>
                            </tr>
                            <tr v-for="poster in adminPosters" :key="poster.id">
                                <td class="px-4 py-3">
                                    <img :src="poster.image_url" :alt="poster.title" class="h-14 w-14 rounded-lg border border-slate-200 object-cover">
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ poster.title }}</td>
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
            </section>

            <section v-if="isSuperadmin" class="surface-card">
                <h3 class="section-title">Upload Dokumen Info (Superadmin)</h3>
                <p class="section-subtitle">Tambah fail PDF baharu dengan tajuk dan tarikh dokumen.</p>

                <form class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-12" @submit.prevent="submitUpload">
                    <input
                        v-model="uploadForm.title"
                        type="text"
                        required
                        class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:col-span-4"
                        placeholder="Tajuk dokumen"
                    >
                    <input
                        v-model="uploadForm.document_date"
                        type="date"
                        required
                        class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:col-span-3"
                    >
                    <input
                        type="file"
                        accept="application/pdf"
                        required
                        class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 lg:col-span-3"
                        @change="onUploadFileChange"
                    >
                    <button
                        type="submit"
                        :disabled="uploadForm.processing"
                        class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60 lg:col-span-2"
                    >
                        {{ uploadForm.processing ? 'Simpan...' : 'Upload PDF' }}
                    </button>
                </form>

                <p v-if="uploadForm.errors.title || uploadForm.errors.document_date || uploadForm.errors.file" class="mt-2 text-sm text-rose-600">
                    {{ uploadForm.errors.title || uploadForm.errors.document_date || uploadForm.errors.file }}
                </p>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Tajuk</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Tarikh</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-if="!adminDocuments.length">
                                <td colspan="4" class="px-4 py-6 text-center text-slate-500">Belum ada dokumen dimuat naik.</td>
                            </tr>
                            <tr v-for="doc in adminDocuments" :key="doc.id">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ doc.title }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ doc.document_date_label || '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="status-pill" :class="doc.is_active ? 'bg-emerald-100 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200'">
                                        {{ doc.is_active ? 'Aktif' : 'Tidak Aktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <a :href="doc.file_url" target="_blank" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            Lihat
                                        </a>
                                        <button type="button" class="inline-flex items-center rounded-xl bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-600" @click="toggleDocumentStatus(doc)">
                                            {{ doc.is_active ? 'Nyahaktif' : 'Aktifkan' }}
                                        </button>
                                        <button type="button" class="inline-flex items-center rounded-xl bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500" @click="deleteDocument(doc.id)">
                                            Padam
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="surface-card">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="section-title">Dokumen Rujukan Ahli</h3>
                        <p class="section-subtitle">Akses undang-undang, senarai AJK, carta aliran proses, dan dokumen rasmi BERKAT.</p>
                    </div>
                    <input
                        v-model="query"
                        type="text"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 md:max-w-sm"
                        placeholder="Cari dokumen atau kategori..."
                    >
                </div>
            </section>

            <section v-if="!groupedDocuments.length" class="surface-card text-center text-sm text-slate-500">
                Tiada dokumen ditemui.
            </section>

            <section
                v-for="([category, items]) in groupedDocuments"
                :key="category"
                class="surface-card"
            >
                <h3 class="section-title">{{ category }}</h3>

                <div class="mt-4 space-y-3">
                    <article
                        v-for="doc in items"
                        :key="doc.id"
                        class="surface-card-soft flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ doc.title }}</p>
                            <p class="mt-1 text-xs text-slate-500">Tarikh Dokumen: {{ doc.document_date || '-' }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <a
                                :href="doc.file_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                Lihat PDF
                            </a>
                            <a
                                :href="doc.file_url"
                                download
                                class="inline-flex items-center rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-500"
                            >
                                Muat Turun
                            </a>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
