<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    forms: {
        type: Array,
        default: () => [],
    },
});

const previewOpen = ref(false);
const selectedSchema = ref(null);
const confirmOpen = ref(false);
const confirmTitle = ref('');
const confirmMessage = ref('');
const confirmButtonLabel = ref('Teruskan');
const confirmButtonClass = ref('bg-indigo-600 hover:bg-indigo-500');
const pendingAction = ref(null);
const selectedCategory = ref('all');
const selectedStatus = ref('all');
const selectedPublisher = ref('all');
const keyword = ref('');
const sortBy = ref('published_at');
const sortDirection = ref('desc');

const categoryOptions = computed(() => {
    return ['all', ...new Set(props.forms.map((form) => form.category_key).filter(Boolean))];
});

const publisherOptions = computed(() => {
    return ['all', ...new Set(props.forms.map((form) => form.published_by_email).filter(Boolean))];
});

const filteredForms = computed(() => {
    const query = keyword.value.trim().toLowerCase();

    return props.forms.filter((form) => {
        const matchCategory = selectedCategory.value === 'all' || form.category_key === selectedCategory.value;
        const matchStatus =
            selectedStatus.value === 'all' ||
            (selectedStatus.value === 'active' && form.is_active) ||
            (selectedStatus.value === 'archived' && !form.is_active);
        const matchPublisher = selectedPublisher.value === 'all' || form.published_by_email === selectedPublisher.value;

        if (!query) {
            return matchCategory && matchStatus && matchPublisher;
        }

        const searchSource = [
            form.category_name,
            form.category_key,
            form.version,
            form.published_by,
            form.published_by_email,
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return matchCategory && matchStatus && matchPublisher && searchSource.includes(query);
    });
});

const normalizeVersion = (version) => {
    const value = String(version || '').toLowerCase().replace(/^v/, '');
    const numeric = Number(value);

    return Number.isNaN(numeric) ? 0 : numeric;
};

const sortedForms = computed(() => {
    const forms = [...filteredForms.value];

    forms.sort((left, right) => {
        if (left.is_active !== right.is_active) {
            return left.is_active ? -1 : 1;
        }

        if (sortBy.value === 'published_at') {
            const leftTime = left.published_at ? new Date(left.published_at).getTime() : 0;
            const rightTime = right.published_at ? new Date(right.published_at).getTime() : 0;
            return sortDirection.value === 'asc' ? leftTime - rightTime : rightTime - leftTime;
        }

        if (sortBy.value === 'version') {
            const leftVersion = normalizeVersion(left.version);
            const rightVersion = normalizeVersion(right.version);
            return sortDirection.value === 'asc' ? leftVersion - rightVersion : rightVersion - leftVersion;
        }

        if (sortBy.value === 'fields_count') {
            return sortDirection.value === 'asc' ? left.fields_count - right.fields_count : right.fields_count - left.fields_count;
        }

        const leftValue = String(left[sortBy.value] || '').toLowerCase();
        const rightValue = String(right[sortBy.value] || '').toLowerCase();

        if (leftValue === rightValue) {
            return 0;
        }

        if (sortDirection.value === 'asc') {
            return leftValue > rightValue ? 1 : -1;
        }

        return leftValue < rightValue ? 1 : -1;
    });

    return forms;
});

const groupedSummary = computed(() => {
    const grouped = {};

    filteredForms.value.forEach((form) => {
        if (!grouped[form.category_key]) {
            grouped[form.category_key] = {
                categoryName: form.category_name,
                total: 0,
                activeVersion: '-',
            };
        }

        grouped[form.category_key].total += 1;
        if (form.is_active) {
            grouped[form.category_key].activeVersion = form.version;
        }
    });

    return Object.values(grouped);
});

const resetFilters = () => {
    selectedCategory.value = 'all';
    selectedStatus.value = 'all';
    selectedPublisher.value = 'all';
    keyword.value = '';
    sortBy.value = 'published_at';
    sortDirection.value = 'desc';
};

const toggleSort = (field) => {
    if (sortBy.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
        return;
    }

    sortBy.value = field;
    sortDirection.value = field === 'published_at' ? 'desc' : 'asc';
};

const sortIndicator = (field) => {
    if (sortBy.value !== field) {
        return '↕';
    }

    return sortDirection.value === 'asc' ? '↑' : '↓';
};

const openPreview = (form) => {
    selectedSchema.value = form;
    previewOpen.value = true;
};

const activateForm = (formId) => {
    router.patch(route('forms.manage.activate', formId), {}, {
        preserveScroll: true,
    });
};

const archiveForm = (formId) => {
    router.patch(route('forms.manage.archive', formId), {}, {
        preserveScroll: true,
    });
};

const duplicateForm = (formId) => {
    router.post(route('forms.manage.duplicate', formId), {}, {
        preserveScroll: true,
    });
};

const deleteForm = (formId) => {
    router.delete(route('forms.manage.destroy', formId), {
        preserveScroll: true,
    });
};

const editForm = (formId) => {
    router.get(route('forms.builder'), { form_schema_id: formId });
};

const openConfirm = ({ title, message, buttonLabel, buttonClass, action }) => {
    confirmTitle.value = title;
    confirmMessage.value = message;
    confirmButtonLabel.value = buttonLabel;
    confirmButtonClass.value = buttonClass;
    pendingAction.value = action;
    confirmOpen.value = true;
};

const closeConfirm = () => {
    confirmOpen.value = false;
    pendingAction.value = null;
};

const runConfirmedAction = () => {
    if (typeof pendingAction.value === 'function') {
        pendingAction.value();
    }

    closeConfirm();
};

const confirmActivate = (form) => {
    openConfirm({
        title: 'Sahkan Aktifkan Borang',
        message: `Anda pasti mahu aktifkan versi ${form.version} untuk ${form.category_name}? Versi aktif semasa akan dinyahaktifkan.`,
        buttonLabel: 'Ya, Aktifkan',
        buttonClass: 'bg-indigo-600 hover:bg-indigo-500',
        action: () => activateForm(form.id),
    });
};

const confirmArchive = (form) => {
    openConfirm({
        title: 'Sahkan Arkib Borang',
        message: `Anda pasti mahu arkibkan borang ${form.category_name} (${form.version})? Borang ini tidak lagi boleh dipilih sebagai aktif.`,
        buttonLabel: 'Ya, Arkibkan',
        buttonClass: 'bg-amber-600 hover:bg-amber-500',
        action: () => archiveForm(form.id),
    });
};

const confirmDelete = (form) => {
    openConfirm({
        title: 'Sahkan Padam Borang',
        message: `Tindakan ini tidak boleh dibatalkan. Anda pasti mahu padam borang ${form.category_name} (${form.version})?`,
        buttonLabel: 'Ya, Padam',
        buttonClass: 'bg-rose-600 hover:bg-rose-500',
        action: () => deleteForm(form.id),
    });
};
</script>

<template>
    <Head title="Pengurusan Semua Borang" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Pengurusan Semua Borang</h2>
                <Link
                    :href="route('forms.builder')"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                >
                    Buka Form Builder
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="item in groupedSummary"
                        :key="item.categoryName"
                        class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100 hover:shadow-md transition-shadow"
                    >
                        <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-slate-50"></div>
                        <p class="relative z-10 text-xs font-bold uppercase tracking-wider text-indigo-500">Kategori Borang</p>
                        <h3 class="relative z-10 mt-1 text-xl font-bold text-slate-900">{{ item.categoryName }}</h3>
                        
                        <div class="relative z-10 mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                            <p class="text-xs font-semibold text-slate-500">Jumlah Rekod: <span class="text-slate-700">{{ item.total }}</span></p>
                            <span v-if="item.activeVersion !== '-'" class="px-2 py-1 text-[10px] uppercase font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md">Status: Aktif</span>
                            <span v-else class="px-2 py-1 text-[10px] uppercase font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-md">Tiada Borang Aktif</span>
                        </div>
                    </article>
                </section>

                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
                    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-5">
                        <div class="lg:col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Carian</label>
                            <input
                                v-model="keyword"
                                type="text"
                                placeholder="Cari kategori, versi atau penerbit..."
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</label>
                            <select
                                v-model="selectedCategory"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="all">Semua</option>
                                <option v-for="category in categoryOptions.filter((item) => item !== 'all')" :key="category" :value="category">
                                    {{ category }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                            <select
                                v-model="selectedStatus"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="all">Semua</option>
                                <option value="active">Aktif</option>
                                <option value="archived">Arkib</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Penerbit</label>
                            <select
                                v-model="selectedPublisher"
                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="all">Semua</option>
                                <option v-for="publisher in publisherOptions.filter((item) => item !== 'all')" :key="publisher" :value="publisher">
                                    {{ publisher }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3 flex justify-end">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                            @click="resetFilters"
                        >
                            Reset Filter
                        </button>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">Senarai Semua Borang Diterbitkan</h3>
                    </div>

                    <div v-if="!sortedForms.length" class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500">
                        Tiada borang dijumpai untuk kriteria carian ini.
                    </div>

                    <div v-else class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-600 w-1/4">
                                        <button type="button" class="inline-flex items-center gap-1.5 uppercase text-xs tracking-wider hover:text-indigo-600 transition-colors" @click="toggleSort('category_key')">
                                            Kategori <span class="text-[10px] rounded bg-white px-1 shadow-sm">{{ sortIndicator('category_key') }}</span>
                                        </button>
                                    </th>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-600 uppercase text-xs tracking-wider">Penerbit</th>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-600">
                                        <button type="button" class="inline-flex items-center gap-1.5 uppercase text-xs tracking-wider hover:text-indigo-600 transition-colors" @click="toggleSort('published_at')">
                                            Tarikh <span class="text-[10px] rounded bg-white px-1 shadow-sm">{{ sortIndicator('published_at') }}</span>
                                        </button>
                                    </th>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-600 uppercase text-xs tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-left font-semibold text-slate-600 uppercase text-xs tracking-wider">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                                <tr v-for="form in sortedForms" :key="form.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="font-bold text-slate-900">{{ form.category_name }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="font-medium text-slate-800">{{ form.published_by || '-' }}</div>
                                        <div class="mt-0.5 text-[11px] text-slate-500">{{ form.published_by_email || '-' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="text-xs text-slate-600 font-medium">{{ form.published_at ? new Date(form.published_at).toLocaleString() : '-' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="flex flex-col items-start gap-1.5">
                                            <span
                                                class="inline-flex rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide shadow-sm"
                                                :class="form.lifecycle_status === 'published' ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : form.lifecycle_status === 'draft' ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-slate-50 border-slate-200 text-slate-600'"
                                            >
                                                {{ form.lifecycle_status }}
                                            </span>
                                            <span
                                                class="inline-flex rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide shadow-sm"
                                                :class="form.is_active ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-slate-50 border-slate-200 text-slate-600'"
                                            >
                                                {{ form.is_active ? 'aktif' : 'tidak aktif' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:text-indigo-600"
                                                @click="editForm(form.id)"
                                            >
                                                Edit Borang
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-700 shadow-sm transition-all hover:bg-emerald-100"
                                                @click="duplicateForm(form.id)"
                                            >
                                                Salin (Draft)
                                            </button>
                                            <button
                                                v-if="!form.is_active && form.lifecycle_status === 'published'"
                                                type="button"
                                                class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-white shadow-sm transition-all hover:bg-indigo-500"
                                                @click="confirmActivate(form)"
                                            >
                                                Aktifkan
                                            </button>
                                            <button
                                                v-if="form.lifecycle_status === 'published'"
                                                type="button"
                                                class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-amber-700 shadow-sm transition-all hover:bg-amber-100"
                                                @click="confirmArchive(form)"
                                            >
                                                Arkibkan
                                            </button>
                                            <button
                                                v-if="form.lifecycle_status === 'draft' || form.lifecycle_status === 'archived'"
                                                type="button"
                                                class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-rose-700 shadow-sm transition-all hover:bg-rose-100"
                                                @click="confirmDelete(form)"
                                            >
                                                Padam
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <Modal :show="previewOpen" max-width="2xl" @close="previewOpen = false">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-slate-900">Preview Schema JSON</h3>
                <p class="mt-1 text-sm text-slate-500">
                    {{ selectedSchema?.category_name }} • {{ selectedSchema?.version }}
                </p>

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-950 p-4">
                    <pre class="max-h-96 overflow-auto text-xs leading-5 text-emerald-300">{{ JSON.stringify(selectedSchema?.schema_json || {}, null, 2) }}</pre>
                </div>

                <div class="mt-5 flex justify-end">
                    <button
                        type="button"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                        @click="previewOpen = false"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmOpen" max-width="lg" @close="closeConfirm">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-slate-900">{{ confirmTitle }}</h3>
                <p class="mt-2 text-sm text-slate-600">{{ confirmMessage }}</p>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        @click="closeConfirm"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-white"
                        :class="confirmButtonClass"
                        @click="runConfirmedAction"
                    >
                        {{ confirmButtonLabel }}
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
