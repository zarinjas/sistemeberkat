<script setup>
import { usePage } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import AdminDashboard from './Dashboard/Admin.vue';
import ApplicantDashboard from './Dashboard/Applicant.vue';
import { computed } from 'vue';

const page = usePage();
const props = defineProps({
    kpiCards: {
        type: Array,
        default: () => [],
    },
    urgentQueue: {
        type: Array,
        default: () => [],
    },
    generalQueue: {
        type: Array,
        default: () => [],
    },
    applications: {
        type: Array,
        default: () => [],
    },
    availableForms: {
        type: Array,
        default: () => [],
    },
    dashboardPosters: {
        type: Array,
        default: () => [],
    },
});

const isAdmin = computed(() => {
    const userRole = page.props.auth?.user?.role;
    return userRole === 'admin';
});

const dashboardTitle = computed(() => isAdmin.value ? 'Command Center Pengurusan' : 'Dashboard Ahli');
</script>

<template>
    <Head :title="dashboardTitle" />
    <component
        :is="isAdmin ? AdminDashboard : ApplicantDashboard"
        :kpi-cards="kpiCards"
        :urgent-queue="urgentQueue"
        :general-queue="generalQueue"
        :applications="applications"
        :available-forms="availableForms"
        :dashboard-posters="dashboardPosters"
    />
</template>
