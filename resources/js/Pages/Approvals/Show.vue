<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    application: {
        type: Object,
        required: true,
    },
    submittedForm: {
        type: Object,
        default: () => ({ sections: [] }),
    },
});

const page = usePage();
const isSuperAdmin = computed(() => Boolean(page.props.auth?.user?.is_superadmin || page.props.auth?.user?.role === 'superadmin'));

const initialDecision = ['approved', 'rejected', 'kuiri'].includes(props.application.status)
    ? props.application.status
    : 'approved';
const selectedDecision = ref(initialDecision);
const reviewerRemarks = ref('');
const isSubmitting = ref(false);
const actionError = ref('');
const actionSuccess = ref('');
const notifyApplicant = ref(true);
const notificationChannels = ref(['mail', 'database']);
const notificationTemplateTouched = ref(false);
const notificationSubject = ref('');
const notificationMessage = ref('');

const statusTextMap = {
    submitted: 'Dihantar',
    under_review: 'Sedang Disemak',
    approved: 'Diluluskan',
    rejected: 'Ditolak',
    kuiri: 'Kuiri',
    disbursed: 'Selesai Bayaran',
};

const currentStatus = computed(() => props.application.status || 'under_review');

const statusBadgeClass = computed(() => {
    if (currentStatus.value === 'approved') {
        return 'bg-emerald-100 text-emerald-700 ring-emerald-200';
    }

    if (currentStatus.value === 'rejected') {
        return 'bg-rose-100 text-rose-700 ring-rose-200';
    }

    if (currentStatus.value === 'kuiri') {
        return 'bg-amber-100 text-amber-700 ring-amber-200';
    }

    return 'bg-blue-100 text-blue-700 ring-blue-200';
});

const decisionLabelMap = {
    approved: 'Luluskan Permohonan',
    rejected: 'Tolak Permohonan',
    kuiri: 'Kembalikan Untuk Kuiri',
};

const decisionHintMap = {
    approved: 'Permohonan akan diteruskan ke proses transaksi oleh superadmin.',
    rejected: 'Permohonan akan ditutup sebagai tidak lulus.',
    kuiri: 'Pemohon perlu kemukakan maklumat/dokumen tambahan.',
};

const requireRemarks = computed(() => ['rejected', 'kuiri'].includes(selectedDecision.value));
const primaryCategory = computed(() => {
    if (Array.isArray(props.application.category_tags) && props.application.category_tags.length) {
        return props.application.category_tags[0];
    }

    return 'Umum';
});

const applicantName = computed(() => props.application.user?.name || 'Pemohon BERKAT');

const buildTemplate = (decision, remarks = '') => {
    const noteLine = remarks?.trim() ? `\nCatatan Pegawai: ${remarks.trim()}` : '';

    if (decision === 'approved') {
        return {
            subject: 'Makluman: Permohonan BERKAT Anda Diluluskan',
            message: `Assalamualaikum dan salam sejahtera ${applicantName.value}.\nPermohonan anda (${props.application.reference_no}) bagi kategori ${primaryCategory.value} telah DILULUSKAN dan akan diteruskan ke proses transaksi.${noteLine}`,
        };
    }

    if (decision === 'rejected') {
        return {
            subject: 'Makluman: Keputusan Permohonan BERKAT',
            message: `Assalamualaikum dan salam sejahtera ${applicantName.value}.\nPermohonan anda (${props.application.reference_no}) bagi kategori ${primaryCategory.value} TIDAK DILULUSKAN buat masa ini.${noteLine}`,
        };
    }

    return {
        subject: 'Makluman: Permohonan BERKAT Perlu Tindakan Lanjut',
        message: `Assalamualaikum dan salam sejahtera ${applicantName.value}.\nPermohonan anda (${props.application.reference_no}) bagi kategori ${primaryCategory.value} memerlukan semakan tambahan (KUIRI). Sila kemaskini maklumat yang berkaitan.${noteLine}`,
    };
};

const applyTemplateDraft = () => {
    const draft = buildTemplate(selectedDecision.value, reviewerRemarks.value);
    notificationSubject.value = draft.subject;
    notificationMessage.value = draft.message;
};

applyTemplateDraft();

watch([selectedDecision, reviewerRemarks], () => {
    if (!notificationTemplateTouched.value) {
        applyTemplateDraft();
    }
});

const submittedFormSections = computed(() => props.submittedForm?.sections || []);

const formatTimestamp = (value) => {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return new Intl.DateTimeFormat('ms-MY', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const statusHistory = computed(() => {
    if (Array.isArray(props.application.status_histories) && props.application.status_histories.length) {
        return props.application.status_histories.map((entry) => ({
            id: `db-${entry.id}`,
            label: statusTextMap[entry.to_status] || formatLabel(entry.to_status),
            actor: entry.changed_by?.name || 'Pegawai',
            actorRole: entry.changed_by?.role || '',
            isOverride: Boolean(
                entry.changed_by?.role === 'superadmin'
                && (
                    (entry.from_status === 'approved' && entry.to_status === 'rejected')
                    || (entry.from_status === 'rejected' && entry.to_status === 'approved')
                )
            ),
            at: formatTimestamp(entry.changed_at),
            remarks: entry.notes || '-',
        }));
    }

    return [
        {
            id: 'seed-1',
            label: statusTextMap[currentStatus.value] || 'Dihantar',
            actor: 'Pemohon',
            at: formatTimestamp(props.application.submitted_at || new Date().toISOString()),
            remarks: '-',
        },
    ];
});

const decisionCardClass = (decision) => {
    if (!canSelectDecision(decision)) {
        return 'border-slate-200 bg-slate-100 text-slate-400 cursor-not-allowed';
    }

    if (selectedDecision.value === decision) {
        if (decision === 'approved') return 'border-emerald-400 bg-emerald-50';
        if (decision === 'rejected') return 'border-rose-400 bg-rose-50';
        return 'border-amber-400 bg-amber-50';
    }

    return 'border-slate-200 bg-white hover:border-slate-300';
};

const canSelectDecision = (decision) => {
    if (isSuperAdmin.value) {
        return true;
    }

    if (currentStatus.value === 'approved' && decision === 'rejected') {
        return false;
    }

    if (currentStatus.value === 'rejected' && decision === 'approved') {
        return false;
    }

    return true;
};

const submitDecision = () => {
    if (!canSelectDecision(selectedDecision.value)) {
        actionError.value = 'Pegawai penyemak tidak dibenarkan menukar keputusan lulus kepada tolak atau sebaliknya. Hubungi superadmin jika perlu.';
        actionSuccess.value = '';
        return;
    }

    if (requireRemarks.value && !reviewerRemarks.value.trim()) {
        actionError.value = 'Sila isi ulasan sebelum hantar keputusan ini.';
        actionSuccess.value = '';
        return;
    }

    if (notifyApplicant.value) {
        if (!notificationChannels.value.length) {
            actionError.value = 'Sila pilih sekurang-kurangnya satu channel notifikasi.';
            actionSuccess.value = '';
            return;
        }

        if (!notificationSubject.value.trim() || !notificationMessage.value.trim()) {
            actionError.value = 'Subjek dan mesej notifikasi perlu diisi jika notifikasi diaktifkan.';
            actionSuccess.value = '';
            return;
        }
    }

    actionError.value = '';
    actionSuccess.value = '';
    isSubmitting.value = true;

    router.patch(route('admin.approvals.status', props.application.id), {
        status: selectedDecision.value,
        notes: reviewerRemarks.value.trim() || null,
        send_notification: notifyApplicant.value,
        notification_channels: notificationChannels.value,
        notification_subject: notifyApplicant.value ? notificationSubject.value : null,
        notification_message: notifyApplicant.value ? notificationMessage.value : null,
    }, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            isSubmitting.value = false;
            actionSuccess.value = 'Keputusan berjaya direkodkan.';
            reviewerRemarks.value = '';
        },
        onError: (errors) => {
            isSubmitting.value = false;
            actionError.value = Object.values(errors || {})[0] || 'Tindakan gagal disimpan. Sila cuba lagi.';
        },
    });
};

const printPage = () => {
    window.print();
};
</script>

<template>
    <Head title="Semakan Permohonan" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Semakan Permohonan</h2>
                <Link :href="route('admin.approvals.index')" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Kembali ke Senarai
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">No Permohonan</p>
                            <h3 class="mt-1 text-2xl font-bold text-slate-900">{{ application.reference_no }}</h3>
                            <div class="mt-2 grid gap-1 text-sm text-slate-600 sm:grid-cols-3 sm:gap-3">
                                <p><span class="font-medium text-slate-700">Nama:</span> {{ application.user?.name || 'Tidak Dinyatakan' }}</p>
                                <p><span class="font-medium text-slate-700">ID Ahli:</span> {{ application.user?.member_no || `AHLI-${application.user_id}` }}</p>
                                <p>
                                    <span class="font-medium text-slate-700">Kategori:</span>
                                    {{ Array.isArray(application.category_tags) && application.category_tags.length ? application.category_tags.join(', ') : 'Umum' }}
                                </p>
                            </div>
                        </div>

                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1" :class="statusBadgeClass">
                            {{ statusTextMap[currentStatus] || currentStatus }}
                        </span>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-lg font-semibold text-slate-900">Maklumat Permohonan</h3>
                        <div class="flex items-center gap-2 print:hidden">
                            <a
                                :href="route('applications.pdf', application.id)"
                                target="_blank"
                                class="rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                            >
                                Muat Turun PDF Rasmi
                            </a>
                            <button
                                type="button"
                                class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500"
                                @click="printPage"
                            >
                                Cetak / Simpan PDF
                            </button>
                        </div>
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Rujukan ini adalah bacaan sahaja untuk tujuan semakan jawatankuasa.</p>

                    <div v-if="!submittedFormSections.length" class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
                        Data borang tidak tersedia.
                    </div>

                    <div v-else class="mt-4 space-y-3">
                        <section
                            v-for="section in submittedFormSections"
                            :key="section.key"
                            class="rounded-xl border border-slate-200 p-4"
                        >
                            <p class="text-sm font-semibold text-slate-800">{{ section.title }}</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <article
                                    v-for="row in section.rows"
                                    :key="`${section.key}-${row.label}`"
                                    class="rounded-lg border border-slate-200 bg-slate-50 p-3"
                                >
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ row.label }}</p>
                                    <p class="mt-1 break-words text-sm font-medium text-slate-800">{{ row.value }}</p>
                                </article>
                            </div>
                        </section>
                    </div>
                </section>

                <section class="rounded-2xl border-t-4 border-indigo-500 bg-gray-50 p-6 shadow-sm ring-1 ring-slate-200">
                    <h3 class="text-lg font-semibold text-slate-900">Keputusan Semakan</h3>
                    <p class="mt-1 text-sm text-slate-600">Pilih satu keputusan yang paling tepat untuk permohonan ini.</p>

                    <div v-if="actionSuccess" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ actionSuccess }}
                    </div>
                    <div v-if="actionError" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                        {{ actionError }}
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        <button
                            type="button"
                            class="rounded-xl border p-4 text-left transition"
                            :class="decisionCardClass('approved')"
                            :disabled="isSubmitting || !canSelectDecision('approved')"
                            @click="canSelectDecision('approved') ? selectedDecision = 'approved' : null"
                        >
                            <p class="text-sm font-semibold text-slate-900">Luluskan Permohonan</p>
                            <p class="mt-1 text-xs text-slate-600">Permohonan akan diteruskan ke transaksi superadmin.</p>
                        </button>

                        <button
                            type="button"
                            class="rounded-xl border p-4 text-left transition"
                            :class="decisionCardClass('rejected')"
                            :disabled="isSubmitting || !canSelectDecision('rejected')"
                            @click="canSelectDecision('rejected') ? selectedDecision = 'rejected' : null"
                        >
                            <p class="text-sm font-semibold text-slate-900">Tolak Permohonan</p>
                            <p class="mt-1 text-xs text-slate-600">Permohonan akan ditutup sebagai tidak lulus.</p>
                        </button>

                        <button
                            type="button"
                            class="rounded-xl border p-4 text-left transition"
                            :class="decisionCardClass('kuiri')"
                            :disabled="isSubmitting || !canSelectDecision('kuiri')"
                            @click="selectedDecision = 'kuiri'"
                        >
                            <p class="text-sm font-semibold text-slate-900">Kembalikan Untuk Kuiri</p>
                            <p class="mt-1 text-xs text-slate-600">Pemohon perlu kemaskini dokumen/maklumat.</p>
                        </button>
                    </div>

                    <p v-if="!isSuperAdmin" class="mt-3 rounded-lg bg-slate-100 px-3 py-2 text-xs text-slate-600">
                        Nota polisi: Pegawai penyemak tidak boleh tukar <strong>approved</strong> kepada <strong>rejected</strong> atau sebaliknya. Status <strong>kuiri</strong> boleh diputuskan semula ke approved/rejected selepas semakan lanjut.
                    </p>
                    <p class="mt-3 rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-700 ring-1 ring-blue-200">
                        Peringatan: Untuk kes tidak lengkap, pilih <strong>Kuiri</strong> dahulu. Elakkan pilih keputusan akhir tanpa semakan dokumen penuh. Jika perlu ubah keputusan akhir, escalate kepada superadmin.
                    </p>

                    <div class="mt-4">
                        <label for="reviewer_remarks" class="block text-sm font-medium text-slate-700">
                            Ulasan Pegawai {{ requireRemarks ? '(Wajib)' : '(Pilihan)' }}
                        </label>
                        <textarea
                            id="reviewer_remarks"
                            v-model="reviewerRemarks"
                            rows="4"
                            placeholder="Contoh: Dokumen pendapatan tidak lengkap. Sila muat naik salinan terbaru."
                            class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <p class="mt-1 text-xs text-slate-500">Keputusan dipilih: <strong>{{ decisionLabelMap[selectedDecision] }}</strong>. {{ decisionHintMap[selectedDecision] }}</p>
                    </div>

                    <div class="mt-4 rounded-xl border border-indigo-200 bg-white p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900">Notifikasi Kepada Pemohon (Template Boleh Ubah)</p>
                            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                                <input v-model="notifyApplicant" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                Hantar notifikasi bersama keputusan ini
                            </label>
                        </div>

                        <p class="mt-1 text-xs text-slate-600">
                            Nama pemohon, rujukan dan kategori sudah dipadankan: <strong>{{ applicantName }}</strong> · <strong>{{ application.reference_no }}</strong> · <strong>{{ primaryCategory }}</strong>
                        </p>

                        <div v-if="notifyApplicant" class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Channel</label>
                                <div class="flex h-[42px] items-center gap-4 rounded-lg border border-slate-300 bg-white px-3">
                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                                        <input v-model="notificationChannels" type="checkbox" value="mail" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        Emel
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                                        <input v-model="notificationChannels" type="checkbox" value="database" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        Dashboard
                                    </label>
                                </div>
                            </div>
                            <div class="flex items-end justify-start md:justify-end">
                                <button
                                    type="button"
                                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                    @click="notificationTemplateTouched = false; applyTemplateDraft()"
                                >
                                    Reset Template Auto
                                </button>
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Subjek Notifikasi</label>
                                <input
                                    v-model="notificationSubject"
                                    type="text"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @input="notificationTemplateTouched = true"
                                >
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Mesej Notifikasi</label>
                                <textarea
                                    v-model="notificationMessage"
                                    rows="4"
                                    class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @input="notificationTemplateTouched = true"
                                ></textarea>
                                <p class="mt-1 text-[11px] text-slate-500">Pratonton akhir akan dihantar kepada pemohon selepas keputusan disimpan.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap justify-end gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="isSubmitting"
                            @click="submitDecision"
                        >
                            {{ isSubmitting ? 'Menyimpan keputusan...' : 'Simpan Keputusan' }}
                        </button>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <h3 class="text-lg font-semibold text-slate-900">Rekod Tindakan</h3>
                    <p class="mt-1 text-sm text-slate-500">Jejak audit tindakan untuk semakan dalaman.</p>

                    <div class="mt-4 space-y-3">
                        <article
                            v-for="history in statusHistory"
                            :key="history.id"
                            class="rounded-xl border border-slate-200 bg-white p-4"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-semibold text-slate-900">{{ history.label }}</p>
                                <span v-if="history.isOverride" class="rounded-full border border-fuchsia-300 bg-fuchsia-700 px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm">
                                    🛡 Override Superadmin
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-slate-600">{{ history.at }} · Oleh {{ history.actor }}</p>
                            <p class="mt-2 text-sm text-slate-700">Ulasan: {{ history.remarks }}</p>
                        </article>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
