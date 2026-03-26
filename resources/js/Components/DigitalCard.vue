<script setup>
import { computed, ref, watch } from 'vue';
import QRCode from 'qrcode';

const props = defineProps({
    member: {
        type: Object,
        default: () => ({}),
    },
});

const defaultMember = {
    brandName: 'Ahli e-BERKAT',
    statusLabel: 'STATUS',
    statusValue: 'Aktif',
    name: 'Applicant Demo',
    membershipId: 'BERKAT-6489722',
    jobTitle: 'Eksekutif',
    phone: '012-3456789',
    state: 'Selangor',
    department: 'Operasi',
    logoUrl: null,
    avatarUrl: 'https://via.placeholder.com/120x120.png?text=AD',
    qrData: 'MECARD:N:Applicant Demo;TEL:0123456789;NOTE:BERKAT-6489722;;',
};

const card = computed(() => ({
    ...defaultMember,
    ...(props.member ?? {}),
}));

const qrImageUrl = ref('');

const buildQrCode = async (value) => {
    try {
        qrImageUrl.value = await QRCode.toDataURL(value || defaultMember.qrData, {
            width: 280,
            margin: 1,
            color: {
                dark: '#111827',
                light: '#FFFFFF',
            },
        });
    } catch {
        qrImageUrl.value = '';
    }
};

watch(
    () => card.value.qrData,
    (value) => {
        buildQrCode(value);
    },
    { immediate: true },
);
</script>

<template>
    <div class="w-full space-y-4">
        <div class="mx-auto max-w-sm md:max-w-4xl">
            <div class="flex flex-col rounded-3xl bg-gradient-to-br from-slate-800 to-blue-900 p-6 text-white shadow-2xl md:flex-row md:p-8">
                <div class="md:w-2/3 md:pr-8">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-md bg-white p-1">
                                <img
                                    v-if="card.logoUrl"
                                    :src="card.logoUrl"
                                    alt="Brand Logo"
                                    class="h-full w-full rounded object-contain"
                                />
                                <div v-else class="flex h-full w-full items-center justify-center rounded bg-slate-200 text-[10px] font-bold text-slate-700">
                                    LOGO
                                </div>
                            </div>
                            <p class="text-sm font-semibold">
                                {{ card.brandName }}
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/70">
                                {{ card.statusLabel }}
                            </p>
                            <p class="text-sm font-bold text-emerald-300">
                                {{ card.statusValue }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/70">
                                MEMBER'S NAME
                            </p>
                            <p class="mt-1 text-2xl font-bold leading-tight">
                                {{ card.name }}
                            </p>
                        </div>

                        <img
                            :src="card.avatarUrl"
                            alt="Avatar"
                            class="h-16 w-16 rounded-full border border-white/40 object-cover md:h-20 md:w-20"
                        />
                    </div>

                    <div class="mt-5 border-t border-white/20 pt-4">
                        <dl class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/70">
                                    JAWATAN
                                </dt>
                                <dd class="mt-1 font-semibold">
                                    {{ card.jobTitle }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/70">
                                    NO TELEFON
                                </dt>
                                <dd class="mt-1 font-semibold">
                                    {{ card.phone }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/70">
                                    NEGERI
                                </dt>
                                <dd class="mt-1 font-semibold">
                                    {{ card.state }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/70">
                                    JABATAN
                                </dt>
                                <dd class="mt-1 font-semibold">
                                    {{ card.department }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-center md:mt-0 md:w-1/3 md:border-l md:border-white/20 md:pl-8">
                    <div class="rounded-xl bg-white p-3">
                        <img
                            v-if="qrImageUrl"
                            :src="qrImageUrl"
                            alt="QR Code"
                            class="h-36 w-36 rounded object-contain"
                        >
                        <div v-else class="flex h-36 w-36 items-center justify-center rounded bg-slate-100 text-xs font-semibold text-slate-500">
                            QR
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-sm md:max-w-4xl">
            <button
                type="button"
                class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50"
            >
                ⬇️ Muat Turun Kad (Simpan ke Telefon)
            </button>
        </div>
    </div>
</template>
