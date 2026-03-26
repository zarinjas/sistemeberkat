<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    users: {
        type: Object,
        default: () => ({ data: [] }),
    },
    stats: {
        type: Object,
        default: () => ({ total: 0, admin: 0, applicant: 0 }),
    },
    membershipImport: {
        type: Object,
        default: () => ({ sampleColumns: [], summary: null }),
    },
    filters: {
        type: Object,
        default: () => ({ q: '' }),
    },
});

const page = usePage();
const importForm = useForm({
    csv_file: null,
});

const isSuperadmin = page.props.auth?.user?.is_superadmin;
const searchQuery = ref(props.filters?.q ?? '');
const selectedRole = ref(props.filters?.role ?? '');
const selectedActivation = ref(props.filters?.activation ?? '');
const selectedPerPage = ref(String(props.filters?.per_page ?? 20));

const onImportFileChange = (event) => {
    importForm.csv_file = event.target.files?.[0] ?? null;
};

const submitImport = () => {
    importForm.post(route('admin.system.membership-import.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            importForm.reset();
        },
    });
};

const searchUsers = () => {
    router.get(route('admin.system.index'), {
        q: searchQuery.value,
        role: selectedRole.value,
        activation: selectedActivation.value,
        per_page: selectedPerPage.value,
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const resetSearch = () => {
    searchQuery.value = '';
    selectedRole.value = '';
    selectedActivation.value = '';
    selectedPerPage.value = '20';
    searchUsers();
};

const updateRole = (userId, role) => {
    router.patch(route('admin.system.users.role', userId), { role }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Modul Pengurusan Sistem" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Modul Pengurusan Sistem</h2>
        </template>

        <div class="page-shell px-4 sm:px-6 lg:px-8">
            <section v-if="$page.props.flash?.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ $page.props.flash.success }}
            </section>

            <section v-if="$page.props.flash?.error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                {{ $page.props.flash.error }}
            </section>

            <section class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <article class="surface-card p-5">
                    <p class="text-sm text-slate-500">Jumlah Pengguna</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ stats.total ?? 0 }}</p>
                </article>
                <article class="surface-card p-5">
                    <p class="text-sm text-slate-500">Pentadbir</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-700">{{ stats.admin ?? 0 }}</p>
                </article>
                <article class="surface-card p-5">
                    <p class="text-sm text-slate-500">Pemohon</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-700">{{ stats.applicant ?? 0 }}</p>
                </article>
            </section>

            <section class="surface-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="section-title">Pengurusan Peranan Pengguna</h3>
                        <p class="section-subtitle">Arahan Admin (BM): Tukar peranan pengguna hanya jika mendapat kelulusan pentadbiran.</p>
                    </div>
                    <Link
                        :href="route('admin.audit.index')"
                        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Buka Audit Operasi
                    </Link>
                </div>

                <form class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-12" @submit.prevent="searchUsers">
                    <input
                        v-model="searchQuery"
                        type="text"
                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:col-span-5"
                        placeholder="Cari nama, e-mel, No. IC, atau No. ahli"
                    >

                    <select
                        v-model="selectedRole"
                        class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:col-span-2"
                    >
                        <option value="">Semua Peranan</option>
                        <option value="admin">Admin</option>
                        <option value="applicant">Pemohon</option>
                    </select>

                    <select
                        v-model="selectedActivation"
                        class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:col-span-2"
                    >
                        <option value="">Semua Status Aktivasi</option>
                        <option value="activated">Sudah Aktif</option>
                        <option value="pending">Belum Aktif</option>
                    </select>

                    <select
                        v-model="selectedPerPage"
                        class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:col-span-1"
                        @change="searchUsers"
                    >
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>

                    <div class="flex gap-2 lg:col-span-2">
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            Cari
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            @click="resetSearch"
                        >
                            Reset
                        </button>
                    </div>
                </form>

                <p class="mt-3 text-xs text-slate-500">
                    Paparan {{ users.from || 0 }} - {{ users.to || 0 }} daripada {{ users.total || 0 }} rekod.
                </p>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Nama</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Emel</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">No. IC</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">No. Ahli</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Cawangan</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Peranan</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Aktivasi</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-if="!users?.data?.length">
                                <td colspan="8" class="px-4 py-6 text-center text-slate-500">Tiada pengguna dijumpai.</td>
                            </tr>
                            <tr v-for="user in users.data || []" :key="user.id">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ user.name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ user.email }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ user.nric || '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ user.member_no || '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ user.branch || '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ user.role }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="status-pill"
                                        :class="user.first_login_completed ? 'bg-emerald-100 text-emerald-700 ring-emerald-200' : 'bg-amber-100 text-amber-700 ring-amber-200'"
                                    >
                                        {{ user.first_login_completed ? 'Aktif' : 'Belum Aktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <select
                                        class="rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                                        :value="user.role"
                                        @change="updateRole(user.id, $event.target.value)"
                                    >
                                        <option value="applicant">applicant</option>
                                        <option value="admin">admin</option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="users.links?.length > 3" class="mt-4 flex flex-wrap items-center gap-2">
                    <Link
                        v-for="(link, index) in users.links"
                        :key="index"
                        :href="link.url || '#'"
                        :class="[
                            'rounded-lg border px-3 py-1.5 text-xs font-semibold',
                            link.active
                                ? 'border-indigo-600 bg-indigo-600 text-white'
                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
                            !link.url ? 'pointer-events-none opacity-50' : '',
                        ]"
                        preserve-scroll
                        preserve-state
                        v-html="link.label"
                    />
                </div>
            </section>

            <section v-if="isSuperadmin" class="surface-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="section-title">Import Ahli (CSV)</h3>
                        <p class="section-subtitle">Muat naik data ahli secara pukal untuk mengaktifkan aliran Login Kali Pertama berasaskan No. IC.</p>
                    </div>
                    <Link
                        :href="route('admin.system.membership-import.template')"
                        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Download Template CSV
                    </Link>
                </div>

                <div class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-700">
                    Kolum disokong:
                    <span class="font-semibold">{{ membershipImport.sampleColumns.join(', ') }}</span>
                </div>

                <form class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="submitImport">
                    <div class="flex-1">
                        <label class="mb-2 block text-sm font-medium text-slate-700">Fail CSV Ahli</label>
                        <input
                            type="file"
                            accept=".csv,text/csv"
                            required
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700"
                            @change="onImportFileChange"
                        >
                        <p v-if="importForm.errors.csv_file" class="mt-1 text-sm text-rose-600">
                            {{ importForm.errors.csv_file }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="importForm.processing"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ importForm.processing ? 'Sedang import...' : 'Import CSV Ahli' }}
                    </button>
                </form>

                <div v-if="membershipImport.summary" class="mt-5 rounded-xl border border-slate-200 bg-white p-4">
                    <h4 class="text-sm font-semibold text-slate-900">Ringkasan Import Terakhir</h4>
                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm md:grid-cols-4">
                        <div class="rounded-lg bg-slate-50 p-3">
                            <p class="text-slate-500">Diproses</p>
                            <p class="text-lg font-bold text-slate-900">{{ membershipImport.summary.processed }}</p>
                        </div>
                        <div class="rounded-lg bg-emerald-50 p-3">
                            <p class="text-emerald-600">Cipta</p>
                            <p class="text-lg font-bold text-emerald-700">{{ membershipImport.summary.created }}</p>
                        </div>
                        <div class="rounded-lg bg-blue-50 p-3">
                            <p class="text-blue-600">Kemaskini</p>
                            <p class="text-lg font-bold text-blue-700">{{ membershipImport.summary.updated }}</p>
                        </div>
                        <div class="rounded-lg bg-amber-50 p-3">
                            <p class="text-amber-600">Langkau</p>
                            <p class="text-lg font-bold text-amber-700">{{ membershipImport.summary.skipped }}</p>
                        </div>
                    </div>

                    <div v-if="membershipImport.summary.errors?.length" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3">
                        <p class="text-sm font-semibold text-rose-700">Ralat Data</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">
                            <li v-for="(error, index) in membershipImport.summary.errors.slice(0, 20)" :key="index">
                                {{ error }}
                            </li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
