<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    notifications: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    unreadCount: {
        type: Number,
        default: 0,
    },
});

const selected = ref(null);
const markingAll = ref(false);

const openNotification = (item) => {
    selected.value = item;
    if (!item.is_read) {
        item.is_read = true;
        router.post(route('applicant.announcements.read', item.id), {}, {
            preserveScroll: true,
            preserveState: true,
        });
    }
};

const closePanel = () => {
    selected.value = null;
};

const markAllRead = () => {
    markingAll.value = true;
    router.post(route('applicant.announcements.read-all'), {}, {
        preserveScroll: true,
        onFinish: () => { markingAll.value = false; },
    });
};

const statusLabel = (status) => {
    const map = {
        pending: 'Dalam Semakan',
        approved: 'Diluluskan',
        rejected: 'Ditolak',
        incomplete: 'Tidak Lengkap',
    };
    return map[status] || status;
};

const statusClass = (status) => {
    const map = {
        approved: 'bg-emerald-100 text-emerald-700',
        rejected: 'bg-rose-100 text-rose-700',
        incomplete: 'bg-amber-100 text-amber-700',
        pending: 'bg-blue-100 text-blue-700',
    };
    return map[status] || 'bg-slate-100 text-slate-600';
};

const handleKeydown = (e) => {
    if (e.key === 'Escape') closePanel();
};
</script>

<template>
    <Head title="Notifikasi Saya" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Notifikasi Saya</h2>
        </template>

        <div class="py-8" @keydown.esc="closePanel">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">

                <!-- Header bar -->
                <div class="mb-5 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-slate-700">Semua Notifikasi</span>
                        <span
                            v-if="unreadCount > 0"
                            class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700"
                        >
                            {{ unreadCount }} belum dibaca
                        </span>
                    </div>
                    <button
                        v-if="unreadCount > 0"
                        type="button"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 disabled:opacity-50"
                        :disabled="markingAll"
                        @click="markAllRead"
                    >
                        {{ markingAll ? 'Menandakan...' : 'Tandakan Semua Dibaca' }}
                    </button>
                </div>

                <!-- Empty state -->
                <div
                    v-if="!notifications.data?.length"
                    class="rounded-2xl bg-white p-16 text-center shadow-sm ring-1 ring-slate-100"
                >
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                        <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-slate-500">Tiada notifikasi lagi</p>
                    <p class="mt-1 text-xs text-slate-400">Notifikasi berkaitan permohonan anda akan muncul di sini.</p>
                </div>

                <!-- Notification list -->
                <div v-else class="space-y-2">
                    <button
                        v-for="item in notifications.data"
                        :key="item.id"
                        type="button"
                        class="group w-full rounded-2xl bg-white p-4 text-left shadow-sm ring-1 transition-all hover:shadow-md hover:ring-blue-200"
                        :class="item.is_read ? 'ring-slate-100' : 'ring-blue-200'"
                        @click="openNotification(item)"
                    >
                        <div class="flex items-start gap-3">
                            <!-- Unread dot -->
                            <div class="mt-1.5 flex-shrink-0">
                                <span
                                    class="block h-2 w-2 rounded-full transition-colors"
                                    :class="item.is_read ? 'bg-slate-200' : 'bg-blue-500'"
                                ></span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p
                                        class="text-sm font-semibold transition-colors group-hover:text-blue-700"
                                        :class="item.is_read ? 'text-slate-600' : 'text-slate-900'"
                                    >
                                        {{ item.subject }}
                                    </p>
                                    <span
                                        v-if="item.status"
                                        class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                        :class="statusClass(item.status)"
                                    >
                                        {{ statusLabel(item.status) }}
                                    </span>
                                </div>

                                <p
                                    class="mt-1 line-clamp-2 text-xs leading-relaxed"
                                    :class="item.is_read ? 'text-slate-400' : 'text-slate-500'"
                                >
                                    {{ item.message }}
                                </p>

                                <div class="mt-2 flex items-center gap-3">
                                    <span class="text-[11px] text-slate-400">{{ item.created_at }}</span>
                                    <span v-if="item.reference_no" class="font-mono text-[11px] text-slate-400">{{ item.reference_no }}</span>
                                    <span v-if="item.image_url" class="text-[11px] text-slate-400">· ada lampiran</span>
                                </div>
                            </div>

                            <svg class="h-4 w-4 flex-shrink-0 text-slate-300 transition-colors group-hover:text-blue-400 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </button>
                </div>

                <!-- Pagination -->
                <div v-if="notifications.links?.length > 3" class="mt-6 flex justify-center gap-1">
                    <template v-for="link in notifications.links" :key="link.label">
                        <button
                            v-if="link.url"
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                            :class="link.active ? 'bg-blue-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                            @click="router.get(link.url, {}, { preserveScroll: true })"
                            v-html="link.label"
                        ></button>
                        <span v-else class="px-2 py-1.5 text-xs text-slate-400" v-html="link.label"></span>
                    </template>
                </div>
            </div>
        </div>

        <!-- Slide-over panel -->
        <Teleport to="body">
            <Transition name="overlay">
                <div
                    v-if="selected"
                    class="fixed inset-0 z-50 bg-slate-900/40"
                    @click.self="closePanel"
                ></div>
            </Transition>

            <Transition name="slideover">
                <div
                    v-if="selected"
                    class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-white shadow-2xl"
                    @keydown.esc="closePanel"
                >
                    <!-- Panel header -->
                    <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
                        <div class="flex-1 min-w-0 pr-4">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span
                                    v-if="selected.status"
                                    class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                    :class="statusClass(selected.status)"
                                >
                                    {{ statusLabel(selected.status) }}
                                </span>
                            </div>
                            <h3 class="text-base font-semibold text-slate-900 leading-snug">{{ selected.subject }}</h3>
                            <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                                <span>{{ selected.created_at }}</span>
                                <span v-if="selected.reference_no" class="font-mono">· {{ selected.reference_no }}</span>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="flex-shrink-0 rounded-xl border border-slate-200 p-2 text-slate-400 transition hover:bg-slate-50 hover:text-slate-600"
                            @click="closePanel"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Panel body -->
                    <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                        <div class="rounded-xl bg-slate-50 p-4 ring-1 ring-slate-100">
                            <p class="text-sm leading-relaxed text-slate-700 whitespace-pre-line">{{ selected.message }}</p>
                        </div>

                        <div v-if="selected.image_url">
                            <p class="mb-2 text-xs font-semibold text-slate-500">Lampiran</p>
                            <a :href="selected.image_url" target="_blank" rel="noopener">
                                <img
                                    :src="selected.image_url"
                                    alt="Lampiran notifikasi"
                                    class="w-full rounded-xl border border-slate-200 object-cover transition hover:opacity-90"
                                >
                            </a>
                            <p class="mt-1.5 text-[11px] text-slate-400">Klik imej untuk buka dalam tab baru</p>
                        </div>
                    </div>

                    <!-- Panel footer -->
                    <div class="border-t border-slate-100 px-6 py-4">
                        <button
                            type="button"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100"
                            @click="closePanel"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.overlay-enter-active,
.overlay-leave-active {
    transition: opacity 0.2s ease;
}
.overlay-enter-from,
.overlay-leave-to {
    opacity: 0;
}

.slideover-enter-active,
.slideover-leave-active {
    transition: transform 0.25s ease;
}
.slideover-enter-from,
.slideover-leave-to {
    transform: translateX(100%);
}
</style>
