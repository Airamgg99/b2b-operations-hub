<script setup>
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <div class="max-w-7xl mx-auto space-y-6">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Profile</h2>
            <p class="text-sm text-slate-500">Manage your account settings and security credentials.</p>
        </div>

        <div class="bg-white p-4 shadow-sm border border-slate-200 sm:rounded-lg sm:p-8">
            <UpdateProfileInformationForm
                :must-verify-email="mustVerifyEmail"
                :status="status"
                class="max-w-xl"
            />
        </div>

        <div class="bg-white p-4 shadow-sm border border-slate-200 sm:rounded-lg sm:p-8">
            <UpdatePasswordForm class="max-w-xl" />
        </div>

        <div class="bg-white p-4 shadow-sm border border-slate-200 sm:rounded-lg sm:p-8">
            <DeleteUserForm class="max-w-xl" />
        </div>
    </div>
</template>
