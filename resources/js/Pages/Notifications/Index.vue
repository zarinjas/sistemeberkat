<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    applications: {
        type: Array,
        default: () => [],
    },
    groups: {
        type: Object,
        default: () => ({ status: [], branch: [] }),
    },
});

const page = usePage();

const showComposer = ref(false);
const selectedApplicationId = ref(null);
const targetType = ref('single');
const groupType = ref('status');
const groupValue = ref('');
const subject = ref('Kemaskini Status Permohonan BERKAT');
const message = ref('Status permohonan anda telah dikemaskini. Sila semak dashboard untuk maklumat lanjut.');
const previewLoading = ref(false);
const recipientCount = ref(0);
const toastVisible = ref(false);
const toastMessage = ref('');
const toastType = ref('success');

let toastTimer = null;

const notifiableApplications = computed(() => props.applications.filter((item) => item.applicant_email));

const groupOptions = computed(() => {
    if (groupType.value === 'branch') {
        return props.groups?.branch || [];
    }

    return props.groups?.status || [];
});

const openComposer = (applicationId = null) => {
    showComposer.value = true;

    if (applicationId) {
        targetType.value = 'single';
        selectedApplicationId.value = applicationId;
    } else if (!selectedApplicationId.value) {
        selectedApplicationId.value = notifiableApplications.value[0]?.id || null;
    }

    groupValue.value = groupOptions.value[0]?.value || '';
};

const closeComposer = () => {
    showComposer.value = false;
};

const loadRecipientPreview = async () => {
    if (!showComposer.value) {
        return;
    }

    previewLoading.value = true;

    try {
        const response = await window.axios.post(route('admin.notifications.preview-count'), {
            target_type: targetType.value,
            application_id: targetType.value === 'single' ? selectedApplicationId.value : null,
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

const sendEmail = (applicationId, customSubject = null, customMessage = null) => {
    router.post(route('admin.notifications.send', applicationId), {
        subject: customSubject || 'Kemaskini Status Permohonan BERKAT',
        message: customMessage || 'Status permohonan anda telah dikemaskini. Sila semak dashboard untuk maklumat lanjut.',
    }, {
        preserveScroll: true,
    });
};

const sendComposedEmail = () => {
    if (!subject.value || !message.value) {
        return;
    }

    if (targetType.value === 'single' && !selectedApplicationId.value) {
        return;
    }

    if (targetType.value === 'group' && (!groupType.value || !groupValue.value)) {
        return;
    }

    router.post(route('admin.notifications.send.bulk'), {
        target_type: targetType.value,
        group_type: targetType.value === 'group' ? groupType.value : null,
        group_value: targetType.value === 'group' ? groupValue.value : null,
        application_id: targetType.value === 'single' ? selectedApplicationId.value : null,
        subject: subject.value,
        message: message.value,
    }, {
        preserveScroll: true,
    });

    closeComposer();
};

watch(groupType, () => {
    groupValue.value = groupOptions.value[0]?.value || '';
});

watch(
    [showComposer, targetType, selectedApplicationId, groupType, groupValue],
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
</script>

<template>
    <Head title="Modul Notifikasi Emel" />

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
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Modul Notifikasi Emel</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Hantar Notifikasi Emel Kepada Pemohon</h3>
                            <p class="mt-1 text-sm text-slate-500">Gunakan tindakan pantas di bawah atau klik butang cipta untuk menulis mesej emel khusus.</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
                            @click="openComposer()"
                        >
                            Cipta Notifikasi Emel
                        </button>
                    </div>

                    <div v-if="showComposer" class="mt-5 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <p class="text-sm font-semibold text-indigo-800">Borang Cipta Notifikasi Emel (BM)</p>
                        <p class="mt-1 text-xs text-indigo-700">Langkah: Pilih sasaran (semua/pengguna/kumpulan), isi subjek dan mesej, kemudian klik Hantar Notifikasi.</p>

                        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Sasaran Emel</label>
                                <select
                                    v-model="targetType"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="all">Hantar kepada Semua Pemohon</option>
                                    <option value="single">Hantar kepada Pengguna Tertentu</option>
                                    <option value="group">Hantar kepada Kumpulan</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Pilih Permohonan</label>
                                <select
                                    v-model="selectedApplicationId"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    :disabled="targetType !== 'single'"
                                >
                                    <option :value="null">-- Pilih --</option>
                                    <option v-for="item in notifiableApplications" :key="item.id" :value="item.id">
                                        {{ item.reference_no }} - {{ item.applicant_name }}
                                    </option>
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
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Subjek Emel</label>
                                <input
                                    v-model="subject"
                                    type="text"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Masukkan subjek emel"
                                >
                            </div>
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
                            Notis: Emel akan dihantar kepada semua pemohon yang mempunyai alamat emel sah.
                        </div>

                        <div class="mt-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                            <span v-if="previewLoading">Mengira penerima emel...</span>
                            <span v-else>Jumlah penerima sasaran: {{ recipientCount }}</span>
                        </div>

                        <div class="mt-3">
                            <label class="mb-1 block text-xs font-semibold text-slate-700">Mesej Emel</label>
                            <textarea
                                v-model="message"
                                rows="4"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Masukkan mesej notifikasi kepada pemohon"
                            ></textarea>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-300"
                                :disabled="previewLoading || recipientCount < 1 || !subject || !message || (targetType === 'single' && !selectedApplicationId) || (targetType === 'group' && !groupValue)"
                                @click="sendComposedEmail"
                            >
                                Hantar Notifikasi
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                @click="closeComposer"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Rujukan</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Pemohon</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Emel</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Kemaskini</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-if="!applications.length">
                                    <td colspan="6" class="px-4 py-6 text-center text-slate-500">Tiada data permohonan untuk notifikasi.</td>
                                </tr>
                                <tr v-for="item in applications" :key="item.id">
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ item.reference_no }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ item.applicant_name }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ item.applicant_email || '-' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ item.status }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ item.updated_at }}</td>
                                    <td class="px-4 py-3">
                                        <button
                                            type="button"
                                            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-300"
                                            :disabled="!item.applicant_email"
                                            @click="sendEmail(item.id)"
                                        >
                                            Hantar Emel
                                        </button>
                                        <button
                                            type="button"
                                            class="ml-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:bg-slate-100"
                                            :disabled="!item.applicant_email"
                                            @click="openComposer(item.id)"
                                        >
                                            Cipta
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
