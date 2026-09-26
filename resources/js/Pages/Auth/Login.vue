<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Sign in" />

        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-900">Sign in to your account</h1>
            <p class="text-sm text-slate-500 mt-1">Enter your credentials to access the workspace.</p>
        </div>

        <div v-if="status" class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm font-medium text-emerald-700 border border-emerald-200">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" value="Email address" class="text-slate-700" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1.5 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm"
                    v-model="form.email"
                    required
                    autofocus
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
                    autocomplete="current-password"
                    placeholder="Enter your password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" class="text-indigo-600 focus:ring-indigo-500 border-slate-300 rounded" />
                    <span class="ms-2 text-sm text-slate-600">Remember me</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500 focus:outline-none"
                >
                    Forgot password?
                </Link>
            </div>

            <div class="pt-1">
                <PrimaryButton
                    class="w-full justify-center py-2.5 !bg-indigo-600 hover:!bg-indigo-500 focus:!ring-indigo-500 rounded-lg text-sm font-semibold shadow-sm transition-colors"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Sign in
                </PrimaryButton>
            </div>

            <p class="text-center text-sm text-slate-500 pt-2 border-t border-slate-100">
                Don't have an account?
                <Link :href="route('register')" class="font-semibold text-indigo-600 hover:text-indigo-500">
                    Create an account
                </Link>
            </p>
        </form>
    </GuestLayout>
</template>
