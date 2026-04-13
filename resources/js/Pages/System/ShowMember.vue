<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DigitalCard from '@/Components/DigitalCard.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    member: {
        type: Object,
        required: true,
    },
    canEditMember: {
        type: Boolean,
        default: false,
    },
    canViewMemberCard: {
        type: Boolean,
        default: false,
    },
});

const cardPayload = computed(() => ({
    brandName: 'Ahli e-BERKAT',
    statusLabel: 'STATUS',
    statusValue: props.member.first_login_completed ? 'Aktif' : 'Belum Aktif',
    name: props.member.name,
    membershipId: props.member.member_no || `BERKAT-${props.member.id}`,
    phone: props.member.phone || '-',
    jobTitle: props.member.job_title || '-',
    state: props.member.state || '-',
    department: props.member.department || '-',
    avatarUrl: props.member.profile_photo_url || 'https://via.placeholder.com/96x96.png?text=' + encodeURIComponent((props.member.name || 'A').charAt(0)),
    qrData: `MECARD:N:${props.member.name || ''};TEL:${props.member.phone || ''};EMAIL:${props.member.email || ''};NOTE:Member ID ${props.member.member_no || ''};;`,
}));
</script>

<template>
    <Head :title="`Detail Ahli: ${member.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Detail Ahli BERKAT</h2>
        </template>

        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ member.name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">No. Ahli: {{ member.member_no || '-' }} | No. IC: {{ member.nric || '-' }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="member.first_login_completed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                        >
                            {{ member.first_login_completed ? 'Aktif' : 'Belum Aktif' }}
                        </span>
                        <span
                            v-if="canEditMember"
                            class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700"
                        >
                            Superadmin Access
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Emel</p><p class="mt-1 font-medium text-slate-900">{{ member.email || '-' }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Telefon</p><p class="mt-1 font-medium text-slate-900">{{ member.phone || '-' }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Cawangan</p><p class="mt-1 font-medium text-slate-900">{{ member.branch || '-' }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Jawatan</p><p class="mt-1 font-medium text-slate-900">{{ member.job_title || '-' }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Jabatan</p><p class="mt-1 font-medium text-slate-900">{{ member.department || '-' }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Negeri</p><p class="mt-1 font-medium text-slate-900">{{ member.state || '-' }}</p></div>
                    <div class="rounded-lg bg-slate-50 p-3 md:col-span-2 lg:col-span-3"><p class="text-xs text-slate-500">Alamat</p><p class="mt-1 font-medium text-slate-900">{{ member.address || '-' }}</p></div>
                </div>
            </section>

            <section v-if="canViewMemberCard" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <h3 class="text-lg font-semibold text-slate-900">Kad Ahli Digital</h3>
                <p class="mt-1 text-sm text-slate-500">Paparan kad ahli hanya untuk Superadmin.</p>
                <div class="mt-4">
                    <DigitalCard :member="cardPayload" />
                </div>
            </section>

            <section v-else class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                Paparan kad ahli dihadkan kepada Superadmin sahaja.
            </section>

            <div>
                <Link
                    :href="route('admin.system.index')"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Kembali ke Pengurusan Ahli BERKAT
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
