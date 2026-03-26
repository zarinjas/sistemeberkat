<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    heroConfig: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    heading: props.heroConfig.heading,
    subtext: props.heroConfig.subtext,
    overlay_color: props.heroConfig.overlay_color || '#020617',
    overlay_opacity: props.heroConfig.overlay_opacity ?? 60,
    logo: null,
    image: null,
});

const imagePreview = ref(null);

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const submit = () => {
    form.post(route('admin.hero-settings.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            // Clear only the image field; keep heading & subtext
            form.image = null;
            form.logo = null;
            imagePreview.value = null;
        },
    });
};

const removeImage = () => {
    form.delete(route('admin.hero-settings.remove-image'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Tetapan Hero Login" />

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div v-if="$page.props.flash?.success" class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ $page.props.flash.success }}
                </div>

                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Tetapan Hero Halaman Login</h1>
                    <p class="mt-2 text-gray-600">Kawal kandungan bahagian kiri halaman login (tajuk, teks penerangan, dan gambar latar).</p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="heading" class="block text-sm font-medium text-gray-700">Tajuk Utama (Heading)</label>
                        <input
                            id="heading"
                            v-model="form.heading"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Selamat Datang ke e-BERKAT!"
                        />
                        <p class="mt-1 text-sm text-gray-500">Teks utama yang dipaparkan di bahagian kiri halaman login.</p>
                        <InputError class="mt-2" :message="form.errors.heading" />
                    </div>

                    <div>
                        <label for="subtext" class="block text-sm font-medium text-gray-700">Teks Penerangan (Subtext)</label>
                        <textarea
                            id="subtext"
                            v-model="form.subtext"
                            rows="3"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Sistem Pengurusan Bantuan Digital Bersepadu yang pintar, pantas, dan telus."
                        ></textarea>
                        <p class="mt-1 text-sm text-gray-500">Teks penerangan tambahan di bawah tajuk utama.</p>
                        <InputError class="mt-2" :message="form.errors.subtext" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="overlay_color" class="block text-sm font-medium text-gray-700">Warna Overlay</label>
                            <select
                                id="overlay_color"
                                v-model="form.overlay_color"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            >
                                <option value="#020617">Gelap Neutral (Hampir Hitam)</option>
                                <option value="#0f172a">Biru Gelap (Slate)</option>
                                <option value="#1d4ed8">Biru Korporat</option>
                                <option value="#0f766e">Hijau Teal</option>
                                <option value="#6d28d9">Ungu</option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">Pilih tona warna overlay di atas gambar/gradient.</p>
                            <InputError class="mt-2" :message="form.errors.overlay_color" />
                        </div>

                        <div>
                            <label for="overlay_opacity" class="block text-sm font-medium text-gray-700">Kelegapan Overlay (Opacity)</label>
                            <input
                                id="overlay_opacity"
                                v-model.number="form.overlay_opacity"
                                type="range"
                                min="10"
                                max="90"
                                step="5"
                                class="mt-2 block w-full"
                            />
                            <p class="mt-1 text-sm text-gray-500">Kelegapan semasa: <span class="font-semibold">{{ form.overlay_opacity }}%</span>. Lebih tinggi = lebih gelap.</p>
                            <InputError class="mt-2" :message="form.errors.overlay_opacity" />
                        </div>
                    </div>

                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700">Gambar Latar (Background Image)</label>
                        <input
                            id="image"
                            type="file"
                            accept="image/*"
                            @change="handleImageChange"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                        />
                        <p class="mt-1 text-sm text-gray-500">
                            Muat naik gambar untuk latar bahagian kiri. Saiz maksimum: 2MB.
                            Format: JPEG, PNG, JPG, GIF. Saiz paparan terbaik: sekurang-kurangnya 1200x800 piksel (nisbah 3:2 atau 16:9).
                        </p>
                        <InputError class="mt-2" :message="form.errors.image" />

                        <div v-if="imagePreview || heroConfig.image_url" class="mt-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">Pratonton:</p>
                            <div class="relative inline-block">
                                <img
                                    :src="imagePreview || (heroConfig.image_url ? `/storage/${heroConfig.image_url}` : '')"
                                    alt="Hero preview"
                                    class="h-32 w-48 object-cover rounded-md border"
                                />
                                <button
                                    v-if="heroConfig.image_url && !imagePreview"
                                    type="button"
                                    @click="removeImage"
                                    class="absolute -top-2 -right-2 rounded-full bg-red-500 p-1 text-white hover:bg-red-600"
                                    title="Buang gambar"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4 mt-2">
                        <h2 class="text-sm font-semibold text-gray-800 mb-3">Logo Aplikasi</h2>
                        <label for="logo" class="block text-sm font-medium text-gray-700">Muat Naik Logo</label>
                        <input
                            id="logo"
                            type="file"
                            accept="image/*"
                            @change="(e) => (form.logo = e.target.files[0])"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                        />
                        <p class="mt-1 text-sm text-gray-500">Saiz maksimum: 1MB. Format: JPEG, PNG, JPG, GIF, SVG. Disyorkan latar lutsinar (transparent) untuk paparan terbaik.</p>
                        <InputError class="mt-2" :message="form.errors.logo" />

                        <div v-if="heroConfig.logo_url" class="mt-3 flex items-center gap-4">
                            <div class="text-xs text-gray-500">Logo semasa:</div>
                            <img :src="`/storage/${heroConfig.logo_url}`" alt="Logo semasa" class="h-10 object-contain" />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            <svg v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            Simpan Tetapan
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>