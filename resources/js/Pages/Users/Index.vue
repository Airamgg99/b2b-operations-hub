<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { notifyError } from '@/Utils/swal';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    companies: {
        type: Array,
        default: () => [],
    },
    archivedCount: {
        type: Number,
        default: 0,
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            company_id: '',
            sort: 'id',
            direction: 'desc',
            trashed: '',
        }),
    },
    availableRoles: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const currentUserId = page.props.auth?.user?.id;
const userRoles = page.props.auth?.user?.roles || [];
const canManageUsers = userRoles.includes('Super Admin') || userRoles.includes('Company Admin');

// Search, Company Filter, Sorting & Trashed State
const search = ref(props.filters.search || '');
const companyFilter = ref(props.filters.company_id || '');
const sortField = ref(props.filters.sort || 'id');
const sortDirection = ref(props.filters.direction || 'desc');
const trashedFilter = ref(props.filters.trashed || '');
let searchTimeout = null;

const fetchUsers = () => {
    router.get(
        route('users.index'),
        {
            search: search.value,
            company_id: companyFilter.value,
            sort: sortField.value,
            direction: sortDirection.value,
            trashed: trashedFilter.value,
        },
        { preserveState: true, replace: true, preserveScroll: true }
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchUsers();
    }, 300);
});

watch(companyFilter, () => {
    fetchUsers();
});

const setTrashedFilter = (value) => {
    trashedFilter.value = value;
    fetchUsers();
};

const sortBy = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    fetchUsers();
};

const clearSearch = () => {
    search.value = '';
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
    });
};

const getInitials = (name) => {
    if (!name) return 'U';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
};

// Quick View (Show) Modal State
const isShowModalOpen = ref(false);
const selectedUser = ref(null);

const openShowModal = (user) => {
    selectedUser.value = user;
    isShowModalOpen.value = true;
};

const closeShowModal = () => {
    isShowModalOpen.value = false;
    selectedUser.value = null;
};

const switchFromShowToEdit = () => {
    const user = selectedUser.value;
    closeShowModal();
    if (user) {
        openEditModal(user);
    }
};

// Create / Edit Modal State
const isFormModalOpen = ref(false);
const isEditing = ref(false);
const editingUserId = ref(null);

const form = useForm({
    name: '',
    email: '',
    company_id: '',
    password: '',
    password_confirmation: '',
    role: 'User',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingUserId.value = null;
    form.reset();
    form.clearErrors();
    isFormModalOpen.value = true;
};

const openEditModal = (user) => {
    isEditing.value = true;
    editingUserId.value = user.id;
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.company_id = user.company_id || '';
    form.password = '';
    form.password_confirmation = '';
    form.role = user.roles && user.roles.length > 0 ? user.roles[0].name : 'User';
    form.password = '';
    isFormModalOpen.value = true;
};

const closeFormModal = () => {
    isFormModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => closeFormModal(),
        onError: () => notifyError('Please check the form for validation errors.'),
    };

    if (isEditing.value) {
        form.put(route('users.update', editingUserId.value), options);
    } else {
        form.post(route('users.store'), options);
    }
};

// Archive (Soft Delete) Modal State
const isDeleteModalOpen = ref(false);
const userToDelete = ref(null);
const deleteForm = useForm({});

const confirmDelete = (user) => {
    if (user.id === currentUserId) {
        notifyError('You cannot archive your own active session account.');
        return;
    }
    userToDelete.value = user;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    userToDelete.value = null;
};

const executeDelete = () => {
    if (!userToDelete.value) return;
    deleteForm.delete(route('users.destroy', userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
        onError: () => notifyError('Unable to archive the selected user.'),
    });
};

// Restore Confirmation Modal State
const isRestoreModalOpen = ref(false);
const userToRestore = ref(null);
const restoreForm = useForm({});

const confirmRestore = (user) => {
    userToRestore.value = user;
    isRestoreModalOpen.value = true;
};

const closeRestoreModal = () => {
    isRestoreModalOpen.value = false;
    userToRestore.value = null;
};

const executeRestore = () => {
    if (!userToRestore.value) return;
    restoreForm.patch(route('users.restore', userToRestore.value.id), {
        preserveScroll: true,
        onSuccess: () => closeRestoreModal(),
        onError: () => notifyError('Unable to restore the selected user.'),
    });
};
</script>

<template>
    <Head title="Users" />

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl font-bold text-slate-900">Users Directory</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                        {{ users.total }} {{ trashedFilter === 'only' ? 'archived' : 'active' }}
                    </span>
                </div>
                <p class="text-sm text-slate-500 mt-1">
                    Manage B2B user accounts, organization assignments, and platform access.
                </p>
            </div>

            <PrimaryButton
                v-if="canManageUsers"
                @click="openCreateModal"
                class="!bg-indigo-600 hover:!bg-indigo-500 focus:!ring-indigo-500 rounded-lg text-sm font-semibold shadow-sm px-4 py-2.5 shrink-0 self-start sm:self-auto"
            >
                <svg class="w-4 h-4 mr-2 -ml-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New User
            </PrimaryButton>
        </div>

        <!-- Main Directory Card -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <!-- Integrated Search, Company Filter & View Tabs Toolbar -->
            <div class="p-4 sm:px-6 border-b border-slate-200 bg-slate-50/50 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 flex-1 flex-wrap">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by ID, name, email..."
                            class="block w-full pl-10 pr-8 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow"
                        />
                        <button
                            v-if="search"
                            @click="clearSearch"
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600"
                            title="Clear search"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Company Filter Select -->
                    <div class="w-full sm:w-56">
                        <select
                            v-model="companyFilter"
                            class="block w-full py-2 pl-3 pr-8 bg-white border border-slate-300 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">All Organizations</option>
                            <option v-for="company in companies" :key="company.id" :value="company.id">
                                {{ company.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Segmented View Toggle (Active vs Archived) -->
                    <div class="inline-flex p-1 bg-slate-200/70 rounded-lg self-start sm:self-auto">
                        <button
                            @click="setTrashedFilter('')"
                            type="button"
                            :class="[
                                'px-3 py-1.5 rounded-md text-xs font-semibold transition-all',
                                trashedFilter === ''
                                    ? 'bg-white text-slate-900 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            Active Users
                        </button>
                        <button
                            @click="setTrashedFilter('only')"
                            type="button"
                            :class="[
                                'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all',
                                trashedFilter === 'only'
                                    ? 'bg-white text-slate-900 shadow-2xs'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Archived</span>
                            <span
                                v-if="archivedCount > 0"
                                class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 text-slate-700 font-bold"
                            >
                                {{ archivedCount }}
                            </span>
                        </button>
                    </div>
                </div>

                <div class="text-xs text-slate-500 xl:text-right shrink-0">
                    Showing <span class="font-semibold text-slate-800">{{ users.from || 0 }}</span> to
                    <span class="font-semibold text-slate-800">{{ users.to || 0 }}</span> of
                    <span class="font-semibold text-slate-800">{{ users.total }}</span> users
                </div>
            </div>

            <!-- Responsive Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse divide-y divide-slate-200">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200">
                            <!-- Sortable ID Column -->
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-24">
                                <button
                                    @click="sortBy('id')"
                                    type="button"
                                    class="group inline-flex items-center gap-1.5 uppercase font-semibold hover:text-indigo-600 focus:outline-none transition-colors"
                                    :class="{ 'text-indigo-600': sortField === 'id' }"
                                >
                                    <span>ID</span>
                                    <svg
                                        class="w-3.5 h-3.5 transition-transform"
                                        :class="[
                                            sortField === 'id' ? 'text-indigo-600' : 'text-slate-400 opacity-60 group-hover:opacity-100',
                                            sortField === 'id' && sortDirection === 'desc' ? 'rotate-180' : ''
                                        ]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                            </th>

                            <!-- Sortable User Name Column -->
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <button
                                    @click="sortBy('name')"
                                    type="button"
                                    class="group inline-flex items-center gap-1.5 uppercase font-semibold hover:text-indigo-600 focus:outline-none transition-colors"
                                    :class="{ 'text-indigo-600': sortField === 'name' }"
                                >
                                    <span>User</span>
                                    <svg
                                        class="w-3.5 h-3.5 transition-transform"
                                        :class="[
                                            sortField === 'name' ? 'text-indigo-600' : 'text-slate-400 opacity-60 group-hover:opacity-100',
                                            sortField === 'name' && sortDirection === 'desc' ? 'rotate-180' : ''
                                        ]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                            </th>

                            <!-- Sortable Email Column -->
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <button
                                    @click="sortBy('email')"
                                    type="button"
                                    class="group inline-flex items-center gap-1.5 uppercase font-semibold hover:text-indigo-600 focus:outline-none transition-colors"
                                    :class="{ 'text-indigo-600': sortField === 'email' }"
                                >
                                    <span>Email</span>
                                    <svg
                                        class="w-3.5 h-3.5 transition-transform"
                                        :class="[
                                            sortField === 'email' ? 'text-indigo-600' : 'text-slate-400 opacity-60 group-hover:opacity-100',
                                            sortField === 'email' && sortDirection === 'desc' ? 'rotate-180' : ''
                                        ]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                            </th>

                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Organization
                            </th>
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <!-- Dedicated ID Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-mono font-semibold text-slate-500 bg-slate-100 px-2 py-1 rounded-md border border-slate-200">
                                    #{{ user.id }}
                                </span>
                            </td>

                            <!-- User Name & Initials Avatar -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3.5">
                                    <div
                                        :class="[
                                            'w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs shrink-0 shadow-xs',
                                            user.deleted_at ? 'bg-slate-200 text-slate-500' : 'bg-indigo-600 text-white'
                                        ]"
                                    >
                                        {{ getInitials(user.name) }}
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="text-sm font-semibold text-slate-900">
                                            {{ user.name }}
                                        </div>
                                        <span
                                            v-if="user.roles && user.roles.length > 0 && user.roles[0].name !== 'User'"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-indigo-100 text-indigo-700 border border-indigo-200"
                                        >
                                            {{ user.roles[0].name }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Email -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                {{ user.email }}
                            </td>

                            <!-- Assigned Company Badge -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    v-if="user.company"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-800 border border-slate-200"
                                >
                                    <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    {{ user.company.name }}
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium text-slate-400 bg-slate-50 border border-slate-200/70 italic"
                                >
                                    Unassigned
                                </span>
                            </td>

                            <!-- Status Pill -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    v-if="user.deleted_at"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Archived
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Active
                                </span>
                            </td>

                            <!-- Compact Icon-Only Action Buttons -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <!-- Show Details Button -->
                                    <button
                                        @click="openShowModal(user)"
                                        type="button"
                                        title="View user profile"
                                        class="p-2 rounded-lg text-slate-500 bg-white border border-slate-200 hover:bg-slate-100 hover:text-slate-900 transition-colors shadow-2xs"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    <!-- Actions for Active Users -->
                                    <template v-if="!user.deleted_at">
                                        <template v-if="canManageUsers">
                                            <button
                                                @click="openEditModal(user)"
                                                type="button"
                                                title="Edit user"
                                                class="p-2 rounded-lg text-indigo-600 bg-white border border-slate-200 hover:bg-indigo-50 hover:border-indigo-200 transition-colors shadow-2xs"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>

                                            <button
                                                @click="confirmDelete(user)"
                                                type="button"
                                                :disabled="user.id === currentUserId"
                                                :title="user.id === currentUserId ? 'Cannot archive your own account' : 'Archive user'"
                                                :class="[
                                                    'p-2 rounded-lg bg-white border border-slate-200 transition-colors shadow-2xs',
                                                    user.id === currentUserId
                                                        ? 'text-slate-300 cursor-not-allowed'
                                                        : 'text-red-600 hover:bg-red-50 hover:border-red-200'
                                                ]"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </template>
                                    </template>

                                    <!-- Restore Action for Archived Users -->
                                    <button
                                        v-else
                                        @click="confirmRestore(user)"
                                        type="button"
                                        title="Restore user"
                                        class="p-2 rounded-lg text-emerald-600 bg-white border border-slate-200 hover:bg-emerald-50 hover:border-emerald-200 transition-colors shadow-2xs"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="users.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="text-sm font-semibold text-slate-700">
                                    {{ trashedFilter === 'only' ? 'No archived users found.' : 'No users found matching your criteria.' }}
                                </p>
                                <p class="text-xs text-slate-400 mt-1">
                                    {{ trashedFilter === 'only' ? 'Archived user accounts will appear here for recovery.' : 'Try adjusting your search or organization filter.' }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <Pagination :links="users.links" />
        </div>

        <!-- Quick View (Show) User Modal -->
        <Modal :show="isShowModalOpen" @close="closeShowModal" maxWidth="md">
            <div v-if="selectedUser" class="p-6 space-y-6">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-base shrink-0 shadow-sm">
                            {{ getInitials(selectedUser.name) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-bold text-slate-900">{{ selectedUser.name }}</h2>
                                <span class="text-xs font-mono font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                    #{{ selectedUser.id }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">{{ selectedUser.email }}</p>
                        </div>
                    </div>

                    <span
                        v-if="selectedUser.deleted_at"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        Archived
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Active
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200/80">
                    <div class="sm:col-span-2">
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Assigned Organization</span>
                        <div class="mt-1">
                            <span
                                v-if="selectedUser.company"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-white text-slate-800 border border-slate-200 shadow-2xs"
                            >
                                {{ selectedUser.company.name }}
                            </span>
                            <span v-else class="text-sm text-slate-400 italic">No organization assigned</span>
                        </div>
                    </div>

                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Registered On</span>
                        <p class="mt-1 text-sm font-medium text-slate-700">
                            {{ formatDate(selectedUser.created_at) }}
                        </p>
                    </div>

                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">
                            {{ selectedUser.deleted_at ? 'Archived On' : 'Last Updated' }}
                        </span>
                        <p class="mt-1 text-sm font-medium text-slate-700">
                            {{ formatDate(selectedUser.deleted_at || selectedUser.updated_at) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                    <SecondaryButton @click="closeShowModal" type="button">
                        Close
                    </SecondaryButton>
                    <PrimaryButton
                        v-if="!selectedUser.deleted_at"
                        @click="switchFromShowToEdit"
                        type="button"
                        class="!bg-indigo-600 hover:!bg-indigo-500 focus:!ring-indigo-500"
                    >
                        Edit User
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Create / Edit User Modal -->
        <Modal :show="isFormModalOpen" @close="closeFormModal" maxWidth="md">
            <form @submit.prevent="submitForm" class="p-6 space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-lg font-bold text-slate-900">
                        {{ isEditing ? 'Edit User Account' : 'Create New User' }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ isEditing ? 'Update user credentials and organization assignment.' : 'Register a new B2B member and assign an organization.' }}
                    </p>
                </div>

                <div>
                    <InputLabel for="name" value="Full Name" class="text-slate-700" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1.5 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm"
                        placeholder="Jane Doe"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="email" value="Corporate Email" class="text-slate-700" />
                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="mt-1.5 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm"
                        placeholder="jane.doe@company.com"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel for="company_id" value="Assigned Organization" class="text-slate-700" />
                    <select
                        id="company_id"
                        v-model="form.company_id"
                        class="mt-1.5 block w-full py-2 px-3 border border-slate-300 bg-white rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">— Unassigned (Internal / Global) —</option>
                        <option v-for="company in companies" :key="company.id" :value="company.id">
                            {{ company.name }} {{ !company.is_active ? '(Inactive)' : '' }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.company_id" />
                </div>

                <div class="mt-4">
                    <InputLabel for="role" value="System Role" class="text-slate-700" />
                    <select
                        id="role"
                        v-model="form.role"
                        required
                        class="mt-1.5 block w-full py-2 px-3 border border-slate-300 bg-white rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option v-for="role in availableRoles" :key="role" :value="role">
                            {{ role }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.role" />
                    <p class="text-xs text-slate-500 mt-1">
                        Company Admins can manage other users and view billing details.
                    </p>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <InputLabel
                        for="password"
                        :value="isEditing ? 'New Password (leave blank to keep current)' : 'Password'"
                        class="text-slate-700"
                    />
                    <TextInput
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="mt-1.5 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm"
                        placeholder="Minimum 8 characters"
                        :required="!isEditing"
                        autocomplete="new-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="Confirm Password" class="text-slate-700" />
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-1.5 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm"
                        placeholder="Repeat password"
                        :required="!isEditing || form.password.length > 0"
                        autocomplete="new-password"
                    />
                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <SecondaryButton @click="closeFormModal" type="button">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton
                        class="!bg-indigo-600 hover:!bg-indigo-500 focus:!ring-indigo-500"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        {{ isEditing ? 'Save Changes' : 'Create User' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Archive (Soft Delete) Confirmation Modal -->
        <Modal :show="isDeleteModalOpen" @close="closeDeleteModal" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">Archive User Account</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Are you sure you want to archive <span class="font-semibold text-slate-900">{{ userToDelete?.name }}</span>?
                    You can restore this account at any time from the Archived tab.
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeDeleteModal" type="button">
                        Cancel
                    </SecondaryButton>
                    <DangerButton
                        @click="executeDelete"
                        :class="{ 'opacity-25': deleteForm.processing }"
                        :disabled="deleteForm.processing"
                    >
                        Confirm Archive
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <!-- Restore Confirmation Modal -->
        <Modal :show="isRestoreModalOpen" @close="closeRestoreModal" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">Restore User Account</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Do you want to restore <span class="font-semibold text-slate-900">{{ userToRestore?.name }}</span> back to the active users directory?
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeRestoreModal" type="button">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton
                        @click="executeRestore"
                        class="!bg-emerald-600 hover:!bg-emerald-500 focus:!ring-emerald-500"
                        :class="{ 'opacity-25': restoreForm.processing }"
                        :disabled="restoreForm.processing"
                    >
                        Confirm Restore
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </div>
</template>
