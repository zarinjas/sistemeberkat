<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
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

const page = usePage();

const applicantName = computed(() => page.props.auth?.user?.name || 'Ahli BERKAT');
const bentoCardClass = 'surface-card';
const sectionTitleClass = 'section-title';
const currentPosterSlide = ref(0);
const viewportWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1280);
let posterInterval = null;

const dummyApplications = [
    {
        id: 101,
        reference_no: 'BERKAT-20260324-A1K9',
        category: 'Kesihatan',
        created_at: '24 Mac 2026',
        status: 'under_review',
    },
    {
        id: 102,
        reference_no: 'BERKAT-20260320-K7H2',
        category: 'Pendidikan',
        created_at: '20 Mac 2026',
        status: 'submitted',
    },
    {
        id: 103,
        reference_no: 'BERKAT-20260318-W3M5',
        category: 'Kebajikan',
        created_at: '18 Mac 2026',
        status: 'approved',
    },
];

const availableForms = computed(() => {
    if (!Array.isArray(props.availableForms)) {
        return [];
    }

    return props.availableForms
        .filter((form) => form && typeof form === 'object')
        .map((form, index) => ({
            id: form.id ?? `form-${index}`,
            title: form.title ?? 'Borang Bantuan',
            description: form.description ?? 'Tiada keterangan disediakan.',
            status: form.status ?? 'Aktif',
        }));
});

const activePosters = computed(() => {
    if (!Array.isArray(props.dashboardPosters)) {
        return [];
    }

    return props.dashboardPosters.filter((poster) => poster && poster.image_url);
});

const postersPerView = computed(() => (viewportWidth.value >= 1024 ? 2 : 1));

const posterSlides = computed(() => {
    const slides = [];
    const perView = postersPerView.value;

    for (let index = 0; index < activePosters.value.length; index += perView) {
        slides.push(activePosters.value.slice(index, index + perView));
    }

    return slides;
});

const totalPosterSlides = computed(() => posterSlides.value.length);

const currentPosterItems = computed(() => {
    if (!totalPosterSlides.value) {
        return [];
    }

    return posterSlides.value[currentPosterSlide.value] ?? posterSlides.value[0];
});

const goToPosterSlide = (index) => {
    if (!totalPosterSlides.value) {
        return;
    }

    currentPosterSlide.value = index;
};

const nextPosterSlide = () => {
    if (!totalPosterSlides.value) {
        return;
    }

    currentPosterSlide.value = (currentPosterSlide.value + 1) % totalPosterSlides.value;
};

const handleResize = () => {
    viewportWidth.value = window.innerWidth;

    if (currentPosterSlide.value >= totalPosterSlides.value) {
        currentPosterSlide.value = 0;
    }
};

onMounted(() => {
    window.addEventListener('resize', handleResize);

    posterInterval = setInterval(() => {
        nextPosterSlide();
    }, 4000);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize);

    if (posterInterval) {
        clearInterval(posterInterval);
    }
});

const recentApplications = computed(() => {
    const sourceApplications = Array.isArray(props.applications) && props.applications.length
        ? props.applications
        : dummyApplications;

    return sourceApplications
        .filter((application) => application && typeof application === 'object')
        .map((application, index) => ({
            id: application.id ?? `application-${index}`,
            reference_no: application.reference_no ?? application.referenceNo ?? null,
            category: application.category ?? null,
            created_at: application.created_at ?? application.createdAt ?? '-',
            status: application.status ?? 'draft',
        }));
});

const timelineSteps = ['draft', 'submitted', 'under_review', 'approved'];

const statusOrder = {
    draft: 0,
    submitted: 1,
    under_review: 2,
    approved: 3,
    disbursed: 3,
    rejected: 2,
};

const statusText = {
    draft: 'Draft',
    submitted: 'Submitted',
    under_review: 'Under Review',
    approved: 'Approved',
    disbursed: 'Approved',
    rejected: 'Rejected',
};

const stepIsCompleted = (currentStatus, stepIndex) => {
    const currentOrder = statusOrder[currentStatus] ?? 0;

    return currentOrder >= stepIndex;
};

const statusBadgeClass = (status) => {
    if (status === 'approved' || status === 'disbursed') {
        return 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200';
    }

    if (status === 'under_review') {
        return 'bg-amber-100 text-amber-700 ring-1 ring-amber-200';
    }

    if (status === 'submitted') {
        return 'bg-blue-100 text-blue-700 ring-1 ring-blue-200';
    }

    if (status === 'rejected') {
        return 'bg-rose-100 text-rose-700 ring-1 ring-rose-200';
    }

    return 'bg-slate-100 text-slate-600 ring-1 ring-slate-200';
};

const openForm = (formId) => {
    if (!formId) {
        return;
    }

    router.get(route('applications.create'), { form_id: formId });
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Dashboard Ahli
            </h2>
        </template>

        <div class="mx-auto max-w-7xl">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3 lg:grid-cols-4">
                <section class="col-span-1 row-span-1 rounded-3xl border border-indigo-100 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm transition hover:shadow-md md:col-span-2 lg:col-span-3">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Portal Ahli BERKAT</p>
                            <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Selamat Datang, {{ applicantName }}</h3>
                            <p class="mt-2 text-sm text-slate-600">Ruang eksklusif untuk semak status permohonan dan urus bantuan anda dengan pantas.</p>
                        </div>

                            <div class="surface-card-soft border-indigo-100 bg-white/70 backdrop-blur-sm">
                            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Document Wallet Status</p>
                            <p class="mt-1 text-sm font-medium text-slate-700">Kad Ahli Digital tersedia untuk semakan.</p>
                            <Link :href="route('membership-card')" class="mt-2 inline-flex text-sm font-semibold text-indigo-700 hover:text-indigo-900">
                                Lihat Kad Ahli →
                            </Link>
                        </div>
                    </div>
                </section>

                <section class="col-span-1 rounded-3xl border border-blue-500 bg-blue-600 p-6 text-white shadow-sm transition hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-100">Quick Action</p>
                    <h3 class="mt-2 text-xl font-bold">Mohon Bantuan Baharu</h3>
                    <p class="mt-2 text-sm text-blue-100">Mulakan permohonan dalam beberapa langkah ringkas.</p>
                    <button
                        type="button"
                        class="mt-5 inline-flex rounded-xl bg-white px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-50"
                        @click="openForm(availableForms[0]?.id)"
                    >
                        Mohon Sekarang
                    </button>
                </section>

                <section :class="`${bentoCardClass} col-span-1 md:col-span-1`">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 :class="sectionTitleClass">Info Terkini</h3>
                        <span class="text-xs font-medium text-slate-500">1:1</span>
                    </div>

                    <div v-if="!currentPosterItems.length" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-center text-sm text-slate-500">
                        Tiada info terkini tersedia.
                    </div>

                    <div v-else>
                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                            <article v-for="poster in currentPosterItems" :key="poster.id">
                                <div class="relative aspect-square overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                    <img
                                        :src="poster.image_url"
                                        :alt="poster.title"
                                        class="h-full w-full object-cover"
                                    >
                                </div>
                                <p class="mt-2 line-clamp-2 text-sm font-semibold text-slate-900">{{ poster.title }}</p>
                            </article>
                        </div>

                        <div v-if="totalPosterSlides > 1" class="mt-3 flex items-center justify-center gap-1.5">
                            <button
                                v-for="(_, index) in posterSlides"
                                :key="`poster-slide-${index}`"
                                type="button"
                                class="h-2.5 w-2.5 rounded-full"
                                :class="currentPosterSlide === index ? 'bg-indigo-600' : 'bg-slate-300 hover:bg-slate-400'"
                                @click="goToPosterSlide(index)"
                            />
                        </div>
                    </div>
                </section>

                <section :class="`${bentoCardClass} col-span-1 md:col-span-2 lg:col-span-3`">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 :class="sectionTitleClass">Borang Tersedia</h3>
                        <span class="text-xs font-medium text-slate-500">{{ availableForms.length }} borang</span>
                    </div>

                    <div v-if="!availableForms.length" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-center text-sm text-slate-500">
                        Tiada borang permohonan tersedia buat masa ini.
                    </div>

                    <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <article
                            v-for="form in availableForms"
                            :key="form.id"
                            class="surface-card-soft"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <h4 class="text-sm font-semibold text-slate-900">{{ form.title }}</h4>
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">{{ form.status }}</span>
                            </div>
                            <p class="mt-2 line-clamp-2 text-xs text-slate-600">{{ form.description }}</p>
                            <button
                                type="button"
                                class="mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                @click="openForm(form.id)"
                            >
                                Buka Borang
                            </button>
                        </article>
                    </div>
                </section>

                <section :class="`${bentoCardClass} col-span-1 md:col-span-3 lg:col-span-4`">
                    <div class="mb-5 flex items-center justify-between">
                        <h3 :class="sectionTitleClass">Timeline Permohonan</h3>
                        <Link :href="route('applications.index')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Lihat semua</Link>
                    </div>

                    <div class="space-y-4">
                        <article v-for="application in recentApplications" :key="application.id" class="surface-card-soft">
                            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ application.reference_no || `Permohonan #${application.id}` }}</p>
                                    <p class="text-xs text-slate-500">{{ application.category || 'Kategori Umum' }} • {{ application.created_at }}</p>
                                </div>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(application.status)">
                                    {{ statusText[application.status] || application.status }}
                                </span>
                            </div>

                            <div class="space-y-2">
                                <div v-for="(step, index) in timelineSteps" :key="step" class="flex items-center gap-3">
                                    <span
                                        class="h-2.5 w-2.5 rounded-full"
                                        :class="stepIsCompleted(application.status, index) ? 'bg-indigo-500' : 'bg-slate-300'"
                                    ></span>
                                    <p
                                        class="text-xs font-medium"
                                        :class="stepIsCompleted(application.status, index) ? 'text-slate-700' : 'text-slate-400'"
                                    >
                                        {{ statusText[step] }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
