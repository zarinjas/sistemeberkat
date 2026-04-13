<script setup>
import { computed, ref, reactive } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const showSidebar = ref(false);

// State untuk semua menu yang ada children
const expandedMenus = reactive({});

const getMenuKey = (item) => item.menuKey ?? item.label;

const isMenuExpanded = (item) => expandedMenus[getMenuKey(item)] ?? true;

const toggleMenu = (item) => {
    const menuKey = getMenuKey(item);
    expandedMenus[menuKey] = !isMenuExpanded(item);
};

const user = computed(() => page.props.auth?.user ?? {});
const userInitial = computed(() => (user.value.name || 'U').charAt(0).toUpperCase());

const navigationItems = computed(() => {
    const guidelineChildren = [
        ...(user.value.is_superadmin ? [{ label: 'Urus Garis Panduan', href: route('guidelines.manage'), active: route().current('guidelines.manage') }] : []),
        ...((page.props.guidelinesNav || []).map((item) => ({
            label: item.title,
            href: route('guidelines.show', item.slug),
            active: route().current('guidelines.show', item.slug),
        }))),
    ];

    const infoCenterChildren = [
        { label: 'Infografik', href: route('info-center.infographics'), active: route().current('info-center.infographics') },
        { label: 'Undang-Undang & Perlembagaan', href: route('info-center.legal'), active: route().current('info-center.legal') },
        { label: 'Senarai AJK BERKAT', href: route('info-center.ajk'), active: route().current('info-center.ajk') },
    ];

    const items = [
        { label: 'Dashboard', href: route('dashboard'), active: route().current('dashboard') },
    ];

    if (user.value.role === 'applicant') {
        items.push(
            { label: 'Permohonan', href: route('applications.index'), active: route().current('applications.*'), isPrimaryAction: true },
            { label: 'Kad Ahli', href: route('membership-card'), active: route().current('membership-card') },
        );
    }

    items.push(
        { label: 'Pusat Info', href: '#', active: route().current('info-center.*'), children: infoCenterChildren, menuKey: 'info-center' },
        { label: 'Garis Panduan', href: '#', active: route().current('guidelines.*'), children: guidelineChildren, menuKey: 'guidelines' },
    );

    if (user.value.role === 'admin' || user.value.is_superadmin) {
        items.push(
            { label: 'Kelulusan', href: route('admin.applications.index'), active: route().current('admin.approvals.*') || route().current('admin.applications.*') },
            { label: 'Bayaran', href: route('admin.payments.index'), active: route().current('admin.payments.*') },
            { label: 'Laporan', href: route('admin.reports.index'), active: route().current('admin.reports.*') },
            { label: 'Notifikasi', href: route('admin.notifications.index'), active: route().current('admin.notifications.*') },
            { label: 'Pengurusan Ahli BERKAT', href: route('admin.system.index'), active: route().current('admin.system.*') },
        );

        if (user.value.is_superadmin) {
            items.push(
                                { label: 'Pengurusan Borang',
                                    menuKey: 'form-management',
                                    active: route().current('admin.form-builder') || route().current('forms.builder') || route().current('forms.manage'),
                                    children: [
                                        { label: 'Senarai Borang', href: route('forms.manage'), active: route().current('forms.manage') },
                                        { label: 'Pembina Borang', href: route('forms.builder'), active: route().current('admin.form-builder') || route().current('forms.builder') },
                                    ]
                                },
                { label: 'Audit Operasi', href: route('admin.audit.index'), active: route().current('admin.audit.*') },
                { label: 'Tetapan Hero', href: route('admin.hero-settings'), active: route().current('admin.hero-settings') },
            );
        }
    }

    return items;
});
</script>

<template>
    <div class="min-h-screen flex bg-gray-50/50">
        <div
            v-if="showSidebar"
            class="fixed inset-0 z-30 bg-slate-900/30 lg:hidden"
            @click="showSidebar = false"
        ></div>

        <aside
            class="fixed inset-y-0 left-0 z-40 w-64 transform border-r border-slate-200/70 bg-white/95 shadow-sm transition-transform duration-200 lg:static lg:translate-x-0"
            :class="showSidebar ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-full flex-col">
                <div class="flex items-center gap-3 px-5 py-6">
                    <Link :href="route('dashboard')" class="inline-flex items-center gap-3">
                        <template v-if="$page.props.branding?.logo_url">
                            <img
                                :src="$page.props.branding.logo_url"
                                alt="e-BERKAT Logo"
                                class="h-10 w-auto object-contain"
                            />
                        </template>
                        <template v-else>
                            <ApplicationLogo class="h-10 w-auto fill-current text-slate-800" />
                        </template>
                        <div>
                            <p class="text-lg font-bold tracking-tight text-slate-900">e-BERKAT</p>
                            <p class="text-xs text-slate-500">Digital Membership</p>
                        </div>
                    </Link>
                </div>

                <nav class="mt-2 space-y-1 px-2">
                    <template v-for="item in navigationItems" :key="item.label">
                        <template v-if="item.children">
                            <button
                                type="button"
                                class="mx-2 flex w-full items-center justify-between rounded-xl p-3 text-sm font-medium transition"
                                :class="item.active ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-blue-50 hover:text-blue-600'"
                                @click="toggleMenu(item)"
                            >
                                <span>{{ item.label }}</span>
                                <span class="text-xs">{{ isMenuExpanded(item) ? '▾' : '▸' }}</span>
                            </button>

                            <div v-if="isMenuExpanded(item)" class="ml-5 mr-2 space-y-1">
                                <Link
                                    v-for="child in item.children"
                                    :key="child.label"
                                    :href="child.href"
                                    class="block rounded-lg px-3 py-2 text-xs font-medium transition"
                                    :class="child.active ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-blue-50 hover:text-blue-600'"
                                    @click="showSidebar = false"
                                >
                                    {{ child.label }}
                                </Link>
                            </div>
                        </template>

                        <Link
                            v-else
                            :href="item.href"
                            class="mx-2 block rounded-xl p-3 text-sm font-medium transition"
                            :class="item.isPrimaryAction
                                ? (item.active
                                    ? 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200'
                                    : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 hover:bg-emerald-100')
                                : (item.active ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-blue-50 hover:text-blue-600')"
                            @click="showSidebar = false"
                        >
                            {{ item.label }}
                        </Link>
                    </template>
                </nav>
            </div>
        </aside>

        <div class="flex-1 flex flex-col lg:pl-0">
            <header class="flex h-20 items-center justify-between px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 lg:hidden"
                        @click="showSidebar = !showSidebar"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div v-if="$slots.header" class="hidden md:block">
                        <slot name="header" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <Dropdown align="right" width="56">
                        <template #trigger>
                            <button
                                type="button"
                                class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700">
                                    {{ userInitial }}
                                </span>
                                <span class="hidden sm:block">{{ user.name }}</span>
                                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <DropdownLink :href="route('profile.edit')">Profil</DropdownLink>
                            <DropdownLink :href="route('profile.edit')">Kemaskini Profil</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Log Keluar</DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <main class="flex-1 p-6 lg:p-8">
                <div v-if="$slots.header" class="mb-6 md:hidden">
                    <slot name="header" />
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
