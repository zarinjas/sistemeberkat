<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DigitalCard from '@/Components/DigitalCard.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const page = usePage();

const member = computed(() => ({
    brandName: 'Ahli e-BERKAT',
    statusLabel: 'STATUS',
    statusValue: 'Aktif',
    name: props.user.name,
    membershipId: props.user.member_no || 'BERKAT-' + (props.user.id || '0000000'),
    phone: props.user.phone || '-',
    jobTitle: props.user.job_title || '-',
    state: props.user.state || '-',
    department: props.user.department || '-',
    logoUrl: page.props.branding?.logo_url || null,
    avatarUrl: props.user.profile_photo_url ||
        'https://via.placeholder.com/96x96.png?text=' +
        encodeURIComponent((props.user.name || 'A').charAt(0)),
    qrData: `MECARD:N:${props.user.name || ''};TEL:${props.user.phone || ''};EMAIL:${props.user.email || ''};NOTE:Member ID ${props.user.member_no || ''};;`,
}));
</script>

<template>
    <Head title="Kad Ahli Digital" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Kad Ahli Digital BERKAT
            </h2>
        </template>

        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <DigitalCard :member="member" />

                <div class="text-center">
                    <Link
                        :href="route('dashboard')"
                        class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        ← Kembali ke Dashboard
                    </Link>
                </div>
        </div>
    </AuthenticatedLayout>
</template>
