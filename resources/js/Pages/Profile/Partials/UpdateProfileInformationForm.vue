<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const avatarPreview = ref(user.profile_photo_url);
const malaysiaStates = [
    'Johor',
    'Kedah',
    'Kelantan',
    'Melaka',
    'Negeri Sembilan',
    'Pahang',
    'Perak',
    'Perlis',
    'Pulau Pinang',
    'Sabah',
    'Sarawak',
    'Selangor',
    'Terengganu',
    'W.P. Kuala Lumpur',
    'W.P. Labuan',
    'W.P. Putrajaya',
];

const nricRaw = ref(user.nric ?? '');

const formatNric = (val) => {
    const digits = val.replace(/\D/g, '').slice(0, 12);
    if (digits.length <= 6) return digits;
    if (digits.length <= 8) return `${digits.slice(0, 6)}-${digits.slice(6)}`;
    return `${digits.slice(0, 6)}-${digits.slice(6, 8)}-${digits.slice(8)}`;
};

const birthDateFromNric = computed(() => {
    const digits = nricRaw.value.replace(/\D/g, '');
    if (digits.length < 6) return '';
    const yy = parseInt(digits.slice(0, 2));
    const mm = digits.slice(2, 4);
    const dd = digits.slice(4, 6);
    const year = yy <= parseInt(new Date().getFullYear().toString().slice(2)) ? 2000 + yy : 1900 + yy;
    return `${dd}/${mm}/${year}`;
});

const onNricInput = (e) => {
    const formatted = formatNric(e.target.value);
    nricRaw.value = formatted;
    form.nric = formatted;
};

const form = useForm({
    name: user.name,
    email: user.email,
    phone: user.phone ?? '',
    nric: user.nric ?? '',
    job_title: user.job_title ?? '',
    state: user.state ?? '',
    department: user.department ?? '',
    address: user.address ?? '',
    postcode: user.postcode ?? '',
    city: user.city ?? '',
    gender: user.gender ?? '',
    marital_status: user.marital_status ?? '',
    avatar: null,
    remove_avatar: false,
    _method: 'patch',
});

const avatarLabel = computed(() => (avatarPreview.value ? 'Tukar Gambar Profil' : 'Muat Naik Gambar Profil'));

const onAvatarChange = (event) => {
    const file = event.target.files?.[0] ?? null;
    form.avatar = file;
    form.remove_avatar = false;

    if (file) {
        avatarPreview.value = URL.createObjectURL(file);
    }
};

const removeAvatar = () => {
    form.avatar = null;
    form.remove_avatar = true;
    avatarPreview.value = null;
};

const submit = () => {
    form.post(route('profile.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.avatar = null;
            form.remove_avatar = false;
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Maklumat Profil
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Kemas kini maklumat profil untuk dipaparkan pada Kad Ahli Digital.
            </p>
        </header>

        <form
            @submit.prevent="submit"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel value="Gambar Profil" />

                <div class="mt-2 flex items-center gap-4">
                    <img
                        :src="avatarPreview || 'https://via.placeholder.com/96x96.png?text=User'"
                        alt="Avatar"
                        class="h-16 w-16 rounded-full border border-gray-200 object-cover"
                    />

                    <div class="space-y-2">
                        <label class="inline-flex cursor-pointer items-center rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            <span>{{ avatarLabel }}</span>
                            <input
                                type="file"
                                accept="image/png,image/jpeg,image/jpg,image/webp"
                                class="hidden"
                                @change="onAvatarChange"
                            >
                        </label>

                        <button
                            v-if="avatarPreview"
                            type="button"
                            class="block text-sm font-medium text-red-600 hover:text-red-700"
                            @click="removeAvatar"
                        >
                            Buang gambar
                        </button>
                    </div>
                </div>

                <InputError class="mt-2" :message="form.errors.avatar" />
            </div>

            <div>
                <InputLabel for="name" value="Nama" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="E-mel" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="phone" value="No Telefon" />
                    <TextInput
                        id="phone"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.phone"
                        autocomplete="tel"
                    />
                    <InputError class="mt-2" :message="form.errors.phone" />
                </div>

                <div>
                    <InputLabel for="nric" value="No Kad Pengenalan" />
                    <TextInput
                        id="nric"
                        type="text"
                        class="mt-1 block w-full"
                        :value="nricRaw"
                        @input="onNricInput"
                        placeholder="940729-04-5407"
                        maxlength="14"
                    />
                    <p v-if="birthDateFromNric" class="mt-1 text-xs text-gray-500">
                        Tarikh Lahir: {{ birthDateFromNric }}
                    </p>
                    <InputError class="mt-2" :message="form.errors.nric" />
                </div>

                <div>
                    <InputLabel for="gender" value="Jantina" />
                    <select
                        id="gender"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        v-model="form.gender"
                    >
                        <option value="">Pilih Jantina</option>
                        <option value="lelaki">Lelaki</option>
                        <option value="perempuan">Perempuan</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.gender" />
                </div>

                <div>
                    <InputLabel for="marital_status" value="Status Perkahwinan" />
                    <select
                        id="marital_status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        v-model="form.marital_status"
                    >
                        <option value="">Pilih Status</option>
                        <option value="berkahwin">Berkahwin</option>
                        <option value="bujang">Bujang</option>
                        <option value="bercerai">Bercerai</option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.marital_status" />
                </div>

                <div>
                    <InputLabel for="job_title" value="Jawatan" />
                    <TextInput
                        id="job_title"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.job_title"
                    />
                    <InputError class="mt-2" :message="form.errors.job_title" />
                </div>

                <div>
                    <InputLabel for="department" value="Jabatan" />
                    <TextInput
                        id="department"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.department"
                    />
                    <InputError class="mt-2" :message="form.errors.department" />
                </div>
            </div>

            <div>
                <InputLabel for="address" value="Alamat Rumah" />
                <textarea
                    id="address"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    rows="3"
                    v-model="form.address"
                    placeholder="No. rumah, Jalan, Taman/Kampung"
                ></textarea>
                <InputError class="mt-2" :message="form.errors.address" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <InputLabel for="postcode" value="Poskod" />
                    <TextInput
                        id="postcode"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.postcode"
                        placeholder="68000"
                        maxlength="5"
                    />
                    <InputError class="mt-2" :message="form.errors.postcode" />
                </div>

                <div>
                    <InputLabel for="city" value="Bandar" />
                    <TextInput
                        id="city"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.city"
                    />
                    <InputError class="mt-2" :message="form.errors.city" />
                </div>

                <div>
                    <InputLabel for="state" value="Negeri" />
                    <select
                        id="state"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        v-model="form.state"
                    >
                        <option value="">Pilih Negeri</option>
                        <option
                            v-for="state in malaysiaStates"
                            :key="state"
                            :value="state"
                        >
                            {{ state }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.state" />
                </div>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Alamat e-mel anda belum disahkan.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Klik di sini untuk hantar semula e-mel pengesahan.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    Pautan pengesahan baharu telah dihantar ke e-mel anda.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Berjaya disimpan.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
