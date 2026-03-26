<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    applications: {
        type: Object,
        default: () => ({ data: [] }),
    },
});

const paymentInputs = reactive(
    (props.applications?.data || []).reduce((carry, item) => {
        carry[item.id] = {
            paid_amount: item.paid_amount ?? item.requested_amount ?? '',
            transaction_ref: item.transaction_ref ?? '',
            paid_at: item.paid_at_input ?? '',
            notes: '',
        };

        return carry;
    }, {}),
);

const disburse = (applicationId) => {
    const input = paymentInputs[applicationId] || {};

    router.patch(route('admin.payments.disburse', applicationId), {
        paid_amount: input.paid_amount,
        transaction_ref: input.transaction_ref,
        paid_at: input.paid_at,
        notes: input.notes,
    }, {
        preserveScroll: true,
    });
};

const prepare = (applicationId) => {
    const input = paymentInputs[applicationId] || {};

    router.patch(route('admin.payments.prepare', applicationId), {
        paid_amount: input.paid_amount,
        transaction_ref: input.transaction_ref,
        paid_at: input.paid_at,
        notes: input.notes,
    }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Modul Bayaran Permohonan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Modul Bayaran Permohonan</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Senarai Permohonan Untuk Bayaran</h3>
                            <p class="mt-1 text-sm text-slate-500">Arahan Admin (BM): Ikut langkah Maker → Checker. Pengguna yang buat Maker tidak boleh buat Checker bagi rekod yang sama.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Link
                                :href="route('admin.payments.export')"
                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Muat Turun CSV Bayaran
                            </Link>
                            <Link
                                :href="route('admin.audit.index')"
                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Lihat Audit Operasi
                            </Link>
                        </div>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Rujukan</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Pemohon</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Jumlah Bayaran</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Rujukan Transaksi</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Tarikh Bayaran</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Nota Maker/Checker</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Maker</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Checker</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-if="!applications?.data?.length">
                                    <td colspan="10" class="px-4 py-6 text-center text-slate-500">Tiada rekod bayaran.</td>
                                </tr>
                                <tr v-for="item in applications.data || []" :key="item.id">
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ item.reference_no }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ item.applicant_name }}</td>
                                    <td class="px-4 py-3">
                                        <input
                                            v-model="paymentInputs[item.id].paid_amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="w-36 rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="0.00"
                                        >
                                    </td>
                                    <td class="px-4 py-3">
                                        <input
                                            v-model="paymentInputs[item.id].transaction_ref"
                                            type="text"
                                            class="w-44 rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Contoh: FPX-2026-0001"
                                        >
                                    </td>
                                    <td class="px-4 py-3">
                                        <input
                                            v-model="paymentInputs[item.id].paid_at"
                                            type="datetime-local"
                                            class="rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </td>
                                    <td class="px-4 py-3">
                                        <input
                                            v-model="paymentInputs[item.id].notes"
                                            type="text"
                                            class="w-56 rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Catatan ringkas untuk audit"
                                        >
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="item.status === 'disbursed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                        >
                                            {{ item.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-700">
                                        <p>{{ item.payment_prepared_by || '-' }}</p>
                                        <p class="text-slate-500">{{ item.payment_prepared_at }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-700">
                                        <p>{{ item.payment_approved_by || '-' }}</p>
                                        <p class="text-slate-500">{{ item.payment_approved_at }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-2">
                                            <button
                                                type="button"
                                                class="rounded-lg bg-slate-700 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-600 disabled:cursor-not-allowed disabled:bg-slate-300"
                                                :disabled="!item.can_prepare"
                                                @click="prepare(item.id)"
                                            >
                                                Simpan Maker
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-300"
                                                :disabled="!item.can_disburse"
                                                @click="disburse(item.id)"
                                            >
                                                Sahkan Checker & Bayar
                                            </button>
                                        </div>
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
