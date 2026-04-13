<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    adminPosters: {
        type: Array,
        default: () => [],
    },
    posters: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const isAdmin = page.props.auth?.user?.role === 'admin';
const isSuperadmin = Boolean(page.props.auth?.user?.is_superadmin);
const isAdminOrSuperadmin = isAdmin || isSuperadmin;

const posterForm = useForm({
    title: '',
    image: null,
});

const showCreateModal = ref(false);
const showUploadModal = ref(false);
const uploadState = ref('idle');
const uploadPreviewUrl = ref('');
const isDragOver = ref(false);
const fileInputRef = ref(null);
let successTimer = null;

const selectImageFile = (file) => {
    if (uploadPreviewUrl.value) {
        URL.revokeObjectURL(uploadPreviewUrl.value);
    }

    posterForm.image = file ?? null;

    if (posterForm.image) {
        uploadPreviewUrl.value = URL.createObjectURL(posterForm.image);
    } else {
        uploadPreviewUrl.value = '';
    }
};

const onPosterImageChange = (event) => {
    selectImageFile(event.target.files?.[0] ?? null);
};

const openFilePicker = () => {
    fileInputRef.value?.click();
};

const onDropZoneDragOver = () => {
    isDragOver.value = true;
};

const onDropZoneDragLeave = () => {
    isDragOver.value = false;
};

const onDropZoneDrop = (event) => {
    isDragOver.value = false;
    selectImageFile(event.dataTransfer?.files?.[0] ?? null);
};

const submitPoster = () => {
    if (!posterForm.image) {
        return;
    }

    showCreateModal.value = false;
    showUploadModal.value = true;
    uploadState.value = 'uploading';

    posterForm.post(route('admin.system.dashboard-posters.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadState.value = 'success';

            if (successTimer) {
                clearTimeout(successTimer);
            }

            successTimer = setTimeout(() => {
                posterForm.reset();
                showUploadModal.value = false;
                uploadState.value = 'idle';

                if (uploadPreviewUrl.value) {
                    URL.revokeObjectURL(uploadPreviewUrl.value);
                }
                uploadPreviewUrl.value = '';

                if (fileInputRef.value) {
                    fileInputRef.value.value = '';
                }
            }, 1200);
        },
        onError: () => {
            showUploadModal.value = false;
            uploadState.value = 'idle';
            showCreateModal.value = true;
        },
        onCancel: () => {
            showUploadModal.value = false;
            uploadState.value = 'idle';
            showCreateModal.value = true;
        },
        onFinish: () => {
            if (!posterForm.processing && uploadState.value !== 'success') {
                showUploadModal.value = false;
                uploadState.value = 'idle';
                showCreateModal.value = true;
            }
        },
    });
};

const openCreateModal = () => {
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

onBeforeUnmount(() => {
    if (successTimer) {
        clearTimeout(successTimer);
    }

    if (uploadPreviewUrl.value) {
        URL.revokeObjectURL(uploadPreviewUrl.value);
    }
});

const togglePosterStatus = (poster) => {
    router.patch(route('admin.system.dashboard-posters.update', poster.id), {
        title: poster.title,
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
const publishedPosters = computed(() => props.posters || []);
</script>

<template>
    <Head title="Pusat Info • Infografik" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Pusat Info • Infografik</h2>
        </template>

        <div :class="isAdminOrSuperadmin ? 'page-shell px-4 sm:px-6 lg:px-8' : 'mx-auto max-w-7xl -mt-1 space-y-4 px-4 sm:px-6 lg:px-8'">
            <section v-if="isAdminOrSuperadmin" class="surface-card">
                <h3 class="section-title">Infografik</h3>
                <p class="section-subtitle" v-if="isAdminOrSuperadmin">Urus infografik untuk dipaparkan pada dashboard ahli.</p>
            </section>

            <section class="surface-card">
                <div v-if="isAdminOrSuperadmin" class="space-y-5">
                    <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">Muat naik poster baharu melalui popup.</p>
                            <p class="text-xs text-slate-500">Saiz paparan terbaik: 1080px x 1350px (4:5). Maksimum fail: 10MB.</p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                            @click="openCreateModal"
                        >
                            Upload Poster Baru
                        </button>
                    </div>

                    <p v-if="posterForm.errors.title || posterForm.errors.image" class="text-sm text-rose-600">
                        {{ posterForm.errors.title || posterForm.errors.image }}
                    </p>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Poster</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Tajuk</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Dikemaskini</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-if="!infographicPosters.length">
                                    <td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada infografik dimuat naik.</td>
                                </tr>
                                <tr v-for="poster in infographicPosters" :key="poster.id">
                                    <td class="px-4 py-3">
                                        <img :src="poster.image_url" :alt="poster.title" class="h-14 w-14 rounded-lg border border-slate-200 object-cover">
                                    </td>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ poster.title }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ poster.updated_at || '-' }}</td>
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

                <div v-else>
                    <div v-if="publishedPosters.length" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <article v-for="poster in publishedPosters" :key="poster.id" class="surface-card-soft">
                            <div class="relative aspect-[4/5] overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                <img :src="poster.image_url" :alt="poster.title" class="h-full w-full object-cover">
                            </div>
                        </article>
                    </div>

                    <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
                        Tiada infografik tersedia.
                    </div>
                </div>
            </section>
        </div>

        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/60 px-4"
            @click.self="closeCreateModal"
        >
            <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-2xl">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h4 class="text-base font-semibold text-slate-900">Upload Poster Baru</h4>
                        <p class="text-xs text-slate-500">Saiz terbaik: 1080px x 1350px (4:5). Maksimum fail 10MB.</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                        @click="closeCreateModal"
                    >
                        Tutup
                    </button>
                </div>

                <form class="space-y-3" @submit.prevent="submitPoster">
                    <input
                        v-model="posterForm.title"
                        type="text"
                        required
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Tajuk infografik"
                    >

                    <input
                        ref="fileInputRef"
                        type="file"
                        accept="image/png,image/jpeg,image/jpg,image/webp"
                        class="hidden"
                        @change="onPosterImageChange"
                    >

                    <div
                        class="rounded-xl border-2 border-dashed bg-slate-50 px-3 py-3 text-sm transition"
                        :class="isDragOver ? 'border-indigo-500 bg-indigo-50/60' : 'border-slate-300 text-slate-600'"
                        @dragover.prevent="onDropZoneDragOver"
                        @dragleave.prevent="onDropZoneDragLeave"
                        @drop.prevent="onDropZoneDrop"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-semibold text-slate-700">
                                    {{ posterForm.image?.name || 'Seret fail ke sini atau klik Pilih Fail' }}
                                </p>
                                <p class="text-[11px] text-slate-500">PNG, JPG, JPEG, WEBP (maks 10MB)</p>
                            </div>
                            <button
                                type="button"
                                class="inline-flex shrink-0 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                                @click="openFilePicker"
                            >
                                Pilih Fail
                            </button>
                        </div>
                    </div>

                    <div v-if="uploadPreviewUrl" class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Pratonton imej</p>
                        <div class="flex items-center gap-3">
                            <img :src="uploadPreviewUrl" alt="Pratonton infografik" class="h-20 w-20 rounded-xl border border-slate-200 object-cover">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ posterForm.image?.name || 'Imej dipilih' }}</p>
                                <p class="text-xs text-slate-500">Poster terbaru akan dipaparkan di bahagian atas.</p>
                            </div>
                        </div>
                    </div>

                    <p v-if="posterForm.errors.title || posterForm.errors.image" class="text-sm text-rose-600">
                        {{ posterForm.errors.title || posterForm.errors.image }}
                    </p>

                    <div class="flex justify-end gap-2 pt-1">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            @click="closeCreateModal"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="posterForm.processing"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
                        >
                            {{ posterForm.processing ? 'Memuat naik...' : 'Muat Naik Poster' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            v-if="showUploadModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl">
                <div class="flex items-center justify-between gap-3">
                    <h4 class="text-base font-semibold text-slate-900">
                        {{ uploadState === 'success' ? 'Infografik berjaya dimuat naik' : 'Sedang memuat naik infografik' }}
                    </h4>
                    <span
                        v-if="uploadState !== 'success'"
                        class="inline-flex h-6 w-6 animate-spin rounded-full border-2 border-indigo-200 border-t-indigo-600"
                    ></span>
                    <span
                        v-else
                        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"
                    >
                        ✓
                    </span>
                </div>

                <p class="mt-2 text-sm text-slate-600">
                    {{ uploadState === 'success' ? 'Fail disimpan dan senarai infografik telah dikemaskini.' : 'Sila tunggu sebentar. Jangan tutup halaman ini sehingga selesai.' }}
                </p>

                <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-slate-200">
                    <div
                        class="h-full rounded-full bg-indigo-600 transition-all duration-200"
                        :style="{ width: `${uploadState === 'success' ? 100 : (posterForm.progress?.percentage ?? 12)}%` }"
                    ></div>
                </div>

                <p class="mt-2 text-right text-xs font-semibold text-slate-600">
                    {{ uploadState === 'success' ? 100 : (posterForm.progress?.percentage ?? 12) }}%
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
