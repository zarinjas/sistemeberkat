<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    application: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user?.name || 'Pegawai Penyemak');

const currentStatus = ref(props.application.status || 'under_review');
const selectedDecision = ref('approved');
const reviewerRemarks = ref('');
const isSubmitting = ref(false);
const actionError = ref('');

const statusTextMap = {
    submitted: 'Dihantar',
    under_review: 'Sedang Disemak',
    approved: 'Diluluskan',
    rejected: 'Digagalkan',
    kuiri: 'Dikuiri',
};

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

const formatLabel = (key) => {
    return String(key)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const readonlyAnswers = computed(() => {
    const dynamicPayload = props.application.dynamic_payload || {};
    const triageAnswers = props.application.triage_answers || {};

    const payloadRows = Object.entries(dynamicPayload).map(([key, value]) => ({
        key: `payload-${key}`,
        label: formatLabel(key),
        value: value === '' || value === null || value === undefined ? '-' : String(value),
    }));

    const triageRows = Object.entries(triageAnswers).map(([key, value]) => ({
        key: `triage-${key}`,
        label: `${formatLabel(key)} (Pengesahan)`,
        value: value === true ? 'Ya' : value === false ? 'Tidak' : String(value ?? '-'),
    }));

    const rows = [...payloadRows, ...triageRows];

    if (rows.length) {
        return rows;
    }

    return [
        {
            key: 'fallback-1',
            label: 'Jenis Bantuan',
            value: 'Bantuan Pendidikan',
        },
        {
            key: 'fallback-2',
            label: 'Nama Institusi',
            value: 'Universiti Malaysia Sabah',
        },
    ];
});

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

const statusHistory = ref(
    Array.isArray(props.application.status_histories) && props.application.status_histories.length
        ? props.application.status_histories.map((entry) => ({
              id: `db-${entry.id}`,
              icon: entry.to_status === 'approved' ? '🟢' : entry.to_status === 'rejected' ? '🔴' : entry.to_status === 'kuiri' ? '🟡' : '🔵',
              label: statusTextMap[entry.to_status] || formatLabel(entry.to_status),
              actor: entry.changed_by?.name || (entry.to_status === 'submitted' ? 'Pemohon' : 'Pegawai'),
              at: formatTimestamp(entry.changed_at),
              remarks: entry.notes || '',
          }))
        : [
              {
                  id: 'seed-1',
                  icon: '🔵',
                  label: 'Dihantar',
                  actor: 'Pemohon',
                  at: formatTimestamp(props.application.submitted_at || new Date().toISOString()),
                  remarks: 'Permohonan baharu dihantar untuk semakan.',
              },
          ],
);

const submitDecision = () => {
    if ((selectedDecision.value === 'rejected' || selectedDecision.value === 'kuiri') && !reviewerRemarks.value.trim()) {
        actionError.value = 'Ulasan ejen wajib diisi jika keputusan Gagal atau Kuiri.';
        return;
    }

    actionError.value = '';
    isSubmitting.value = true;

    router.patch(route('admin.approvals.status', props.application.id), {
        status: selectedDecision.value,
        notes: reviewerRemarks.value.trim() || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
        },
        onError: (errors) => {
            isSubmitting.value = false;
            actionError.value = Object.values(errors || {})[0] || 'Tindakan gagal disimpan. Sila cuba lagi.';
        },
    });
};
</script>

<template>
    <Head title="Semakan Permohonan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Semakan Permohonan (Pegawai Penyemak)</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rujukan Permohonan</p>
                            <h3 class="mt-1 text-2xl font-bold text-slate-900">{{ application.reference_no }}</h3>
                            <div class="mt-2 grid gap-1 text-sm text-slate-600 sm:grid-cols-3 sm:gap-3">
                                <p><span class="font-medium text-slate-700">Nama Pemohon:</span> {{ application.user?.name || 'Tidak Dinyatakan' }}</p>
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
                    <h3 class="text-lg font-semibold text-slate-900">Data Permohonan (Read-Only)</h3>
                    <p class="mt-1 text-sm text-slate-500">Maklumat ini dipaparkan untuk semakan ejen dan tidak boleh diubah di skrin ini.</p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div v-for="item in readonlyAnswers" :key="item.key" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ item.label }}</p>
                            <p class="mt-1 text-sm font-medium text-slate-800 break-words">{{ item.value }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <h3 class="text-lg font-semibold text-slate-900">Sejarah Tindakan</h3>
                    <p class="mt-1 text-sm text-slate-500">Audit trail ini memaparkan siapa yang membuat tindakan dan bila tindakan dibuat.</p>

                    <div class="mt-4 space-y-3">
                        <article
                            v-for="history in statusHistory"
                            :key="history.id"
                            class="rounded-xl border border-slate-200 bg-white p-4"
                        >
                            <p class="text-sm font-semibold text-slate-900">
                                {{ history.icon }} {{ history.label }} - Disemak oleh: {{ history.actor }} pada {{ history.at }}
                            </p>
                            <p class="mt-1 text-sm text-slate-600">Ulasan: {{ history.remarks }}</p>
                        </article>
                    </div>
                </section>

                <section class="rounded-2xl border-t-4 border-blue-500 bg-gray-50 p-6 shadow-sm ring-1 ring-slate-200">
                    <h3 class="text-lg font-semibold text-slate-900">Panel Tindakan Ejen</h3>

                    <div v-if="actionError" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                        {{ actionError }}
                    </div>

                    <div class="mt-4 grid gap-6 lg:grid-cols-2">
                        <div>
                            <p class="text-sm font-medium text-slate-700">Keputusan Semakan</p>
                            <div class="mt-3 space-y-2">
                                <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <input v-model="selectedDecision" type="radio" value="approved" class="border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                    <span class="text-sm text-slate-700">✅ Lulus (Approve)</span>
                                </label>
                                <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <input v-model="selectedDecision" type="radio" value="rejected" class="border-slate-300 text-rose-600 focus:ring-rose-500" />
                                    <span class="text-sm text-slate-700">❌ Gagal (Reject)</span>
                                </label>
                                <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2">
                                    <input v-model="selectedDecision" type="radio" value="kuiri" class="border-slate-300 text-amber-600 focus:ring-amber-500" />
                                    <span class="text-sm text-slate-700">⚠️ Semak Semula / Kuiri</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="agent_remarks" class="block text-sm font-medium text-slate-700">
                                Ulasan / Catatan Ejen (Wajib jika Gagal/Kuiri)
                            </label>
                            <textarea
                                id="agent_remarks"
                                v-model="reviewerRemarks"
                                rows="5"
                                placeholder="Contoh: Dokumen pendapatan tidak jelas, sila kemukakan semula..."
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                            :disabled="isSubmitting"
                            @click="alert('Jana laporan PDF (simulasi).')"
                        >
                            🖨️ Cetak / Jana Laporan PDF
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="isSubmitting"
                            @click="submitDecision"
                        >
                            {{ isSubmitting ? 'Menyimpan...' : 'Sahkan Tindakan' }}
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
