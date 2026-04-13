<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    ajkContent: {
        type: Object,
        default: () => ({
            image_url: null,
            updated_at: null,
            updated_by: '',
        }),
    },
});

const page = usePage();
const isSuperadmin = Boolean(page.props.auth?.user?.is_superadmin);
const ajkImage = computed(() => props.ajkContent?.image_url || null);

const ajkForm = useForm({
    image: null,
});

const onAjkImageChange = (event) => {
    ajkForm.image = event.target.files?.[0] ?? null;
};

const uploadAjkImage = () => {
    ajkForm.post(route('info-center.ajk.upload'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            ajkForm.reset();
        },
    });
};

const removeAjkImage = () => {
    if (!window.confirm('Padam imej Senarai AJK semasa?')) {
        return;
    }

    router.delete(route('info-center.ajk.remove'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Pusat Info • Senarai AJK" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">Pusat Info • Senarai AJK BERKAT</h2>
        </template>

        <div :class="isSuperadmin ? 'page-shell px-4 sm:px-6 lg:px-8' : 'mx-auto max-w-7xl -mt-1 space-y-4 px-4 sm:px-6 lg:px-8'">
            <section v-if="isSuperadmin" class="surface-card">
                <h3 class="section-title">Senarai AJK BERKAT</h3>
                <p class="section-subtitle" v-if="isSuperadmin">Superadmin upload imej PNG untuk paparan pengguna.</p>
            </section>

            <section class="surface-card">
                <div v-if="isSuperadmin" class="space-y-4">
                    <form class="grid grid-cols-1 gap-3 md:grid-cols-12" @submit.prevent="uploadAjkImage">
                        <input
                            type="file"
                            accept="image/png"
                            required
                            class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 md:col-span-9"
                            @change="onAjkImageChange"
                        >
                        <button
                            type="submit"
                            :disabled="ajkForm.processing"
                            class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60 md:col-span-3"
                        >
                            {{ ajkForm.processing ? 'Upload...' : 'Upload PNG' }}
                        </button>
                    </form>

                    <p v-if="ajkForm.errors.image" class="text-sm text-rose-600">{{ ajkForm.errors.image }}</p>
                    <p class="text-xs text-slate-500">Format dibenarkan: PNG sahaja.</p>
                </div>

                <div v-if="ajkImage" class="mt-4 space-y-3 rounded-2xl border border-slate-200 bg-white p-4">
                    <img :src="ajkImage" alt="Senarai AJK BERKAT" class="w-full rounded-xl border border-slate-200 object-contain">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-slate-500">
                            Dikemaskini: {{ props.ajkContent?.updated_at || '-' }}
                            <span v-if="props.ajkContent?.updated_by">oleh {{ props.ajkContent.updated_by }}</span>
                        </p>
                        <button
                            v-if="isSuperadmin"
                            type="button"
                            class="rounded-xl bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500"
                            @click="removeAjkImage"
                        >
                            Padam Imej
                        </button>
                    </div>
                </div>

                <div v-else class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
                    Senarai AJK belum dimuat naik lagi.
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
