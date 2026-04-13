<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

const props = defineProps({
    groups: {
        type: Object,
        default: () => ({ status: [], branch: [] }),
    },
    history: {
        type: Array,
        default: () => [],
    },
    historyFilters: {
        type: Object,
        default: () => ({
            kind: 'all',
            module: 'all',
            sender_id: 0,
            from: '',
            to: '',
            q: '',
        }),
    },
    historySenderOptions: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const showComposer = ref(false);
const targetType = ref('all');
const groupType = ref('status');
const groupValue = ref('');
const channels = ref(['mail', 'database']);
const subject = ref('Makluman Umum BERKAT');
const message = ref('Assalamualaikum dan salam sejahtera. Sila rujuk dashboard BERKAT untuk makluman terkini.');
const notificationImage = ref(null);

const previewLoading = ref(false);
const recipientCount = ref(0);
const draftPreviewLoading = ref(false);
const showDraftPreview = ref(false);
const draftPreview = ref(null);

const historyFilterForm = reactive({
    kind: props.historyFilters?.kind || 'all',
    module: props.historyFilters?.module || 'all',
    sender_id: Number(props.historyFilters?.sender_id || 0),
    from: props.historyFilters?.from || '',
    to: props.historyFilters?.to || '',
    q: props.historyFilters?.q || '',
});

const toastVisible = ref(false);
const toastMessage = ref('');
const toastType = ref('success');
const imagePreviewUrl = ref('');
const imagePreviewTitle = ref('Lampiran notifikasi');

let toastTimer = null;

const closeImagePreview = () => {
    imagePreviewUrl.value = '';
    imagePreviewTitle.value = 'Lampiran notifikasi';
    document.body.style.overflow = '';
};

const openImagePreview = (url, title = 'Lampiran notifikasi') => {
    if (!url) {
        return;
    }

    imagePreviewUrl.value = url;
    imagePreviewTitle.value = title;
    document.body.style.overflow = 'hidden';
};

const handleEscapePreview = (event) => {
    if (event.key === 'Escape') {
        closeImagePreview();
    }
};

const groupOptions = computed(() => {
    if (groupType.value === 'branch') {
        return props.groups?.branch || [];
    }

    return props.groups?.status || [];
});

const filteredHistory = computed(() => props.history || []);

const applyHistoryFilters = () => {
    router.get(route('admin.notifications.index'), {
        history_kind: historyFilterForm.kind !== 'all' ? historyFilterForm.kind : undefined,
        history_module: historyFilterForm.module !== 'all' ? historyFilterForm.module : undefined,
        history_sender_id: historyFilterForm.sender_id > 0 ? historyFilterForm.sender_id : undefined,
        history_from: historyFilterForm.from || undefined,
        history_to: historyFilterForm.to || undefined,
        history_q: historyFilterForm.q || undefined,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
};

const resetHistoryFilters = () => {
    historyFilterForm.kind = 'all';
    historyFilterForm.module = 'all';
    historyFilterForm.sender_id = 0;
    historyFilterForm.from = '';
    historyFilterForm.to = '';
    historyFilterForm.q = '';
    applyHistoryFilters();
};

const openComposer = () => {
    showComposer.value = true;
    groupValue.value = groupOptions.value[0]?.value || '';
};

const closeComposer = () => {
    showComposer.value = false;
    showDraftPreview.value = false;
    draftPreview.value = null;
    notificationImage.value = null;
};

const onNotificationImageChange = (event) => {
    notificationImage.value = event.target.files?.[0] || null;
    showDraftPreview.value = false;
};

const loadRecipientPreview = async () => {
    if (!showComposer.value) {
        return;
    }

    previewLoading.value = true;

    try {
        const response = await window.axios.post(route('admin.notifications.preview-count'), {
            target_type: targetType.value,
            group_type: targetType.value === 'group' ? groupType.value : null,
            group_value: targetType.value === 'group' ? groupValue.value : null,
        });

        recipientCount.value = Number(response?.data?.count || 0);
    } catch {
        recipientCount.value = 0;
    } finally {
        previewLoading.value = false;
    }
};

const isComposerValid = computed(() => {
    return !previewLoading.value
        && recipientCount.value > 0
        && Boolean(subject.value)
        && Boolean(message.value)
        && channels.value.length > 0
        && !(targetType.value === 'group' && !groupValue.value);
});

const sendComposedEmail = () => {
    if (!isComposerValid.value) {
        return;
    }

    router.post(route('admin.notifications.send.bulk'), {
        target_type: targetType.value,
        group_type: targetType.value === 'group' ? groupType.value : null,
        group_value: targetType.value === 'group' ? groupValue.value : null,
        subject: subject.value,
        message: message.value,
        channels: channels.value,
        notification_image: notificationImage.value,
    }, {
        preserveScroll: true,
        forceFormData: true,
    });

    closeComposer();
};

const previewComposedNotification = async () => {
    if (!isComposerValid.value) {
        return;
    }

    draftPreviewLoading.value = true;

    try {
        const response = await window.axios.post(route('admin.notifications.preview'), {
            target_type: targetType.value,
            group_type: targetType.value === 'group' ? groupType.value : null,
            group_value: targetType.value === 'group' ? groupValue.value : null,
            subject: subject.value,
            message: message.value,
            channels: channels.value,
        });

        draftPreview.value = response?.data || null;
        showDraftPreview.value = true;
    } catch {
        draftPreview.value = null;
        showDraftPreview.value = false;
    } finally {
        draftPreviewLoading.value = false;
    }
};

watch(groupType, () => {
    groupValue.value = groupOptions.value[0]?.value || '';
});

watch(targetType, () => {
    showDraftPreview.value = false;
});

watch(showComposer, (visible) => {
    if (!visible) {
        showDraftPreview.value = false;
        draftPreview.value = null;
    }
});

watch([groupType, groupValue, channels, subject, message], () => {
    showDraftPreview.value = false;
});

watch(
    [showComposer, targetType, groupType, groupValue],
    () => {
        loadRecipientPreview();
    },
    { immediate: true },
);

watch(
    [() => page.props.flash?.success, () => page.props.flash?.error],
    ([success, error]) => {
        const messageText = success || error;

        if (!messageText) {
            return;
        }

        toastMessage.value = messageText;
        toastType.value = success ? 'success' : 'error';
        toastVisible.value = true;

        if (toastTimer) {
            clearTimeout(toastTimer);
        }

        toastTimer = setTimeout(() => {
            toastVisible.value = false;
        }, 3500);
    },
    { immediate: true },
);

onMounted(() => {
    window.addEventListener('keydown', handleEscapePreview);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleEscapePreview);
    document.body.style.overflow = '';
});
</script>

<template>
    <Head title="Modul Notifikasi" />

    <div
        v-if="toastVisible"
        class="fixed right-4 top-4 z-50 max-w-md rounded-xl px-4 py-3 text-sm font-semibold shadow-lg"
        :class="toastType === 'success' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'"
    >
        <div class="flex items-start justify-between gap-3">
            <p>{{ toastMessage }}</p>
            <button type="button" class="text-white/90 hover:text-white" @click="toastVisible = false">
                ✕
            </button>
        </div>
    </div>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Modul Notifikasi</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Cipta Notifikasi Umum</h3>
                            <p class="mt-1 text-sm text-slate-500">Modul ini untuk pengumuman umum sahaja. Notifikasi keputusan permohonan dihantar dari modul Kelulusan.</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
                            @click="openComposer()"
                        >
                            Buka Borang Blast
                        </button>
                    </div>

                    <div v-if="showComposer" class="mt-5 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <p class="text-sm font-semibold text-indigo-800">Borang Cipta Notifikasi Umum</p>
                        <p class="mt-1 text-xs text-indigo-700">Langkah: pilih sasaran, pilih channel notifikasi, isi subjek/mesej, klik Pratonton, kemudian sahkan penghantaran.</p>

                        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Sasaran</label>
                                <select
                                    v-model="targetType"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="all">Semua Pemohon</option>
                                    <option value="group">Kumpulan Pemohon</option>
                                </select>
                            </div>

                            <div v-if="targetType === 'group'" class="md:col-span-1">
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Jenis Kumpulan</label>
                                <select
                                    v-model="groupType"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="status">Ikut Status Permohonan</option>
                                    <option value="branch">Ikut Cawangan Pemohon</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Channel</label>
                                <div class="flex h-[42px] items-center gap-4 rounded-lg border border-slate-300 bg-white px-3">
                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                                        <input v-model="channels" type="checkbox" value="mail" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        Emel
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                                        <input v-model="channels" type="checkbox" value="database" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        Dashboard
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Subjek</label>
                                <input
                                    v-model="subject"
                                    type="text"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Masukkan subjek"
                                >
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="mb-1 block text-xs font-semibold text-slate-700">Lampiran Imej (Pilihan)</label>
                            <input
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp"
                                class="w-full rounded-lg border border-slate-300 bg-white p-2 text-xs text-slate-700"
                                @change="onNotificationImageChange"
                            >
                            <p v-if="notificationImage" class="mt-1 text-[11px] text-slate-600">
                                Fail dipilih: {{ notificationImage.name }}
                            </p>
                        </div>

                        <div v-if="targetType === 'group'" class="mt-3">
                            <label class="mb-1 block text-xs font-semibold text-slate-700">Nilai Kumpulan</label>
                            <select
                                v-model="groupValue"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">-- Pilih Kumpulan --</option>
                                <option v-for="group in groupOptions" :key="`${groupType}-${group.value}`" :value="group.value">
                                    {{ group.label }} ({{ group.count }})
                                </option>
                            </select>
                        </div>

                        <div v-if="targetType === 'all'" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700">
                            Notis: Blast akan dihantar kepada semua pemohon mengikut channel yang dipilih.
                        </div>

                        <div class="mt-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                            <span v-if="previewLoading">Mengira penerima...</span>
                            <span v-else>Jumlah penerima sasaran: {{ recipientCount }}</span>
                        </div>

                        <div class="mt-3">
                            <label class="mb-1 block text-xs font-semibold text-slate-700">Mesej</label>
                            <textarea
                                v-model="message"
                                rows="4"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Masukkan mesej notifikasi umum"
                            ></textarea>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-300"
                                :disabled="!isComposerValid || draftPreviewLoading"
                                @click="previewComposedNotification"
                            >
                                {{ draftPreviewLoading ? 'Menjana Pratonton...' : 'Pratonton Sebelum Hantar' }}
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                @click="closeComposer"
                            >
                                Tutup
                            </button>
                        </div>

                        <div v-if="showDraftPreview && draftPreview" class="mt-4 rounded-xl border border-slate-300 bg-white p-4">
                            <h4 class="text-sm font-semibold text-slate-900">Pratonton Blast</h4>
                            <p class="mt-1 text-xs text-slate-600">Semak butiran di bawah sebelum sahkan penghantaran.</p>

                            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div class="rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                                    <p class="text-[11px] font-semibold text-slate-500">Subjek</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ draftPreview.subject }}</p>
                                </div>
                                <div class="rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                                    <p class="text-[11px] font-semibold text-slate-500">Channel</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ (draftPreview.channels || []).join(', ') }}</p>
                                </div>
                            </div>

                            <div class="mt-3 rounded-lg bg-slate-50 p-3 ring-1 ring-slate-200">
                                <p class="text-[11px] font-semibold text-slate-500">Mesej</p>
                                <p class="mt-1 text-sm text-slate-800 whitespace-pre-line">{{ draftPreview.message }}</p>
                            </div>

                            <div v-if="notificationImage" class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
                                Imej akan dihantar bersama notifikasi: <strong>{{ notificationImage.name }}</strong>
                            </div>

                            <div class="mt-3 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">
                                Jumlah penerima: {{ draftPreview.count }}
                                <span v-if="draftPreview.sample_truncated"> (paparan contoh pertama sahaja)</span>
                            </div>

                            <div class="mt-3 overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-xs">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-600">Nama</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-600">Rujukan</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-600">Emel</th>
                                            <th class="px-3 py-2 text-left font-semibold text-slate-600">Channel Efektif</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <tr v-for="recipient in draftPreview.sample_recipients || []" :key="`${recipient.application_id}-${recipient.reference_no}`">
                                            <td class="px-3 py-2 text-slate-700">{{ recipient.name }}</td>
                                            <td class="px-3 py-2 text-slate-700">{{ recipient.reference_no }}</td>
                                            <td class="px-3 py-2 text-slate-700">{{ recipient.email || '-' }}</td>
                                            <td class="px-3 py-2 text-slate-700">{{ (recipient.effective_channels || []).join(', ') || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-500"
                                    @click="sendComposedEmail"
                                >
                                    Sahkan & Hantar
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                    @click="showDraftPreview = false"
                                >
                                    Batal Pratonton
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-700">
                        Tip: Gunakan notifikasi umum untuk hebahan sistem, makluman pentadbiran, dan peringatan berkala.
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Sejarah Notifikasi</h3>
                            <p class="mt-1 text-sm text-slate-500">Semua notifikasi umum dan notifikasi permohonan dipaparkan dalam satu sejarah audit.</p>
                        </div>
                        <div class="flex flex-wrap items-end gap-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Jenis</label>
                                <select v-model="historyFilterForm.kind" class="rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="all">Semua</option>
                                    <option value="general">Umum</option>
                                    <option value="application">Permohonan</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Modul</label>
                                <select v-model="historyFilterForm.module" class="rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="all">Semua</option>
                                    <option value="notifications">Notifikasi</option>
                                    <option value="approvals">Kelulusan</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Penghantar</label>
                                <select v-model="historyFilterForm.sender_id" class="rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                    <option :value="0">Semua</option>
                                    <option v-for="sender in historySenderOptions" :key="sender.id" :value="sender.id">{{ sender.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Dari</label>
                                <input v-model="historyFilterForm.from" type="date" class="rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Hingga</label>
                                <input v-model="historyFilterForm.to" type="date" class="rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Carian</label>
                                <input
                                    v-model="historyFilterForm.q"
                                    type="text"
                                    class="rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Subjek / mesej"
                                    @keyup.enter="applyHistoryFilters"
                                >
                            </div>
                            <button
                                type="button"
                                class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500"
                                @click="applyHistoryFilters"
                            >
                                Tapis
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                @click="resetHistoryFilters"
                            >
                                Reset
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Masa Hantar</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Subjek</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Sasaran</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Channel</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Penerima</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Penghantar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-if="!filteredHistory.length">
                                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada sejarah notifikasi.</td>
                                </tr>
                                <tr v-for="item in filteredHistory" :key="item.id">
                                    <td class="px-4 py-3 text-slate-700">{{ item.sent_at }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-900">{{ item.subject }}</p>
                                        <p class="line-clamp-2 text-xs text-slate-500">{{ item.message }}</p>
                                        <div v-if="item.image_url" class="mt-2">
                                            <button
                                                type="button"
                                                class="block w-40"
                                                @click="openImagePreview(item.image_url, item.subject || 'Lampiran notifikasi')"
                                            >
                                                <img
                                                    :src="item.image_url"
                                                    alt="Lampiran notifikasi"
                                                    class="h-24 w-40 rounded-lg border border-slate-200 object-cover"
                                                >
                                            </button>
                                        </div>
                                        <div v-if="item.image_url" class="mt-1 flex items-center gap-3">
                                            <button
                                                type="button"
                                                class="inline-flex text-[11px] font-semibold text-indigo-600 hover:text-indigo-500"
                                                @click="openImagePreview(item.image_url, item.subject || 'Lampiran notifikasi')"
                                            >
                                                Pratonton Imej
                                            </button>
                                            <a
                                                :href="item.image_url"
                                                target="_blank"
                                                class="inline-flex text-[11px] font-semibold text-slate-600 hover:text-slate-500"
                                            >
                                                Buka Tab Baru
                                            </a>
                                        </div>
                                        <div class="mt-1 flex items-center gap-2">
                                            <span
                                                class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                                :class="item.notification_kind === 'application' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'"
                                            >
                                                {{ item.notification_kind === 'application' ? 'Permohonan' : 'Umum' }}
                                            </span>
                                            <span class="text-[11px] text-slate-500">Modul: {{ item.source_module || '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        <span v-if="item.target_type === 'all'">Semua Pemohon</span>
                                        <span v-else-if="item.target_type === 'single'">Pemohon Tertentu</span>
                                        <span v-else>Ikut Kumpulan</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ (item.channels || []).join(', ') || '-' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ item.recipient_count }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ item.sent_by }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <div
            v-if="imagePreviewUrl"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 px-4"
            @click.self="closeImagePreview"
        >
            <div class="w-full max-w-5xl rounded-2xl bg-slate-950 p-3 shadow-2xl">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="truncate text-xs font-semibold text-slate-200">{{ imagePreviewTitle }}</p>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-700 px-2.5 py-1 text-xs font-semibold text-slate-200 hover:bg-slate-800"
                        @click="closeImagePreview"
                    >
                        Tutup
                    </button>
                </div>
                <img
                    :src="imagePreviewUrl"
                    :alt="imagePreviewTitle"
                    class="max-h-[78vh] w-full rounded-xl object-contain"
                >
            </div>
        </div>
    </AuthenticatedLayout>
</template>
