<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Create an account" />

        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-900">Create an account</h1>
            <p class="text-sm text-slate-500 mt-1">Register to access the B2B operations portal.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="name" value="Full name" class="text-slate-700" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1.5 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="John Doe"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Work email" class="text-slate-700" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1.5 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="name@company.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Password" class="text-slate-700" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1.5 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="Minimum 8 characters"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirm password" class="text-slate-700" />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1.5 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Repeat your password"
                />

                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center py-2.5 !bg-indigo-600 hover:!bg-indigo-500 focus:!ring-indigo-500 rounded-lg text-sm font-semibold shadow-sm transition-colors"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Create account
                </PrimaryButton>
            </div>

            <p class="text-center text-sm text-slate-500 pt-2 border-t border-slate-100">
                Already registered?
                <Link :href="route('login')" class="font-semibold text-indigo-600 hover:text-indigo-500">
                    Sign in
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
