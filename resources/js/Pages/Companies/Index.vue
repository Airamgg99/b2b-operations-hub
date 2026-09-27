<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { notifyError } from '@/Utils/swal';

const props = defineProps({
    companies: {
        type: Object,
        required: true,
    },
    archivedCount: {
        type: Number,
        default: 0,
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            sort: 'id',
            direction: 'desc',
            trashed: '',
        }),
    },
});

// Search, Sorting & Trashed Filter State
const search = ref(props.filters.search || '');
const sortField = ref(props.filters.sort || 'id');
const sortDirection = ref(props.filters.direction || 'desc');
const trashedFilter = ref(props.filters.trashed || '');
let searchTimeout = null;

const fetchCompanies = () => {
    router.get(
        route('companies.index'),
        {
            search: search.value,
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
        fetchCompanies();
    }, 300);
});

const setTrashedFilter = (value) => {
    trashedFilter.value = value;
    fetchCompanies();
};

const sortBy = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    fetchCompanies();
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

// Quick View (Show) Modal State
const isShowModalOpen = ref(false);
const selectedCompany = ref(null);

const openShowModal = (company) => {
    selectedCompany.value = company;
    isShowModalOpen.value = true;
};

const closeShowModal = () => {
    isShowModalOpen.value = false;
    selectedCompany.value = null;
};

const switchFromShowToEdit = () => {
    const company = selectedCompany.value;
    closeShowModal();
    if (company) {
        openEditModal(company);
    }
};

// Create / Edit Modal State
const isFormModalOpen = ref(false);
const isEditing = ref(false);
const editingCompanyId = ref(null);

const form = useForm({
    name: '',
    vat_number: '',
    is_active: true,
});

const openCreateModal = () => {
    isEditing.value = false;
    editingCompanyId.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    isFormModalOpen.value = true;
};

const openEditModal = (company) => {
    isEditing.value = true;
    editingCompanyId.value = company.id;
    form.clearErrors();
    form.name = company.name;
    form.vat_number = company.vat_number;
    form.is_active = Boolean(company.is_active);
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
        form.put(route('companies.update', editingCompanyId.value), options);
    } else {
        form.post(route('companies.store'), options);
    }
};

// Delete (Archive) Confirmation Modal State
const isDeleteModalOpen = ref(false);
const companyToDelete = ref(null);
const deleteForm = useForm({});

const confirmDelete = (company) => {
    companyToDelete.value = company;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    companyToDelete.value = null;
};

const executeDelete = () => {
    if (!companyToDelete.value) return;
    deleteForm.delete(route('companies.destroy', companyToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
        onError: () => notifyError('Unable to archive the selected company.'),
    });
};

// Restore Confirmation Modal State
const isRestoreModalOpen = ref(false);
const companyToRestore = ref(null);
const restoreForm = useForm({});

const confirmRestore = (company) => {
    companyToRestore.value = company;
    isRestoreModalOpen.value = true;
};

const closeRestoreModal = () => {
    isRestoreModalOpen.value = false;
    companyToRestore.value = null;
};

const executeRestore = () => {
    if (!companyToRestore.value) return;
    restoreForm.patch(route('companies.restore', companyToRestore.value.id), {
        preserveScroll: true,
        onSuccess: () => closeRestoreModal(),
        onError: () => notifyError('Unable to restore the selected company.'),
    });
};
</script>

<template>
    <Head title="Companies" />

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl font-bold text-slate-900">Companies Directory</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                        {{ companies.total }} {{ trashedFilter === 'only' ? 'archived' : 'active' }}
                    </span>
                </div>
                <p class="text-sm text-slate-500 mt-1">
                    Manage B2B client organizations, VAT records, and operational status.
                </p>
            </div>

            <PrimaryButton
                @click="openCreateModal"
                class="!bg-indigo-600 hover:!bg-indigo-500 focus:!ring-indigo-500 rounded-lg text-sm font-semibold shadow-sm px-4 py-2.5 shrink-0 self-start sm:self-auto"
            >
                <svg class="w-4 h-4 mr-2 -ml-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Company
            </PrimaryButton>
        </div>

        <!-- Main Directory Card -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <!-- Integrated Search, View Tabs & Summary Toolbar -->
            <div class="p-4 sm:px-6 border-b border-slate-200 bg-slate-50/50 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 flex-1">
                    <!-- Search Input -->
                    <div class="relative w-full sm:max-w-xs">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by ID, name, or VAT..."
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
                            Active Directory
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

                <div class="text-xs text-slate-500 lg:text-right">
                    Showing <span class="font-semibold text-slate-800">{{ companies.from || 0 }}</span> to
                    <span class="font-semibold text-slate-800">{{ companies.to || 0 }}</span> of
                    <span class="font-semibold text-slate-800">{{ companies.total }}</span> organizations
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

                            <!-- Sortable Company Column -->
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <button
                                    @click="sortBy('name')"
                                    type="button"
                                    class="group inline-flex items-center gap-1.5 uppercase font-semibold hover:text-indigo-600 focus:outline-none transition-colors"
                                    :class="{ 'text-indigo-600': sortField === 'name' }"
                                >
                                    <span>Company</span>
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

                            <!-- Sortable VAT Number Column -->
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <button
                                    @click="sortBy('vat_number')"
                                    type="button"
                                    class="group inline-flex items-center gap-1.5 uppercase font-semibold hover:text-indigo-600 focus:outline-none transition-colors"
                                    :class="{ 'text-indigo-600': sortField === 'vat_number' }"
                                >
                                    <span>VAT Number</span>
                                    <svg
                                        class="w-3.5 h-3.5 transition-transform"
                                        :class="[
                                            sortField === 'vat_number' ? 'text-indigo-600' : 'text-slate-400 opacity-60 group-hover:opacity-100',
                                            sortField === 'vat_number' && sortDirection === 'desc' ? 'rotate-180' : ''
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
                                Team Size
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
                            v-for="company in companies.data"
                            :key="company.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <!-- Dedicated ID Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-mono font-semibold text-slate-500 bg-slate-100 px-2 py-1 rounded-md border border-slate-200">
                                    #{{ company.id }}
                                </span>
                            </td>

                            <!-- Company Name & Avatar -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3.5">
                                    <div
                                        :class="[
                                            'w-9 h-9 rounded-lg flex items-center justify-center font-bold text-sm shrink-0 shadow-xs',
                                            company.deleted_at ? 'bg-slate-200 text-slate-500' : 'bg-slate-900 text-indigo-400'
                                        ]"
                                    >
                                        {{ company.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <button
                                        @click="openShowModal(company)"
                                        type="button"
                                        class="text-sm font-semibold text-slate-900 hover:text-indigo-600 transition-colors truncate text-left focus:outline-none"
                                    >
                                        {{ company.name }}
                                    </button>
                                </div>
                            </td>

                            <!-- VAT Badge -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-semibold uppercase tracking-wide bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ company.vat_number }}
                                </span>
                            </td>

                            <!-- Users Count -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 text-sm text-slate-600">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span class="font-medium text-slate-700">{{ company.users_count }}</span>
                                    <span class="text-slate-400">{{ company.users_count === 1 ? 'member' : 'members' }}</span>
                                </div>
                            </td>

                            <!-- Status Pill -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    v-if="company.deleted_at"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Archived
                                </span>
                                <span
                                    v-else
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold',
                                        company.is_active
                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                            : 'bg-slate-100 text-slate-600 border border-slate-200'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            company.is_active ? 'bg-emerald-500' : 'bg-slate-400'
                                        ]"
                                    ></span>
                                    {{ company.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <!-- Compact Icon-Only Action Buttons -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <!-- Show Details Button -->
                                    <button
                                        @click="openShowModal(company)"
                                        type="button"
                                        title="View company details"
                                        class="p-2 rounded-lg text-slate-500 bg-white border border-slate-200 hover:bg-slate-100 hover:text-slate-900 transition-colors shadow-2xs"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    <!-- Actions for Active Directory -->
                                    <template v-if="!company.deleted_at">
                                        <button
                                            @click="openEditModal(company)"
                                            type="button"
                                            title="Edit company"
                                            class="p-2 rounded-lg text-indigo-600 bg-white border border-slate-200 hover:bg-indigo-50 hover:border-indigo-200 transition-colors shadow-2xs"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <button
                                            @click="confirmDelete(company)"
                                            type="button"
                                            title="Archive company"
                                            class="p-2 rounded-lg text-red-600 bg-white border border-slate-200 hover:bg-red-50 hover:border-red-200 transition-colors shadow-2xs"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </template>

                                    <!-- Restore Action for Archived Companies -->
                                    <button
                                        v-else
                                        @click="confirmRestore(company)"
                                        type="button"
                                        title="Restore company"
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
                        <tr v-if="companies.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="text-sm font-semibold text-slate-700">
                                    {{ trashedFilter === 'only' ? 'No archived companies found.' : 'No companies found matching your criteria.' }}
                                </p>
                                <p class="text-xs text-slate-400 mt-1">
                                    {{ trashedFilter === 'only' ? 'Archived organizations will appear here for recovery.' : 'Try adjusting your search query or register a new organization.' }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <Pagination :links="companies.links" />
        </div>

        <!-- Quick View (Show) Company Modal -->
        <Modal :show="isShowModalOpen" @close="closeShowModal" maxWidth="md">
            <div v-if="selectedCompany" class="p-6 space-y-6">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-slate-900 text-indigo-400 flex items-center justify-center font-bold text-lg shrink-0 shadow-sm">
                            {{ selectedCompany.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-bold text-slate-900">{{ selectedCompany.name }}</h2>
                                <span class="text-xs font-mono font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                    #{{ selectedCompany.id }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">Organization Overview & Audit Record</p>
                        </div>
                    </div>

                    <span
                        v-if="selectedCompany.deleted_at"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        Archived
                    </span>
                    <span
                        v-else
                        :class="[
                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold shrink-0',
                            selectedCompany.is_active
                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                : 'bg-slate-100 text-slate-600 border border-slate-200'
                        ]"
                    >
                        <span
                            :class="[
                                'w-1.5 h-1.5 rounded-full',
                                selectedCompany.is_active ? 'bg-emerald-500' : 'bg-slate-400'
                            ]"
                        ></span>
                        {{ selectedCompany.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200/80">
                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">VAT / Tax ID</span>
                        <div class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-semibold uppercase bg-white text-slate-800 border border-slate-200 shadow-2xs">
                                {{ selectedCompany.vat_number }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Assigned Team</span>
                        <p class="mt-1 text-sm font-semibold text-slate-800">
                            {{ selectedCompany.users_count }} {{ selectedCompany.users_count === 1 ? 'registered user' : 'registered users' }}
                        </p>
                    </div>

                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Created On</span>
                        <p class="mt-1 text-sm font-medium text-slate-700">
                            {{ formatDate(selectedCompany.created_at) }}
                        </p>
                    </div>

                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">
                            {{ selectedCompany.deleted_at ? 'Archived On' : 'Last Updated' }}
                        </span>
                        <p class="mt-1 text-sm font-medium text-slate-700">
                            {{ formatDate(selectedCompany.deleted_at || selectedCompany.updated_at) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                    <SecondaryButton @click="closeShowModal" type="button">
                        Close
                    </SecondaryButton>
                    <PrimaryButton
                        v-if="!selectedCompany.deleted_at"
                        @click="switchFromShowToEdit"
                        type="button"
                        class="!bg-indigo-600 hover:!bg-indigo-500 focus:!ring-indigo-500"
                    >
                        Edit Company
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Create / Edit Company Modal -->
        <Modal :show="isFormModalOpen" @close="closeFormModal" maxWidth="md">
            <form @submit.prevent="submitForm" class="p-6 space-y-5">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-lg font-bold text-slate-900">
                        {{ isEditing ? 'Edit Company' : 'Create New Company' }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ isEditing ? 'Update the organization details and operational status.' : 'Register a new B2B client organization in the hub.' }}
                    </p>
                </div>

                <div>
                    <InputLabel for="name" value="Company Name" class="text-slate-700" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1.5 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm"
                        placeholder="Acme Corporation S.L."
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="vat_number" value="VAT / Tax ID Number" class="text-slate-700" />
                    <TextInput
                        id="vat_number"
                        v-model="form.vat_number"
                        type="text"
                        class="mt-1.5 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm font-mono uppercase"
                        placeholder="ESB12345678"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.vat_number" />
                </div>

                <div class="pt-1">
                    <label class="flex items-center cursor-pointer">
                        <Checkbox
                            name="is_active"
                            v-model:checked="form.is_active"
                            class="text-indigo-600 focus:ring-indigo-500 border-slate-300 rounded"
                        />
                        <span class="ms-2 text-sm text-slate-700 font-medium">Active operational status</span>
                    </label>
                    <p class="text-xs text-slate-400 mt-1 ms-6">
                        Inactive companies cannot access multi-tenant resources.
                    </p>
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
                        {{ isEditing ? 'Save Changes' : 'Create Company' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Archive (Soft Delete) Confirmation Modal -->
        <Modal :show="isDeleteModalOpen" @close="closeDeleteModal" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">Archive Company</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Are you sure you want to archive <span class="font-semibold text-slate-900">{{ companyToDelete?.name }}</span>?
                    You can restore it at any time from the Archived tab.
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
                <h2 class="text-lg font-bold text-slate-900">Restore Company</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Do you want to restore <span class="font-semibold text-slate-900">{{ companyToRestore?.name }}</span> back to the active directory?
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
