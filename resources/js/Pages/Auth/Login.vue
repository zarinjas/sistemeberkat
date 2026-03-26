<script setup>
import InputError from '@/Components/InputError.vue';
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    heroConfig: {
        type: Object,
        default: () => ({
            image_url: '',
            heading: 'Selamat Datang ke e-BERKAT!',
            subtext: 'Sistem Pengurusan Bantuan Digital Bersepadu yang pintar, pantas, dan telus.',
            overlay_color: '#020617',
            overlay_opacity: 60,
        }),
    },
});

const overlayStyle = computed(() => {
    const color = props.heroConfig.overlay_color || '#020617';
    const opacityPercent = props.heroConfig.overlay_opacity ?? 60;
    const alpha = Math.min(Math.max(opacityPercent, 0), 100) / 100;

    // If already rgba/hsla, just use directly
    if (typeof color === 'string' && color.startsWith('rgb')) {
        return { backgroundColor: color };
    }

    // Assume hex string like #rrggbb
    const hex = (color || '#020617').replace('#', '');
    const bigint = parseInt(hex.length === 3 ? hex.split('').map((c) => c + c).join('') : hex, 16);
    const r = (bigint >> 16) & 255;
    const g = (bigint >> 8) & 255;
    const b = bigint & 255;

    return { backgroundColor: `rgba(${r}, ${g}, ${b}, ${alpha})` };
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const firstTimeForm = useForm({
    nric: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const authMode = ref('login');

const quickRole = ref(null);

const demoCredentials = {
    applicant: {
        email: 'user@berkat.com',
        password: 'password',
    },
    reviewer: {
        email: 'reviewer@berkat.com',
        password: 'password',
    },
    superadmin: {
        email: 'superadmin@berkat.com',
        password: 'password',
    },
};

const submit = () => {
    form.post('/login', {
        onFinish: () => {
            form.reset('password');
            quickRole.value = null;
        },
    });
};

const submitFirstTime = () => {
    firstTimeForm.post(route('login.first-time'), {
        onFinish: () => {
            firstTimeForm.reset('password', 'password_confirmation');
        },
    });
};

const quickLogin = (role) => {
    const credentials = demoCredentials[role];

    if (!credentials || form.processing) {
        return;
    }

    quickRole.value = role;
    form.email = credentials.email;
    form.password = credentials.password;
    form.remember = true;

    submit();
};
</script>

<template>
    <Head title="Log Masuk" />

    <div class="min-h-screen grid lg:grid-cols-2">
        <!-- Left Column: Dynamic Branding -->
        <div
            class="hidden lg:flex items-center justify-center p-8 relative"
            :style="props.heroConfig.image_url ? { backgroundImage: `url(/storage/${props.heroConfig.image_url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}"
        >
            <!-- Default gradient if no image -->
            <div
                v-if="!props.heroConfig.image_url"
                class="absolute inset-0 bg-gradient-to-br from-blue-600 to-indigo-900"
            ></div>

            <!-- Overlay for text readability -->
            <div class="absolute inset-0" :style="overlayStyle"></div>

            <!-- Content -->
            <div class="relative z-10 text-center text-white max-w-md">
                <h1 class="text-4xl font-bold mb-4">{{ props.heroConfig.heading }}</h1>
                <p class="text-lg opacity-90">{{ props.heroConfig.subtext }}</p>
            </div>
        </div>

        <!-- Right Column: Login Form -->
        <div class="flex items-center justify-center p-8 bg-white">
            <div class="w-full max-w-md">
                <div class="mb-6 text-center">
                    <div v-if="$page.props.branding?.logo_url" class="mb-4 flex justify-center">
                        <img
                            :src="$page.props.branding.logo_url"
                            alt="e-BERKAT Logo"
                            class="h-14 w-auto object-contain"
                        />
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700">JANM BERKAT</p>
                    <h1 class="mt-2 text-2xl font-bold text-slate-900">Selamat Kembali!</h1>
                    <p class="mt-2 text-sm text-slate-600">Log masuk ke akaun anda</p>
                </div>

                <div v-if="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
                    {{ status }}
                </div>

                <div class="mb-4 grid grid-cols-2 overflow-hidden rounded-lg border border-slate-200 bg-slate-50 p-1 text-sm">
                    <button
                        type="button"
                        class="rounded-md px-3 py-2 font-semibold transition"
                        :class="authMode === 'login' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        @click="authMode = 'login'"
                    >
                        Log Masuk
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-2 font-semibold transition"
                        :class="authMode === 'first-time' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                        @click="authMode = 'first-time'"
                    >
                        Login Kali Pertama
                    </button>
                </div>

                <form v-if="authMode === 'login'" class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">E-mel</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            autocomplete="username"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="anda@contoh.com"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Kata Laluan</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="••••••••"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            Ingat saya
                        </label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm text-slate-600 underline decoration-slate-300 underline-offset-2 hover:text-slate-900"
                        >
                            Lupa kata laluan?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <svg
                            v-if="form.processing && !quickRole"
                            class="mr-2 h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        {{ form.processing && !quickRole ? 'Sedang log masuk...' : 'Log Masuk' }}
                    </button>
                </form>

                <form v-else class="space-y-4" @submit.prevent="submitFirstTime">
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                        Untuk pengguna kali pertama sahaja. Masukkan No. IC yang telah didaftarkan oleh pentadbir sistem.
                    </div>

                    <div>
                        <label for="nric" class="mb-1.5 block text-sm font-medium text-slate-700">No. IC</label>
                        <input
                            id="nric"
                            v-model="firstTimeForm.nric"
                            type="text"
                            required
                            autocomplete="off"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="contoh: 900101101010"
                        />
                        <InputError class="mt-2" :message="firstTimeForm.errors.nric" />
                    </div>

                    <div>
                        <label for="first_email" class="mb-1.5 block text-sm font-medium text-slate-700">E-mel</label>
                        <input
                            id="first_email"
                            v-model="firstTimeForm.email"
                            type="email"
                            required
                            autocomplete="username"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="anda@contoh.com"
                        />
                        <InputError class="mt-2" :message="firstTimeForm.errors.email" />
                    </div>

                    <div>
                        <label for="first_password" class="mb-1.5 block text-sm font-medium text-slate-700">Kata Laluan Baharu</label>
                        <input
                            id="first_password"
                            v-model="firstTimeForm.password"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Minimum 8 aksara"
                        />
                        <InputError class="mt-2" :message="firstTimeForm.errors.password" />
                    </div>

                    <div>
                        <label for="first_password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Sahkan Kata Laluan Baharu</label>
                        <input
                            id="first_password_confirmation"
                            v-model="firstTimeForm.password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Ulang kata laluan"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="firstTimeForm.processing"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ firstTimeForm.processing ? 'Sedang aktifkan akaun...' : 'Aktifkan Akaun Kali Pertama' }}
                    </button>
                </form>

                <div class="my-6 flex items-center gap-3">
                    <div class="h-px flex-1 bg-slate-200"></div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Akses Demo</p>
                    <div class="h-px flex-1 bg-slate-200"></div>
                </div>

                <div class="space-y-3">
                    <button
                        type="button"
                        :disabled="form.processing"
                        @click="quickLogin('applicant')"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <svg
                            v-if="form.processing && quickRole === 'applicant'"
                            class="mr-2 h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        👤 Pemohon (Applicant)
                    </button>

                    <button
                        type="button"
                        :disabled="form.processing"
                        @click="quickLogin('reviewer')"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-slate-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-600 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <svg
                            v-if="form.processing && quickRole === 'reviewer'"
                            class="mr-2 h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        🧾 Pegawai Penyemak (Reviewer/Agent)
                    </button>

                    <button
                        type="button"
                        :disabled="form.processing"
                        @click="quickLogin('superadmin')"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <svg
                            v-if="form.processing && quickRole === 'superadmin'"
                            class="mr-2 h-4 w-4 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        🛠️ Superadmin IT (System Admin)
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
