<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    latestSchemasByCategory: {
        type: Array,
        default: () => [],
    },
    editingSchema: {
        type: Object,
        default: null,
    },
    allCategories: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const defaultCategories = [
    { key: 'pendidikan', label: 'Bantuan Pendidikan', persisted: false },
    { key: 'kesihatan', label: 'Bantuan Kesihatan', persisted: false },
];

const categoryOptions = ref((() => {
    const map = new Map();

    props.allCategories.forEach((category) => {
        if (category?.key && category?.label) {
            map.set(category.key, {
                key: category.key,
                label: category.label,
                persisted: true,
            });
        }
    });

    if (props.editingSchema?.category_key && props.editingSchema?.category_name) {
        map.set(props.editingSchema.category_key, {
            key: props.editingSchema.category_key,
            label: props.editingSchema.category_name,
            persisted: true,
        });
    }

    if (!map.size) {
        defaultCategories.forEach((category) => map.set(category.key, category));
    }

    return Array.from(map.values());
})());

const editingFormId = ref(props.editingSchema?.id || null);
const formCategoryKey = ref(props.editingSchema?.category_key || categoryOptions.value[0]?.key || 'pendidikan');
const formVersion = ref(props.editingSchema?.version || '');
const showPublishPreview = ref(false);
const formCardColor = ref(props.editingSchema?.card_color || '#f8fafc');
const formDescription = ref(props.editingSchema?.schema_json?.description || '');
const publishedSchemaJson = ref('');
const newCategoryName = ref('');
const editingCategoryKey = ref(null);
const editingCategoryName = ref('');
const actionError = ref('');
const isSubmitting = ref(false);

const flashSuccess = computed(() => page.props.flash?.success || '');
const flashError = computed(() => page.props.flash?.error || '');

const toolboxComponents = [
    { key: 'text', label: 'Teks Pendek' },
    { key: 'textarea', label: 'Teks Panjang' },
    { key: 'instruction', label: 'Teks Arahan' },
    { key: 'select', label: 'Pilihan Dropdown' },
    { key: 'radio', label: 'Pilihan Radio' },
    { key: 'file', label: 'Muat Naik Dokumen' },
    { key: 'checkbox', label: 'Checkbox Persetujuan' },
    { key: 'profile_field', label: 'Maklumat Profil (Autofill)' },
];

const PROFILE_KEY_OPTIONS = [
    { key: 'name', label: 'Nama Penuh' },
    { key: 'nric', label: 'No. IC (MyKad)' },
    { key: 'phone', label: 'No. Telefon' },
    { key: 'email', label: 'E-mel' },
    { key: 'tarikh_lahir', label: 'Tarikh Lahir' },
    { key: 'gender', label: 'Jantina' },
    { key: 'marital_status', label: 'Status Perkahwinan' },
    { key: 'address', label: 'Alamat' },
    { key: 'postcode', label: 'Poskod' },
    { key: 'city', label: 'Bandar' },
    { key: 'state', label: 'Negeri' },
    { key: 'job_title', label: 'Jawatan' },
    { key: 'department', label: 'Jabatan' },
    { key: 'employment_grade', label: 'Gred' },
    { key: 'member_no', label: 'No. Ahli' },
];

const slugify = (value) =>
    String(value || 'field')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '_')
        .replace(/_+/g, '_');

const toFieldState = (field) => ({
    id: Date.now() + Math.floor(Math.random() * 10000),
    type: field?.type || 'text',
    label: field?.label || 'Soalan Baru',
    content: field?.content || '',
    required: Boolean(field?.required),
    options: Array.isArray(field?.options) ? [...field.options] : [],
    optionsText: Array.isArray(field?.options) ? field.options.join(', ') : '',
    newOption: '',
    profile_key: field?.profile_key || '',
    readonly: Boolean(field?.readonly),
});

const formFields = ref(
    props.editingSchema?.schema_json?.fields?.length
        ? props.editingSchema.schema_json.fields.map((field) => toFieldState(field))
        : [toFieldState({ type: 'text', label: 'Soalan Baru' })],
);

const isEditingPublished = computed(() => props.editingSchema?.lifecycle_status === 'published');

const createField = (type = 'text') => {
    if (type === 'instruction') {
        return toFieldState({ type: 'instruction', content: 'Sila baca arahan ini.' });
    }

    if (type === 'radio') {
        return toFieldState({ type: 'radio', label: 'Pilihan Ya/Tidak', options: ['Ya', 'Tidak'] });
    }

    if (type === 'select') {
        return toFieldState({ type: 'select', label: 'Sila pilih', options: ['Pilihan 1', 'Pilihan 2'] });
    }

    if (type === 'file') {
        return toFieldState({ type: 'file', label: 'Muat naik dokumen sokongan' });
    }

    if (type === 'checkbox') {
        return toFieldState({ type: 'checkbox', label: 'Saya setuju dengan syarat permohonan' });
    }

    if (type === 'profile_field') {
        return toFieldState({ type: 'profile_field', label: 'Nama Penuh', profile_key: 'name', readonly: true });
    }

    return toFieldState({ type, label: 'Soalan Baru' });
};

const addField = (type) => {
    formFields.value.push(createField(type));
};

const moveFieldUp = (index) => {
    if (index < 1) {
        return;
    }

    const copy = [...formFields.value];
    const temp = copy[index - 1];
    copy[index - 1] = copy[index];
    copy[index] = temp;
    formFields.value = copy;
};

const moveFieldDown = (index) => {
    if (index >= formFields.value.length - 1) {
        return;
    }

    const copy = [...formFields.value];
    const temp = copy[index + 1];
    copy[index + 1] = copy[index];
    copy[index] = temp;
    formFields.value = copy;
};

const duplicateField = (index) => {
    const target = formFields.value[index];
    const cloned = toFieldState({
        ...target,
        options: target.type === 'radio'
            ? target.optionsText.split(',').map((option) => option.trim()).filter(Boolean)
            : target.options,
    });

    const copy = [...formFields.value];
    copy.splice(index + 1, 0, cloned);
    formFields.value = copy;
};

const removeField = (index) => {
    if (formFields.value.length === 1) {
        return;
    }

    const copy = [...formFields.value];
    copy.splice(index, 1);
    formFields.value = copy;
};

const addSelectOption = (field) => {
    if (!field.newOption?.trim()) {
        return;
    }

    field.options.push(field.newOption.trim());
    field.newOption = '';
};

const removeSelectOption = (field, optionIndex) => {
    field.options.splice(optionIndex, 1);
};

const normalizeCategoryKey = (value) => {
    return String(value || '')
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '_')
        .replace(/[^a-z0-9_-]/g, '');
};

const selectedCategoryOption = computed(() => {
    return categoryOptions.value.find((option) => option.key === formCategoryKey.value) || null;
});

const canAddCategory = computed(() => {
    const label = newCategoryName.value.trim();
    const key = normalizeCategoryKey(label);

    if (!key || !label) {
        return false;
    }

    return !categoryOptions.value.some((option) => option.key === key);
});

const addCategoryOption = () => {
    if (!canAddCategory.value) {
        return;
    }

    const label = newCategoryName.value.trim();
    const key = normalizeCategoryKey(label);

    categoryOptions.value.push({ key, label, persisted: false });
    formCategoryKey.value = key;

    newCategoryName.value = '';
};

const beginEditCategory = (category) => {
    editingCategoryKey.value = category.key;
    editingCategoryName.value = category.label;
};

const cancelEditCategory = () => {
    editingCategoryKey.value = null;
    editingCategoryName.value = '';
};

const saveCategoryEdit = (category) => {
    const newName = editingCategoryName.value.trim();
    if (!newName) {
        return;
    }

    if (category.persisted) {
        router.patch(route('admin.form-builder.categories.update', category.key), {
            category_name: newName,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                cancelEditCategory();
            },
        });

        return;
    }

    const newKey = normalizeCategoryKey(newName);
    if (!newKey) {
        actionError.value = 'Nama kategori tidak sah. Sila guna teks kategori yang jelas.';
        return;
    }

    const duplicate = categoryOptions.value.some((item) => item.key === newKey && item.key !== category.key);
    if (duplicate) {
        actionError.value = 'Nama kategori bertindih dengan kategori sedia ada.';
        return;
    }

    category.key = newKey;
    category.label = newName;

    if (formCategoryKey.value === editingCategoryKey.value) {
        formCategoryKey.value = newKey;
    }

    cancelEditCategory();
};

const removeCategory = (category) => {
    if (category.persisted) {
        router.delete(route('admin.form-builder.categories.destroy', category.key), {
            preserveScroll: true,
        });

        return;
    }

    categoryOptions.value = categoryOptions.value.filter((item) => item.key !== category.key);

    if (!categoryOptions.value.length) {
        const fallback = defaultCategories[0];
        categoryOptions.value = [fallback];
    }

    if (formCategoryKey.value === category.key) {
        formCategoryKey.value = categoryOptions.value[0].key;
    }
};

const buildPayload = () => {
    const version = formVersion.value?.trim();

    return {
        form_schema_id: editingFormId.value,
        category_key: formCategoryKey.value,
        card_color: formCardColor.value,
        category_name: selectedCategoryOption.value?.label || 'Bantuan Umum',
        description: formDescription.value?.trim() || null,
        version: version || null,
        fields: formFields.value.map((field, index) => ({
            order: index + 1,
            type: field.type,
            name: field.type === 'instruction' ? null : (field.type === 'profile_field' ? field.profile_key : slugify(field.label || `field_${index + 1}`)),
            label: field.type === 'instruction' ? null : field.label,
            content: field.type === 'instruction' ? field.content : null,
            required: field.type === 'instruction' ? false : field.required,
            options:
                field.type === 'select'
                    ? field.options.filter((option) => option?.trim())
                    : field.type === 'radio'
                        ? field.optionsText
                            .split(',')
                            .map((option) => option.trim())
                            .filter(Boolean)
                        : null,
            profile_key: field.type === 'profile_field' ? field.profile_key : null,
            readonly: field.type === 'profile_field' ? field.readonly : null,
        })),
        published_at: new Date().toISOString(),
    };
};

const preflightValidate = () => {
    if (!formCategoryKey.value) {
        return 'Sila pilih kategori sebelum simpan atau terbitkan borang.';
    }

    if (!formFields.value.length) {
        return 'Sila tambah sekurang-kurangnya satu medan borang.';
    }

    for (const field of formFields.value) {
        if (field.type === 'instruction' && !String(field.content || '').trim()) {
            return 'Medan jenis arahan mesti mempunyai kandungan teks.';
        }

        if (field.type !== 'instruction' && !String(field.label || '').trim()) {
            return 'Semua medan soalan mesti mempunyai label.';
        }

        if (field.type === 'select') {
            const options = field.options.filter((option) => String(option || '').trim());
            if (!options.length) {
                return 'Medan dropdown mesti mempunyai sekurang-kurangnya satu pilihan.';
            }
        }

        if (field.type === 'radio') {
            const options = String(field.optionsText || '')
                .split(',')
                .map((option) => option.trim())
                .filter(Boolean);

            if (!options.length) {
                return 'Medan radio mesti mempunyai sekurang-kurangnya satu pilihan.';
            }
        }

        if (field.type === 'profile_field' && !field.profile_key) {
            return 'Medan Maklumat Profil mesti mempunyai jenis data profil yang dipilih.';
        }
    }

    return '';
};

const saveDraft = () => {
    actionError.value = preflightValidate();
    if (actionError.value) {
        return;
    }

    isSubmitting.value = true;
    router.post(route('admin.form-builder.draft'), buildPayload(), {
        preserveScroll: true,
        onError: (errors) => {
            actionError.value = Object.values(errors || {})[0] || 'Simpan draft gagal. Sila semak input borang.';
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

const publishForm = () => {
    actionError.value = preflightValidate();
    if (actionError.value) {
        return;
    }

    const payload = buildPayload();
    publishedSchemaJson.value = JSON.stringify(payload, null, 2);

    isSubmitting.value = true;
    router.post(route('admin.form-builder.publish'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            showPublishPreview.value = true;
        },
        onError: (errors) => {
            actionError.value = Object.values(errors || {})[0] || 'Terbitkan borang gagal. Sila semak input borang.';
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

const onCategoryKeyChange = () => {
    if (!categoryOptions.value.some((option) => option.key === formCategoryKey.value)) {
        formCategoryKey.value = categoryOptions.value[0]?.key || 'pendidikan';
    }
};
</script>

<template>
    <Head title="Pembina Borang" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-slate-800">Pembina Borang</h2>
                <Link
                    :href="route('forms.manage')"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Senarai Borang
                </Link>
            </div>
        </template>

        <div class="py-6 sm:py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-6 lg:grid-cols-4">
                    <aside class="lg:col-span-1">
                        <div class="top-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100 lg:sticky">
                            <h3 class="text-base font-semibold text-slate-900">Tambah Medan</h3>
                            <p class="mt-1 text-xs text-slate-500">Klik jenis medan untuk tambah ke senarai borang.</p>

                            <div class="mt-4 space-y-2">
                                <button
                                    v-for="item in toolboxComponents"
                                    :key="item.key"
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                                    @click="addField(item.key)"
                                >
                                    <span>{{ item.label }}</span>
                                    <span class="text-slate-400">+</span>
                                </button>
                            </div>
                        </div>
                    </aside>

                    <section class="lg:col-span-3">
                        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100 sm:p-6">
                            <div v-if="flashSuccess" class="mb-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                                {{ flashSuccess }}
                            </div>

                            <div v-if="flashError || actionError" class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                                {{ flashError || actionError }}
                            </div>

                            <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-medium text-indigo-800">
                                Arahan BM: Susun medan guna butang Naik/Turun. Simpan dahulu sebagai Draft sebelum terbitkan.
                            </div>

                            <div v-if="isEditingPublished" class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700">
                                Borang ini asalnya Published. Anda kini mengedit salinan; gunakan Draft/Publish untuk kemas kini versi.
                            </div>

                            <div class="mt-5 grid gap-4 md:grid-cols-3">
                                <div class="md:col-span-2">
                                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Kategori Bantuan</label>
                                    <select
                                        v-model="formCategoryKey"
                                        class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        @change="onCategoryKeyChange"
                                    >
                                        <option v-for="option in categoryOptions" :key="option.key" :value="option.key">{{ option.label }}</option>
                                    </select>
                                </div>

                                <div class="md:col-span-1">
                                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Warna Tema Kad</label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="formCardColor"
                                            type="color"
                                            class="h-[38px] w-full max-w-[5rem] cursor-pointer rounded border border-slate-300 p-0 shadow-sm"
                                        >
                                        <span class="text-xs font-mono uppercase text-slate-500">{{ formCardColor || '#F8FAFC' }}</span>
                                    </div>
                                </div>

                                <div class="md:col-span-3 mt-1">
                                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Keterangan Borang</label>
                                    <textarea
                                        v-model="formDescription"
                                        rows="2"
                                        class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Contoh: Borang pendaftaran untuk bantuan permaidani surau dan infrastruktur"
                                    ></textarea>
                                </div>
                                <!-- Versi borang dibuang mengikut permintaan -->
                            </div>

                            <div class="mt-4 rounded-xl border border-amber-100 bg-amber-50 p-4">
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-amber-700">Tambah Kategori Baharu</label>
                                <div class="grid gap-3 md:grid-cols-2">
                                    <input
                                        v-model="newCategoryName"
                                        type="text"
                                        placeholder="Contoh: Bantuan Kebajikan"
                                        class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:bg-amber-300"
                                        :disabled="!canAddCategory"
                                        @click="addCategoryOption"
                                    >
                                        Tambah Kategori
                                    </button>
                                </div>

                                <div class="mt-4 space-y-2">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Urus Kategori Sedia Ada</p>

                                    <div
                                        v-for="category in categoryOptions"
                                        :key="`cat-${category.key}`"
                                        class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <template v-if="editingCategoryKey === category.key">
                                                <input
                                                    v-model="editingCategoryName"
                                                    type="text"
                                                    class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                            </template>
                                            <template v-else>
                                                <p class="truncate text-sm font-medium text-slate-700">{{ category.label }}</p>
                                            </template>
                                        </div>

                                        <div class="flex items-center gap-1">
                                            <template v-if="editingCategoryKey === category.key">
                                                <button
                                                    type="button"
                                                    class="rounded-lg bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-indigo-500"
                                                    @click="saveCategoryEdit(category)"
                                                >
                                                    Simpan
                                                </button>
                                                <button
                                                    type="button"
                                                    class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                    @click="cancelEditCategory"
                                                >
                                                    Batal
                                                </button>
                                            </template>
                                            <template v-else>
                                                <button
                                                    type="button"
                                                    class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                    @click="beginEditCategory(category)"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    class="rounded-lg border border-rose-300 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                                                    @click="removeCategory(category)"
                                                >
                                                    Buang
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 space-y-4">
                                <article
                                    v-for="(field, index) in formFields"
                                    :key="field.id"
                                    class="rounded-xl border p-4"
                                    :class="{
                                        'border-blue-200 bg-blue-50/60': field.type === 'instruction',
                                        'border-emerald-200 bg-emerald-50/60': field.type === 'profile_field',
                                        'border-slate-200 bg-slate-50/50': field.type !== 'instruction' && field.type !== 'profile_field',
                                    }"
                                >
                                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm font-semibold text-slate-800">Medan #{{ index + 1 }} · {{ field.type }}</p>
                                        <div class="flex flex-wrap items-center gap-1">
                                            <button
                                                type="button"
                                                class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                :disabled="index === 0"
                                                @click="moveFieldUp(index)"
                                            >
                                                Naik
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                :disabled="index === formFields.length - 1"
                                                @click="moveFieldDown(index)"
                                            >
                                                Turun
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-lg border border-emerald-300 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"
                                                @click="duplicateField(index)"
                                            >
                                                Duplicate
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-lg border border-rose-300 bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                                                @click="removeField(index)"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </div>

                                    <div v-if="field.type === 'instruction'" class="space-y-2">
                                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-blue-700">Teks Arahan</label>
                                        <textarea
                                            v-model="field.content"
                                            rows="4"
                                            class="block w-full rounded-lg border-blue-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        ></textarea>
                                    </div>

                                    <div v-else-if="field.type === 'profile_field'" class="space-y-3">
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Label Soalan</label>
                                            <input
                                                v-model="field.label"
                                                type="text"
                                                class="block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Data Profil</label>
                                            <select
                                                v-model="field.profile_key"
                                                class="block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="" disabled>Pilih data profil...</option>
                                                <option v-for="opt in PROFILE_KEY_OPTIONS" :key="opt.key" :value="opt.key">{{ opt.label }}</option>
                                            </select>
                                        </div>
                                        <label class="inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2">
                                            <span class="text-sm font-medium text-slate-700">Readonly (pemohon tidak boleh ubah)</span>
                                            <input v-model="field.readonly" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        </label>
                                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-700">
                                            Nilai akan diisi secara automatik dari profil pemohon semasa borang dipaparkan.
                                        </div>
                                    </div>

                                    <div v-else class="space-y-3">
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Label Soalan</label>
                                            <input
                                                v-model="field.label"
                                                type="text"
                                                class="block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                        </div>

                                        <div v-if="field.type === 'radio'">
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Pilihan (pisah dengan koma)</label>
                                            <input
                                                v-model="field.optionsText"
                                                type="text"
                                                class="block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                placeholder="Ya, Tidak"
                                            >
                                        </div>

                                        <div v-if="field.type === 'select'" class="rounded-lg border border-indigo-100 bg-indigo-50/40 p-3">
                                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-indigo-700">Pilihan Dropdown</p>

                                            <div class="space-y-2">
                                                <div
                                                    v-for="(option, optionIndex) in field.options"
                                                    :key="`${field.id}-${optionIndex}`"
                                                    class="flex items-center gap-2"
                                                >
                                                    <input
                                                        v-model="field.options[optionIndex]"
                                                        type="text"
                                                        class="block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                    <button
                                                        type="button"
                                                        class="rounded-lg border border-rose-300 bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                                                        @click="removeSelectOption(field, optionIndex)"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="mt-2 flex items-center gap-2">
                                                <input
                                                    v-model="field.newOption"
                                                    type="text"
                                                    placeholder="Tambah pilihan"
                                                    class="block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    @keydown.enter.prevent="addSelectOption(field)"
                                                >
                                                <button
                                                    type="button"
                                                    class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-500"
                                                    @click="addSelectOption(field)"
                                                >
                                                    Tambah
                                                </button>
                                            </div>
                                        </div>

                                        <label class="inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2">
                                            <span class="text-sm font-medium text-slate-700">Required</span>
                                            <input v-model="field.required" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        </label>
                                    </div>
                                </article>
                            </div>

                            <div class="mt-6 flex flex-wrap gap-2 border-t border-slate-200 pt-5">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                    :disabled="isSubmitting"
                                    @click="saveDraft"
                                >
                                    Simpan Draft
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500"
                                    :disabled="isSubmitting"
                                    @click="publishForm"
                                >
                                    {{ isSubmitting ? 'Memproses...' : 'Terbitkan Borang' }}
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <Modal :show="showPublishPreview" max-width="2xl" @close="showPublishPreview = false">
            <div class="p-6">
                <div class="flex flex-col items-center">
                    <ApplicationLogo class="h-14 w-auto mb-4" />
                    <h3 class="text-lg font-semibold text-slate-900">Borang Berjaya Diterbitkan</h3>
                    <p class="mt-1 text-sm text-slate-600">Borang telah diterbitkan mengikut lifecycle baharu.</p>
                </div>
                <div class="mt-5 flex justify-end">
                    <button
                        type="button"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
                        @click="showPublishPreview = false"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
