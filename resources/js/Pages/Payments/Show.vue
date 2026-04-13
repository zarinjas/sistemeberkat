<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    application: {
        type: Object,
        required: true,
    },
    submittedForm: {
        type: Object,
        default: () => ({ sections: [] }),
    },
    canManagePayments: {
        type: Boolean,
        default: false,
    },
});

const form = reactive({
    paid_amount: props.application.paid_amount ?? props.application.requested_amount ?? '',
    transaction_ref: props.application.transaction_ref ?? '',
    paid_at: props.application.paid_at_input ?? '',
    payment_receipt: null,
    notes: '',
});

const prepare = () => {
    router.patch(route('admin.payments.prepare', props.application.id), {
        paid_amount: form.paid_amount,
        transaction_ref: form.transaction_ref,
        paid_at: form.paid_at,
        payment_receipt: form.payment_receipt,
        notes: form.notes,
    }, {
        forceFormData: true,
        preserveScroll: true,
    });
};

const disburse = () => {
    const payload = {
        paid_amount: form.paid_amount,
        transaction_ref: form.transaction_ref,
        paid_at: form.paid_at,
        notes: form.notes,
    };

    if (form.payment_receipt) {
        payload.payment_receipt = form.payment_receipt;
    }

    router.patch(route('admin.payments.disburse', props.application.id), payload, {
        forceFormData: true,
        preserveScroll: true,
    });
};

const onReceiptChange = (event) => {
    form.payment_receipt = event.target.files?.[0] || null;
};

const statusBadgeClass = (status) => {
    if (status === 'approved') {
        return 'bg-blue-100 text-blue-700';
    }

    if (status === 'disbursed') {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (status === 'rejected') {
        return 'bg-rose-100 text-rose-700';
    }

    if (status === 'under_review') {
        return 'bg-amber-100 text-amber-700';
    }

    if (status === 'kuiri') {
        return 'bg-orange-100 text-orange-700';
    }

    return 'bg-slate-100 text-slate-700';
};
</script>

<template>
    <Head :title="`Detail Bayaran - ${application.reference_no}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Detail Rekod Transaksi</h2>
                <Link :href="route('admin.payments.index')" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Kembali Ke Senarai
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <article class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">No Permohonan</p>
                            <p class="mt-1 text-base font-bold text-slate-900">{{ application.reference_no }}</p>
                        </article>
                        <article class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pemohon</p>
                            <p class="mt-1 text-base font-bold text-slate-900">{{ application.applicant_name }}</p>
                            <p class="text-xs text-slate-500">{{ application.applicant_email || '-' }}</p>
                        </article>
                        <article class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
                            <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusBadgeClass(application.status)">
                                {{ application.status }}
                            </span>
                            <p class="mt-2 text-xs text-slate-500">Kategori: {{ application.category }}</p>
                        </article>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-base font-semibold text-slate-900">Paparan Borang Permohonan</h3>
                        <a
                            :href="route('applications.pdf', application.id)"
                            target="_blank"
                            class="rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                        >
                            Muat Turun PDF Rasmi
                        </a>
                    </div>
                    <div v-if="!submittedForm?.sections?.length" class="mt-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-3 text-sm text-slate-500">
                        Data borang tidak tersedia.
                    </div>
                    <div v-else class="mt-3 space-y-3">
                        <section
                            v-for="section in submittedForm.sections"
                            :key="section.key"
                            class="rounded-lg border border-slate-200 p-3"
                        >
                            <p class="text-sm font-semibold text-slate-800">{{ section.title }}</p>
                            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                <article v-for="row in section.rows" :key="`${section.key}-${row.label}`" class="rounded bg-slate-50 p-2.5">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ row.label }}</p>
                                    <p class="mt-1 text-sm text-slate-800">{{ row.value }}</p>
                                </article>
                            </div>
                        </section>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <h3 class="text-base font-semibold text-slate-900">Maklumat Transaksi</h3>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Jumlah Bayaran (RM)</label>
                            <input
                                v-model="form.paid_amount"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :disabled="!canManagePayments"
                            >
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Rujukan Transaksi</label>
                            <input
                                v-model="form.transaction_ref"
                                type="text"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :disabled="!canManagePayments"
                            >
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Tarikh Bayaran</label>
                            <input
                                v-model="form.paid_at"
                                type="datetime-local"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :disabled="!canManagePayments"
                            >
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Resit Bayaran (jpg/png/pdf)</label>
                            <input
                                type="file"
                                accept=".jpg,.jpeg,.png,.pdf"
                                class="w-full rounded-lg border border-slate-300 bg-white p-2 text-xs text-slate-700"
                                :disabled="!canManagePayments"
                                @change="onReceiptChange"
                            >
                            <a
                                v-if="application.payment_receipt_url"
                                :href="application.payment_receipt_url"
                                target="_blank"
                                class="mt-2 inline-flex text-xs font-semibold text-indigo-600 hover:text-indigo-500"
                            >
                                Lihat Resit Semasa
                            </a>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Nota</label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            :disabled="!canManagePayments"
                        ></textarea>
                    </div>

                    <div v-if="canManagePayments" class="mt-5 flex flex-wrap gap-2">
                        <button
                            v-if="application.can_prepare"
                            type="button"
                            class="rounded-lg bg-slate-800 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700"
                            @click="prepare"
                        >
                            Rekod Transaksi
                        </button>
                        <button
                            v-else-if="application.can_disburse"
                            type="button"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-500"
                            @click="disburse"
                        >
                            Sahkan & Salurkan
                        </button>
                        <span v-else class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-medium text-slate-600">Tiada tindakan tersedia untuk status semasa.</span>
                    </div>

                    <p v-else class="mt-5 rounded-xl bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700 ring-1 ring-amber-200">
                        Pegawai penyemak hanya boleh lihat rekod. Tindakan transaksi hanya untuk superadmin.
                    </p>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <h3 class="text-base font-semibold text-slate-900">Jejak Audit Ringkas</h3>
                    <div class="mt-4 space-y-2">
                        <div v-if="!application.status_histories?.length" class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-500">
                            Tiada rekod sejarah status.
                        </div>
                        <article
                            v-for="entry in application.status_histories"
                            :key="entry.id"
                            class="rounded-lg border border-slate-200 p-3"
                        >
                            <p class="text-sm font-semibold text-slate-900">{{ entry.from_status || '-' }} → {{ entry.to_status || '-' }}</p>
                            <p class="mt-1 text-xs text-slate-600">{{ entry.changed_at }} · Oleh {{ entry.changed_by }}</p>
                            <p v-if="entry.notes" class="mt-2 text-xs text-slate-700">{{ entry.notes }}</p>
                        </article>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
