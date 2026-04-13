<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import QRCode from 'qrcode';

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
    announcements: {
        type: Array,
        default: () => [],
    },
    unreadAnnouncementsCount: {
        type: Number,
        default: 0,
    },
});

const page = usePage();

const applicantName = computed(() => page.props.auth?.user?.name || 'Ahli BERKAT');
const memberNo = computed(() => page.props.auth?.user?.member_no || 'BERKAT-MEMBER');
const memberEmail = computed(() => page.props.auth?.user?.email || 'ahli@berkat.my');
const memberPhone = computed(() => page.props.auth?.user?.phone || '-');
const memberJobTitle = computed(() => page.props.auth?.user?.job_title || '-');
const memberDepartment = computed(() => page.props.auth?.user?.department || '-');
const memberState = computed(() => page.props.auth?.user?.state || '-');
const brandLogoUrl = computed(() => page.props.branding?.logo_url || '');
const memberProfilePhotoUrl = computed(() => page.props.auth?.user?.profile_photo_url || '');
const memberCardUrl = computed(() => route('membership-card'));
const memberCardQrUrl = ref('');
const memberInitials = computed(() => {
    const words = String(applicantName.value || '').trim().split(/\s+/).filter(Boolean);

    if (!words.length) {
        return 'AB';
    }

    return words.slice(0, 2).map((word) => word[0]?.toUpperCase() || '').join('');
});
const bentoCardClass = 'surface-card';
const sectionTitleClass = 'section-title';
const currentPosterSlide = ref(0);
const viewportWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1280);
const imagePreviewUrl = ref('');
const imagePreviewTitle = ref('Lampiran notifikasi');
let posterInterval = null;

const closeImagePreview = () => {
    imagePreviewUrl.value = '';
    imagePreviewTitle.value = 'Lampiran notifikasi';
    document.body.style.overflow = '';
};

const openImagePreview = (url, title = 'Lampiran notifikasi') => {
    if (!url) {
        return;
    }

    imagePreviewUrl.value = url;
    imagePreviewTitle.value = title;
    document.body.style.overflow = 'hidden';
};

const handleEscapePreview = (event) => {
    if (event.key === 'Escape') {
        closeImagePreview();
    }
};

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

const postersPerView = computed(() => (viewportWidth.value >= 1024 ? 3 : 1));

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

const buildMemberCardQr = async () => {
    try {
        memberCardQrUrl.value = await QRCode.toDataURL(memberCardUrl.value, {
            width: 180,
            margin: 1,
            color: {
                dark: '#0f172a',
                light: '#ffffff',
            },
        });
    } catch {
        memberCardQrUrl.value = '';
    }
};

onMounted(() => {
    buildMemberCardQr();
    window.addEventListener('resize', handleResize);
    window.addEventListener('keydown', handleEscapePreview);

    posterInterval = setInterval(() => {
        nextPosterSlide();
    }, 4000);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize);
    window.removeEventListener('keydown', handleEscapePreview);
    document.body.style.overflow = '';

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

const announcements = computed(() => {
    if (!Array.isArray(props.announcements)) {
        return [];
    }

    return props.announcements
        .filter((item) => item && typeof item === 'object')
        .map((item, index) => ({
            id: item.id ?? `announcement-${index}`,
            subject: item.subject ?? 'Notifikasi BERKAT',
            message: item.message ?? '-',
            reference_no: item.reference_no ?? null,
            status: item.status ?? null,
            image_url: item.image_url ?? null,
            created_at: item.created_at ?? '-',
            is_read: Boolean(item.is_read),
        }));
});

const unreadAnnouncementsCount = computed(() => {
    if (typeof props.unreadAnnouncementsCount === 'number') {
        return props.unreadAnnouncementsCount;
    }

    return announcements.value.filter((item) => !item.is_read).length;
});

const markAnnouncementRead = (notificationId) => {
    if (!notificationId) {
        return;
    }

    router.post(route('applicant.announcements.read', notificationId), {}, {
        preserveScroll: true,
    });
};

const markAllAnnouncementsRead = () => {
    router.post(route('applicant.announcements.read-all'), {}, {
        preserveScroll: true,
    });
};

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
    in_review: 'In Review',
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
                <section class="col-span-1 row-span-1 rounded-3xl border border-indigo-100 bg-gradient-to-r from-blue-50 to-indigo-50 p-6 shadow-sm transition hover:shadow-md md:col-span-3 lg:col-span-2">
                    <div class="flex h-full flex-col justify-between gap-5">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Portal Ahli BERKAT</p>
                            <h3 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Selamat Datang, {{ applicantName }}</h3>
                            <p class="mt-2 text-sm text-slate-600">Ruang eksklusif untuk semak status permohonan dan urus bantuan anda dengan pantas.</p>
                        </div>
                    </div>
                </section>

                <section class="col-span-1 md:col-span-3 lg:col-span-2">
                    <Link :href="route('membership-card')" class="group block">
                        <article class="relative w-full overflow-hidden rounded-[24px] border border-indigo-200 bg-gradient-to-br from-indigo-600 via-sky-600 to-cyan-500 p-5 text-white shadow-md transition duration-200 hover:-translate-y-1 hover:shadow-lg sm:p-6 lg:h-full lg:px-6 lg:py-5">
                            <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-white/20 blur-2xl" />
                            <div class="pointer-events-none absolute -bottom-10 -left-8 h-24 w-24 rounded-full bg-sky-300/30 blur-2xl" />

                            <div class="relative flex h-full min-h-[340px] flex-col gap-5 sm:min-h-[300px] lg:min-h-[260px] lg:grid lg:grid-cols-[minmax(0,1fr)_180px] lg:gap-4">
                                <div class="flex h-full flex-col justify-between gap-4">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-md bg-white/90 p-1 ring-1 ring-white/30">
                                                <img v-if="brandLogoUrl" :src="brandLogoUrl" alt="Logo BERKAT" class="h-full w-full object-contain">
                                                <span v-else class="text-[9px] font-bold text-slate-700">BKT</span>
                                            </div>
                                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-white/90">BERKAT</p>
                                        </div>
                                        <span class="rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-semibold">AHLI</span>
                                    </div>

                                    <div class="flex items-center gap-3 lg:gap-4">
                                        <div
                                            v-if="memberProfilePhotoUrl"
                                            class="h-14 w-14 overflow-hidden rounded-2xl ring-1 ring-white/40"
                                        >
                                            <img
                                                :src="memberProfilePhotoUrl"
                                                :alt="`Gambar profil ${applicantName}`"
                                                class="h-full w-full object-cover"
                                            >
                                        </div>
                                        <div v-else class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 text-lg font-bold tracking-wide ring-1 ring-white/30">
                                            {{ memberInitials }}
                                        </div>
                                        <div>
                                            <p class="line-clamp-2 text-sm font-semibold leading-tight lg:text-base">{{ applicantName }}</p>
                                            <p class="mt-1 text-[11px] text-white/80">{{ memberEmail }}</p>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="grid grid-cols-2 gap-2 text-[11px] lg:grid-cols-4">
                                            <div>
                                                <p class="uppercase tracking-[0.12em] text-white/75">No. Ahli</p>
                                                <p class="mt-0.5 font-semibold tracking-wide">{{ memberNo }}</p>
                                            </div>
                                            <div>
                                                <p class="uppercase tracking-[0.12em] text-white/75">Telefon</p>
                                                <p class="mt-0.5 font-semibold">{{ memberPhone }}</p>
                                            </div>
                                            <div>
                                                <p class="uppercase tracking-[0.12em] text-white/75">Jawatan</p>
                                                <p class="mt-0.5 line-clamp-1 font-semibold">{{ memberJobTitle }}</p>
                                            </div>
                                            <div>
                                                <p class="uppercase tracking-[0.12em] text-white/75">Jabatan</p>
                                                <p class="mt-0.5 line-clamp-1 font-semibold">{{ memberDepartment }}</p>
                                            </div>
                                        </div>

                                        <div class="mt-1 flex items-end justify-between gap-3 lg:block">
                                            <p class="text-[11px] text-white/80">{{ memberState }}</p>
                                            <div class="flex flex-col items-center gap-1 lg:hidden">
                                                <div class="rounded-lg bg-white p-1.5 shadow-sm ring-1 ring-white/40">
                                                    <img
                                                        v-if="memberCardQrUrl"
                                                        :src="memberCardQrUrl"
                                                        alt="QR Kad Ahli"
                                                        class="h-14 w-14 rounded object-contain"
                                                    >
                                                    <div v-else class="flex h-14 w-14 items-center justify-center rounded bg-slate-100 text-[10px] font-semibold text-slate-500">
                                                        QR
                                                    </div>
                                                </div>
                                                <p class="text-[10px] font-semibold text-white/90">Scan buka kad</p>
                                            </div>
                                        </div>
                                        <p class="pt-1 text-[11px] font-semibold text-white/90 group-hover:text-white">Tap untuk lihat kad penuh</p>
                                    </div>
                                </div>

                                <div class="hidden h-full items-center justify-center rounded-2xl bg-white/10 p-4 ring-1 ring-white/20 lg:flex">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="rounded-lg bg-white p-2 shadow-sm ring-1 ring-slate-200/70">
                                            <img
                                                v-if="memberCardQrUrl"
                                                :src="memberCardQrUrl"
                                                alt="QR Kad Ahli"
                                                class="h-24 w-24 rounded object-contain"
                                            >
                                            <div v-else class="flex h-24 w-24 items-center justify-center rounded bg-slate-100 text-xs font-semibold text-slate-500">
                                                QR
                                            </div>
                                        </div>
                                        <p class="text-[11px] font-semibold text-white">Scan buka kad</p>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </Link>
                </section>

                <section :class="`${bentoCardClass} col-span-1 md:col-span-3 lg:col-span-4`">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 :class="sectionTitleClass">Sejarah Permohonan</h3>
                        <Link :href="route('applications.index')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Lihat semua</Link>
                    </div>

                    <div v-if="!recentApplications.length" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-center text-sm text-slate-500">
                        Tiada sejarah permohonan lagi.
                    </div>

                    <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <article
                            v-for="application in recentApplications"
                            :key="`history-${application.id}`"
                            class="surface-card-soft"
                        >
                            <p class="text-sm font-semibold text-slate-900">{{ application.reference_no || `Permohonan #${application.id}` }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ application.category || 'Kategori Umum' }} • {{ application.created_at }}</p>
                            <div class="mt-3">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="statusBadgeClass(application.status)">
                                    {{ statusText[application.status] || application.status }}
                                </span>
                            </div>
                        </article>
                    </div>
                </section>

                <section :class="`${bentoCardClass} col-span-1 md:col-span-3 lg:col-span-4`">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 :class="sectionTitleClass">Info Terkini</h3>
                        <span class="text-xs font-medium text-slate-500">{{ totalPosterSlides ? `${currentPosterSlide + 1}/${totalPosterSlides}` : '0/0' }}</span>
                    </div>

                    <div v-if="!currentPosterItems.length" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-center text-sm text-slate-500">
                        Tiada info terkini tersedia.
                    </div>

                    <div v-else>
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            <article v-for="poster in currentPosterItems" :key="poster.id">
                                <div class="relative aspect-[4/5] overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 sm:aspect-[3/4] lg:aspect-[16/10]">
                                    <img
                                        :src="poster.image_url"
                                        :alt="poster.title"
                                        class="h-full w-full object-cover"
                                    >
                                </div>
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

                <section :class="`${bentoCardClass} col-span-1 md:col-span-3 lg:col-span-4`">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 :class="sectionTitleClass">Notifikasi Terkini</h3>
                        <div class="flex items-center gap-2">
                            <span v-if="unreadAnnouncementsCount > 0" class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-semibold text-rose-700">
                                {{ unreadAnnouncementsCount }} belum dibaca
                            </span>
                            <button
                                type="button"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="unreadAnnouncementsCount < 1"
                                @click="markAllAnnouncementsRead"
                            >
                                Tanda Semua Dibaca
                            </button>
                        </div>
                    </div>

                    <div v-if="!announcements.length" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-center text-sm text-slate-500">
                        Tiada notifikasi terkini.
                    </div>

                    <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <article
                            v-for="notice in announcements"
                            :key="notice.id"
                            class="surface-card-soft"
                            :class="notice.is_read ? 'ring-slate-200' : 'ring-rose-200 bg-rose-50/40'"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">{{ notice.subject }}</h4>
                                    <span v-if="!notice.is_read" class="mt-1 inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-700">Baru</span>
                                </div>
                                <span class="text-[11px] font-medium text-slate-500">{{ notice.created_at }}</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-700">{{ notice.message }}</p>
                            <div v-if="notice.image_url" class="mt-2">
                                <button
                                    type="button"
                                    class="block w-full"
                                    @click="openImagePreview(notice.image_url, notice.subject || 'Lampiran notifikasi')"
                                >
                                    <img
                                        :src="notice.image_url"
                                        alt="Lampiran notifikasi"
                                        class="h-40 w-full rounded-lg border border-slate-200 object-cover"
                                    >
                                </button>
                            </div>
                            <div v-if="notice.image_url" class="mt-2 flex items-center gap-3">
                                <button
                                    type="button"
                                    class="inline-flex text-[11px] font-semibold text-indigo-600 hover:text-indigo-500"
                                    @click="openImagePreview(notice.image_url, notice.subject || 'Lampiran notifikasi')"
                                >
                                    Pratonton Imej
                                </button>
                                <a
                                    :href="notice.image_url"
                                    target="_blank"
                                    class="inline-flex text-[11px] font-semibold text-slate-600 hover:text-slate-500"
                                >
                                    Buka Tab Baru
                                </a>
                            </div>
                            <p class="mt-2 text-[11px] text-slate-500">
                                <span v-if="notice.reference_no">Rujukan: {{ notice.reference_no }}</span>
                                <span v-if="notice.status" class="ml-2">Status: {{ notice.status }}</span>
                            </p>
                            <div v-if="!notice.is_read" class="mt-3">
                                <button
                                    type="button"
                                    class="rounded-lg border border-rose-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-50"
                                    @click="markAnnouncementRead(notice.id)"
                                >
                                    Tanda Dibaca
                                </button>
                            </div>
                        </article>
                    </div>
                </section>

            </div>
        </div>

        <div
            v-if="imagePreviewUrl"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 px-4"
            @click.self="closeImagePreview"
        >
            <div class="w-full max-w-5xl rounded-2xl bg-slate-950 p-3 shadow-2xl">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <p class="truncate text-xs font-semibold text-slate-200">{{ imagePreviewTitle }}</p>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-700 px-2.5 py-1 text-xs font-semibold text-slate-200 hover:bg-slate-800"
                        @click="closeImagePreview"
                    >
                        Tutup
                    </button>
                </div>
                <img
                    :src="imagePreviewUrl"
                    :alt="imagePreviewTitle"
                    class="max-h-[78vh] w-full rounded-xl object-contain"
                >
            </div>
        </div>
    </AuthenticatedLayout>
</template>
