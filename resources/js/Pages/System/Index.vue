<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    users: {
        type: Object,
        default: () => ({ data: [] }),
    },
    stats: {
        type: Object,
        default: () => ({ total: 0, admin: 0, applicant: 0 }),
    },
    canManageMembers: {
        type: Boolean,
        default: false,
    },
    canImportExportMembers: {
        type: Boolean,
        default: false,
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
const isSuperadmin = computed(() => Boolean(page.props.auth?.user?.is_superadmin && props.canManageMembers));
const compactMode = ref(false);
const tableCellClass = computed(() => (compactMode.value ? 'px-4 py-2' : 'px-4 py-3'));

const searchQuery = ref(props.filters?.q ?? '');
const selectedRole = ref(props.filters?.role ?? '');
const selectedActivation = ref(props.filters?.activation ?? '');
const selectedPerPage = ref(String(props.filters?.per_page ?? 20));

const importForm = useForm({
    csv_file: null,
});

const createMemberForm = useForm({
    name: '',
    email: '',
    nric: '',
    phone: '',
    member_no: '',
    job_title: '',
    department: '',
    state: '',
    branch: '',
    address: '',
    first_login_completed: false,
});

const editingMemberId = ref(null);
const editMemberForm = useForm({
    name: '',
    email: '',
    nric: '',
    phone: '',
    member_no: '',
    job_title: '',
    department: '',
    state: '',
    branch: '',
    address: '',
    first_login_completed: false,
});

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

const submitManualMember = () => {
    createMemberForm.post(route('admin.system.members.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createMemberForm.reset();
        },
    });
};

const openEditMember = (member) => {
    if (!member) {
        return;
    }

    editingMemberId.value = member.id;
    editMemberForm.name = member.name || '';
    editMemberForm.email = member.email || '';
    editMemberForm.nric = member.nric || '';
    editMemberForm.phone = member.phone || '';
    editMemberForm.member_no = member.member_no || '';
    editMemberForm.job_title = member.job_title || '';
    editMemberForm.department = member.department || '';
    editMemberForm.state = member.state || '';
    editMemberForm.branch = member.branch || '';
    editMemberForm.address = member.address || '';
    editMemberForm.first_login_completed = Boolean(member.first_login_completed);
};

const closeEditMember = () => {
    editingMemberId.value = null;
    editMemberForm.reset();
    editMemberForm.clearErrors();
};

const submitEditMember = () => {
    if (!editingMemberId.value) {
        return;
    }

    editMemberForm.patch(route('admin.system.members.update', editingMemberId.value), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditMember();
        },
    });
};

</script>

<template>
    <Head title="Pengurusan Ahli BERKAT" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Pengurusan Ahli BERKAT</h2>
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
                    <p class="text-sm text-slate-500">Ahli</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-700">{{ stats.applicant ?? 0 }}</p>
                </article>
            </section>

            <section class="surface-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="section-title">Direktori Ahli BERKAT</h3>
                        <p class="section-subtitle">Pegawai penyemak hanya boleh lihat detail ahli. Tindakan tambah/edit/import/export hanya untuk superadmin.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex whitespace-nowrap items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                            @click="compactMode = !compactMode"
                        >
                            {{ compactMode ? 'Paparan Normal' : 'Paparan Padat' }}
                        </button>
                        <Link
                            v-if="isSuperadmin"
                            :href="route('admin.audit.index')"
                            class="inline-flex whitespace-nowrap items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Audit Operasi
                        </Link>
                        <Link
                            v-if="canImportExportMembers"
                            :href="route('admin.system.members.export', { q: searchQuery || undefined })"
                            class="inline-flex whitespace-nowrap items-center rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"
                        >
                            Export CSV Ahli
                        </Link>
                    </div>
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
                        <option value="applicant">Ahli</option>
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
                            class="inline-flex whitespace-nowrap items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            Cari
                        </button>
                        <button
                            type="button"
                            class="inline-flex whitespace-nowrap items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            @click="resetSearch"
                        >
                            Reset
                        </button>
                    </div>
                </form>

                <p class="mt-3 text-xs text-slate-500">
                    Paparan {{ users.from || 0 }} - {{ users.to || 0 }} daripada {{ users.total || 0 }} rekod.
                </p>

                <div class="mt-5 overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Nama</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Emel</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">No. IC</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">No. Ahli</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Peranan</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Aktivasi</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-if="!users?.data?.length">
                                <td colspan="7" class="px-4 py-6 text-center text-slate-500">Tiada pengguna dijumpai.</td>
                            </tr>
                            <tr v-for="user in users.data || []" :key="user.id">
                                <td :class="`${tableCellClass} font-medium text-slate-900`">{{ user.name }}</td>
                                <td :class="`${tableCellClass} text-slate-700`">{{ user.email }}</td>
                                <td :class="`${tableCellClass} text-slate-700`">{{ user.nric || '-' }}</td>
                                <td :class="`${tableCellClass} text-slate-700`">{{ user.member_no || '-' }}</td>
                                <td :class="`${tableCellClass} text-slate-700`">{{ user.role }}</td>
                                <td :class="tableCellClass">
                                    <span
                                        class="status-pill"
                                        :class="user.first_login_completed ? 'bg-emerald-100 text-emerald-700 ring-emerald-200' : 'bg-amber-100 text-amber-700 ring-amber-200'"
                                    >
                                        {{ user.first_login_completed ? 'Aktif' : 'Belum Aktif' }}
                                    </span>
                                </td>
                                <td :class="tableCellClass">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <Link
                                            v-if="user.role === 'applicant'"
                                            :href="route('admin.system.members.show', user.id)"
                                            class="inline-flex whitespace-nowrap rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            Lihat Detail
                                        </Link>
                                        <span v-else class="text-xs text-slate-400">-</span>
                                        <button
                                            v-if="isSuperadmin && user.role === 'applicant'"
                                            type="button"
                                            class="inline-flex whitespace-nowrap rounded-md border border-indigo-300 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                                            @click="openEditMember(user)"
                                        >
                                            Edit Ahli
                                        </button>
                                    </div>
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
                            'whitespace-nowrap rounded-lg border px-3 py-1.5 text-xs font-semibold',
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
                <h3 class="section-title">Tambah Ahli Manual</h3>
                <p class="section-subtitle">Gunakan borang ini untuk pendaftaran manual ahli oleh Superadmin.</p>

                <form class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2" @submit.prevent="submitManualMember">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Nama</label>
                        <input v-model="createMemberForm.name" type="text" required class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <p v-if="createMemberForm.errors.name" class="mt-1 text-xs text-rose-600">{{ createMemberForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">No. IC</label>
                        <input v-model="createMemberForm.nric" type="text" required class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <p v-if="createMemberForm.errors.nric" class="mt-1 text-xs text-rose-600">{{ createMemberForm.errors.nric }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Emel</label>
                        <input v-model="createMemberForm.email" type="email" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">No. Ahli</label>
                        <input v-model="createMemberForm.member_no" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Telefon</label>
                        <input v-model="createMemberForm.phone" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Jawatan</label>
                        <input v-model="createMemberForm.job_title" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Jabatan</label>
                        <input v-model="createMemberForm.department" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Negeri</label>
                        <input v-model="createMemberForm.state" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Cawangan</label>
                        <input v-model="createMemberForm.branch" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Alamat</label>
                        <input v-model="createMemberForm.address" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                            <input v-model="createMemberForm.first_login_completed" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Tandakan ahli sudah aktif log masuk pertama
                        </label>
                    </div>
                    <div class="md:col-span-2">
                        <button
                            type="submit"
                            :disabled="createMemberForm.processing"
                            class="inline-flex whitespace-nowrap rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {{ createMemberForm.processing ? 'Menyimpan...' : 'Tambah Ahli Manual' }}
                        </button>
                    </div>
                </form>
            </section>

            <section v-if="canImportExportMembers" class="surface-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="section-title">Import Ahli (CSV)</h3>
                        <p class="section-subtitle">Muat naik data ahli secara pukal untuk mengaktifkan aliran Login Kali Pertama berasaskan No. IC.</p>
                    </div>
                    <Link
                        :href="route('admin.system.membership-import.template')"
                        class="inline-flex whitespace-nowrap items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
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
                        class="inline-flex whitespace-nowrap items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
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

        <div
            v-if="editingMemberId"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
            @click.self="closeEditMember"
        >
            <div class="w-full max-w-3xl rounded-2xl bg-white p-5 shadow-2xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">Kemaskini Maklumat Ahli</h3>
                    <button type="button" class="text-sm font-semibold text-slate-500 hover:text-slate-700" @click="closeEditMember">Tutup</button>
                </div>

                <form class="grid grid-cols-1 gap-3 md:grid-cols-2" @submit.prevent="submitEditMember">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Nama</label>
                        <input v-model="editMemberForm.name" type="text" required class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">No. IC</label>
                        <input v-model="editMemberForm.nric" type="text" required class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Emel</label>
                        <input v-model="editMemberForm.email" type="email" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">No. Ahli</label>
                        <input v-model="editMemberForm.member_no" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Telefon</label>
                        <input v-model="editMemberForm.phone" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Jawatan</label>
                        <input v-model="editMemberForm.job_title" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Jabatan</label>
                        <input v-model="editMemberForm.department" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Negeri</label>
                        <input v-model="editMemberForm.state" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Cawangan</label>
                        <input v-model="editMemberForm.branch" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Alamat</label>
                        <input v-model="editMemberForm.address" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                            <input v-model="editMemberForm.first_login_completed" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Tandakan ahli sudah aktif log masuk pertama
                        </label>
                    </div>
                    <div class="md:col-span-2 flex items-center gap-2">
                        <button
                            type="submit"
                            :disabled="editMemberForm.processing"
                            class="inline-flex whitespace-nowrap rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {{ editMemberForm.processing ? 'Menyimpan...' : 'Simpan Kemaskini' }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex whitespace-nowrap rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            @click="closeEditMember"
                        >
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
