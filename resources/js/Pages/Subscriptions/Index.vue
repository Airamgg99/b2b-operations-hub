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
    subscriptions: {
        type: Object,
        required: true,
    },
    companies: {
        type: Array,
        default: () => [],
    },
    availableStatuses: {
        type: Array,
        default: () => ['active', 'trialing', 'past_due', 'canceled', 'expired'],
    },
    availablePlans: {
        type: Array,
        default: () => ['Starter', 'Professional', 'Business', 'Enterprise'],
    },
    archivedCount: {
        type: Number,
        default: 0,
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            status: '',
            sort: 'id',
            direction: 'desc',
            trashed: '',
        }),
    },
});

const page = usePage();
const userRoles = page.props.auth?.user?.roles || [];
const isSuperAdmin = userRoles.includes('Super Admin');
const isCompanyAdmin = userRoles.includes('Company Admin');

// Search, Status Filter, Sorting & Trashed State
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const sortField = ref(props.filters.sort || 'id');
const sortDirection = ref(props.filters.direction || 'desc');
const trashedFilter = ref(props.filters.trashed || '');
let searchTimeout = null;

const fetchSubscriptions = () => {
    router.get(
        route('subscriptions.index'),
        {
            search: search.value,
            status: statusFilter.value,
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
        fetchSubscriptions();
    }, 300);
});

watch(statusFilter, () => {
    fetchSubscriptions();
});

const setTrashedFilter = (value) => {
    trashedFilter.value = value;
    fetchSubscriptions();
};

const sortBy = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    fetchSubscriptions();
};

const clearSearch = () => {
    search.value = '';
};

// Date Helpers
const formatDate = (dateString) => {
    if (!dateString) return 'No expiration';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
    });
};

const formatDateForInput = (dateString) => {
    if (!dateString) return '';
    return String(dateString).substring(0, 10);
};

// Status Styling Helper
const formatStatusLabel = (status) => {
    if (!status) return 'Unknown';
    return String(status)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const getStatusBadgeClasses = (status) => {
    const normalized = String(status || '').toLowerCase();
    if (normalized === 'active') {
        return {
            badge: 'bg-emerald-50 text-emerald-700 border-emerald-200',
            dot: 'bg-emerald-500',
        };
    }
    if (['trialing', 'trial', 'pending'].includes(normalized)) {
        return {
            badge: 'bg-indigo-50 text-indigo-700 border-indigo-200',
            dot: 'bg-indigo-500',
        };
    }
    if (['past_due', 'suspended', 'warning'].includes(normalized)) {
        return {
            badge: 'bg-amber-50 text-amber-700 border-amber-200',
            dot: 'bg-amber-500',
        };
    }
    return {
        badge: 'bg-rose-50 text-rose-700 border-rose-200',
        dot: 'bg-rose-500',
    };
};

// Quick View (Show) Modal State
const isShowModalOpen = ref(false);
const selectedSubscription = ref(null);

const openShowModal = (subscription) => {
    selectedSubscription.value = subscription;
    isShowModalOpen.value = true;
};

const closeShowModal = () => {
    isShowModalOpen.value = false;
    selectedSubscription.value = null;
};

const switchFromShowToEdit = () => {
    const sub = selectedSubscription.value;
    closeShowModal();
    if (sub) {
        openEditModal(sub);
    }
};

// Create / Edit Modal State
const isFormModalOpen = ref(false);
const isEditing = ref(false);
const editingSubscriptionId = ref(null);

const todayDate = new Date().toISOString().substring(0, 10);

const form = useForm({
    company_id: '',
    plan_name: 'Professional',
    status: 'active',
    starts_at: todayDate,
    ends_at: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingSubscriptionId.value = null;
    form.reset();
    form.clearErrors();
    form.plan_name = props.availablePlans[0] || 'Professional';
    form.status = 'active';
    form.starts_at = todayDate;
    form.ends_at = '';
    isFormModalOpen.value = true;
};

const openEditModal = (subscription) => {
    isEditing.value = true;
    editingSubscriptionId.value = subscription.id;
    form.clearErrors();
    form.company_id = subscription.company_id || '';
    form.plan_name = subscription.plan_name || '';
    form.status = subscription.status || 'active';
    form.starts_at = formatDateForInput(subscription.starts_at);
    form.ends_at = formatDateForInput(subscription.ends_at);
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
        onError: () => notifyError('Please check the subscription form for validation errors.'),
    };

    if (isEditing.value) {
        form.put(route('subscriptions.update', editingSubscriptionId.value), options);
    } else {
        form.post(route('subscriptions.store'), options);
    }
};

// Archive (Soft Delete) Confirmation Modal State
const isDeleteModalOpen = ref(false);
const subscriptionToDelete = ref(null);
const deleteForm = useForm({});

const confirmDelete = (subscription) => {
    subscriptionToDelete.value = subscription;
    isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    subscriptionToDelete.value = null;
};

const executeDelete = () => {
    if (!subscriptionToDelete.value) return;
    deleteForm.delete(route('subscriptions.destroy', subscriptionToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
        onError: () => notifyError('Unable to archive the selected subscription.'),
    });
};

// Restore Confirmation Modal State
const isRestoreModalOpen = ref(false);
const subscriptionToRestore = ref(null);
const restoreForm = useForm({});

const confirmRestore = (subscription) => {
    subscriptionToRestore.value = subscription;
    isRestoreModalOpen.value = true;
};

const closeRestoreModal = () => {
    isRestoreModalOpen.value = false;
    subscriptionToRestore.value = null;
};

const executeRestore = () => {
    if (!subscriptionToRestore.value) return;
    restoreForm.patch(route('subscriptions.restore', subscriptionToRestore.value.id), {
        preserveScroll: true,
        onSuccess: () => closeRestoreModal(),
        onError: () => notifyError('Unable to restore the archived subscription.'),
    });
};
</script>

<template>
    <Head title="Subscriptions" />

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl font-bold text-slate-900">Subscriptions & Billing Plans</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                        {{ subscriptions.total }} {{ trashedFilter === 'only' ? 'archived' : 'active' }}
                    </span>
                </div>
                <p class="text-sm text-slate-500 mt-1">
                    Monitor B2B organization billing tiers, contract validity periods, and renewal status.
                </p>
            </div>

            <PrimaryButton
                v-if="isSuperAdmin"
                @click="openCreateModal"
                class="!bg-indigo-600 hover:!bg-indigo-500 focus:!ring-indigo-500 rounded-lg text-sm font-semibold shadow-sm px-4 py-2.5 shrink-0 self-start sm:self-auto"
            >
                <svg class="w-4 h-4 mr-2 -ml-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Subscription
            </PrimaryButton>
        </div>

        <!-- Main Directory Card -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <!-- Integrated Search, Status Filter & View Tabs Toolbar -->
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
                            placeholder="Search by ID, company, plan..."
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

                    <!-- Status Filter Select -->
                    <div class="w-full sm:w-48">
                        <select
                            v-model="statusFilter"
                            class="block w-full py-2 pl-3 pr-8 bg-white border border-slate-300 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option value="">All Statuses</option>
                            <option v-for="status in availableStatuses" :key="status" :value="status">
                                {{ formatStatusLabel(status) }}
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
                            Active Subscriptions
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
                    Showing <span class="font-semibold text-slate-800">{{ subscriptions.from || 0 }}</span> to
                    <span class="font-semibold text-slate-800">{{ subscriptions.to || 0 }}</span> of
                    <span class="font-semibold text-slate-800">{{ subscriptions.total }}</span> subscriptions
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

                            <!-- Organization Column -->
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Organization
                            </th>

                            <!-- Sortable Plan Name Column -->
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <button
                                    @click="sortBy('plan_name')"
                                    type="button"
                                    class="group inline-flex items-center gap-1.5 uppercase font-semibold hover:text-indigo-600 focus:outline-none transition-colors"
                                    :class="{ 'text-indigo-600': sortField === 'plan_name' }"
                                >
                                    <span>Plan Tier</span>
                                    <svg
                                        class="w-3.5 h-3.5 transition-transform"
                                        :class="[
                                            sortField === 'plan_name' ? 'text-indigo-600' : 'text-slate-400 opacity-60 group-hover:opacity-100',
                                            sortField === 'plan_name' && sortDirection === 'desc' ? 'rotate-180' : ''
                                        ]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                            </th>

                            <!-- Sortable Status Column -->
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <button
                                    @click="sortBy('status')"
                                    type="button"
                                    class="group inline-flex items-center gap-1.5 uppercase font-semibold hover:text-indigo-600 focus:outline-none transition-colors"
                                    :class="{ 'text-indigo-600': sortField === 'status' }"
                                >
                                    <span>Status</span>
                                    <svg
                                        class="w-3.5 h-3.5 transition-transform"
                                        :class="[
                                            sortField === 'status' ? 'text-indigo-600' : 'text-slate-400 opacity-60 group-hover:opacity-100',
                                            sortField === 'status' && sortDirection === 'desc' ? 'rotate-180' : ''
                                        ]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                            </th>

                            <!-- Sortable Start Date Column -->
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <button
                                    @click="sortBy('starts_at')"
                                    type="button"
                                    class="group inline-flex items-center gap-1.5 uppercase font-semibold hover:text-indigo-600 focus:outline-none transition-colors"
                                    :class="{ 'text-indigo-600': sortField === 'starts_at' }"
                                >
                                    <span>Starts At</span>
                                    <svg
                                        class="w-3.5 h-3.5 transition-transform"
                                        :class="[
                                            sortField === 'starts_at' ? 'text-indigo-600' : 'text-slate-400 opacity-60 group-hover:opacity-100',
                                            sortField === 'starts_at' && sortDirection === 'desc' ? 'rotate-180' : ''
                                        ]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                            </th>

                            <!-- Sortable End Date Column -->
                            <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <button
                                    @click="sortBy('ends_at')"
                                    type="button"
                                    class="group inline-flex items-center gap-1.5 uppercase font-semibold hover:text-indigo-600 focus:outline-none transition-colors"
                                    :class="{ 'text-indigo-600': sortField === 'ends_at' }"
                                >
                                    <span>Ends At</span>
                                    <svg
                                        class="w-3.5 h-3.5 transition-transform"
                                        :class="[
                                            sortField === 'ends_at' ? 'text-indigo-600' : 'text-slate-400 opacity-60 group-hover:opacity-100',
                                            sortField === 'ends_at' && sortDirection === 'desc' ? 'rotate-180' : ''
                                        ]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                            </th>

                            <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <tr
                            v-for="subscription in subscriptions.data"
                            :key="subscription.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <!-- Dedicated ID Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-mono font-semibold text-slate-500 bg-slate-100 px-2 py-1 rounded-md border border-slate-200">
                                    #{{ subscription.id }}
                                </span>
                            </td>

                            <!-- Organization Name & Avatar -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="subscription.company" class="flex items-center gap-3.5">
                                    <div
                                        :class="[
                                            'w-9 h-9 rounded-lg flex items-center justify-center font-bold text-sm shrink-0 shadow-xs',
                                            subscription.deleted_at ? 'bg-slate-200 text-slate-500' : 'bg-slate-900 text-indigo-400'
                                        ]"
                                    >
                                        {{ subscription.company.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <button
                                        @click="openShowModal(subscription)"
                                        type="button"
                                        class="text-sm font-semibold text-slate-900 hover:text-indigo-600 transition-colors truncate text-left focus:outline-none"
                                    >
                                        {{ subscription.company.name }}
                                    </button>
                                </div>
                                <span v-else class="text-xs text-slate-400 italic">Unassigned Organization</span>
                            </td>

                            <!-- Plan Tier Badge -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50/70 text-indigo-700 border border-indigo-100">
                                    <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                    {{ subscription.plan_name }}
                                </span>
                            </td>

                            <!-- Status Pill -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    v-if="subscription.deleted_at"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Archived
                                </span>
                                <span
                                    v-else
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border',
                                        getStatusBadgeClasses(subscription.status).badge
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            getStatusBadgeClasses(subscription.status).dot
                                        ]"
                                    ></span>
                                    {{ formatStatusLabel(subscription.status) }}
                                </span>
                            </td>

                            <!-- Starts At -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                {{ formatDate(subscription.starts_at) }}
                            </td>

                            <!-- Ends At -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                {{ formatDate(subscription.ends_at) }}
                            </td>

                            <!-- Compact Icon-Only Action Buttons -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <!-- Show Details Button -->
                                    <button
                                        @click="openShowModal(subscription)"
                                        type="button"
                                        title="View subscription details"
                                        class="p-2 rounded-lg text-slate-500 bg-white border border-slate-200 hover:bg-slate-100 hover:text-slate-900 transition-colors shadow-2xs"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    <!-- Actions for Active Subscriptions -->
                                    <template v-if="!subscription.deleted_at">
                                        <button
                                            v-if="isSuperAdmin || isCompanyAdmin"
                                            @click="openEditModal(subscription)"
                                            type="button"
                                            title="Edit subscription"
                                            class="p-2 rounded-lg text-indigo-600 bg-white border border-slate-200 hover:bg-indigo-50 hover:border-indigo-200 transition-colors shadow-2xs"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <button
                                            v-if="isSuperAdmin"
                                            @click="confirmDelete(subscription)"
                                            type="button"
                                            title="Archive subscription"
                                            class="p-2 rounded-lg text-red-600 bg-white border border-slate-200 hover:bg-red-50 hover:border-red-200 transition-colors shadow-2xs"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </template>

                                    <!-- Restore Action for Archived Subscriptions -->
                                    <button
                                        v-else
                                        @click="confirmRestore(subscription)"
                                        type="button"
                                        title="Restore subscription"
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
                        <tr v-if="subscriptions.data.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center">
                                <p class="text-sm font-semibold text-slate-700">
                                    {{ trashedFilter === 'only' ? 'No archived subscriptions found.' : 'No subscriptions found matching your criteria.' }}
                                </p>
                                <p class="text-xs text-slate-400 mt-1">
                                    {{ trashedFilter === 'only' ? 'Archived billing records will appear here for recovery.' : 'Try adjusting your search or status filter.' }}
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <Pagination :links="subscriptions.links" />
        </div>

        <!-- Quick View (Show) Subscription Modal -->
        <Modal :show="isShowModalOpen" @close="closeShowModal" maxWidth="md">
            <div v-if="selectedSubscription" class="p-6 space-y-6">
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-slate-900 text-indigo-400 flex items-center justify-center font-bold text-lg shrink-0 shadow-sm">
                            {{ selectedSubscription.company ? selectedSubscription.company.name.charAt(0).toUpperCase() : 'S' }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-bold text-slate-900">
                                    {{ selectedSubscription.company ? selectedSubscription.company.name : 'Unassigned' }}
                                </h2>
                                <span class="text-xs font-mono font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                    #{{ selectedSubscription.id }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">Subscription Contract & Billing Overview</p>
                        </div>
                    </div>

                    <span
                        v-if="selectedSubscription.deleted_at"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        Archived
                    </span>
                    <span
                        v-else
                        :class="[
                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border shrink-0',
                            getStatusBadgeClasses(selectedSubscription.status).badge
                        ]"
                    >
                        <span
                            :class="[
                                'w-1.5 h-1.5 rounded-full',
                                getStatusBadgeClasses(selectedSubscription.status).dot
                            ]"
                        ></span>
                        {{ formatStatusLabel(selectedSubscription.status) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200/80">
                    <div class="sm:col-span-2">
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Assigned Plan Tier</span>
                        <div class="mt-1">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-white text-indigo-700 border border-indigo-200 shadow-2xs">
                                {{ selectedSubscription.plan_name }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Start Date</span>
                        <p class="mt-1 text-sm font-semibold text-slate-800">
                            {{ formatDate(selectedSubscription.starts_at) }}
                        </p>
                    </div>

                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">End / Renewal Date</span>
                        <p class="mt-1 text-sm font-semibold text-slate-800">
                            {{ formatDate(selectedSubscription.ends_at) }}
                        </p>
                    </div>

                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Created On</span>
                        <p class="mt-1 text-sm font-medium text-slate-700">
                            {{ formatDate(selectedSubscription.created_at) }}
                        </p>
                    </div>

                    <div>
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">
                            {{ selectedSubscription.deleted_at ? 'Archived On' : 'Last Updated' }}
                        </span>
                        <p class="mt-1 text-sm font-medium text-slate-700">
                            {{ formatDate(selectedSubscription.deleted_at || selectedSubscription.updated_at) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
                    <SecondaryButton @click="closeShowModal" type="button">
                        Close
                    </SecondaryButton>
                    <PrimaryButton
                        v-if="!selectedSubscription.deleted_at"
                        @click="switchFromShowToEdit"
                        type="button"
                        class="!bg-indigo-600 hover:!bg-indigo-500 focus:!ring-indigo-500"
                    >
                        Edit Subscription
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Create / Edit Subscription Modal -->
        <Modal :show="isFormModalOpen" @close="closeFormModal" maxWidth="md">
            <form @submit.prevent="submitForm" class="p-6 space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-lg font-bold text-slate-900">
                        {{ isEditing ? 'Edit Subscription' : 'Create New Subscription' }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        {{ isEditing ? 'Update plan tier, billing status, and validity dates.' : 'Assign a billing plan and contract period to an organization.' }}
                    </p>
                </div>

                <div>
                    <InputLabel for="company_id" value="Organization" class="text-slate-700" />
                    <select
                        id="company_id"
                        v-model="form.company_id"
                        required
                        class="mt-1.5 block w-full py-2 px-3 border border-slate-300 bg-white rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="" disabled>Select an organization...</option>
                        <option v-for="company in companies" :key="company.id" :value="company.id">
                            {{ company.name }} {{ !company.is_active ? '(Inactive)' : '' }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.company_id" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="plan_name" value="Plan Tier" class="text-slate-700" />
                        <select
                            id="plan_name"
                            v-model="form.plan_name"
                            required
                            class="mt-1.5 block w-full py-2 px-3 border border-slate-300 bg-white rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option v-for="plan in availablePlans" :key="plan" :value="plan">
                                {{ plan }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.plan_name" />
                    </div>

                    <div>
                        <InputLabel for="status" value="Billing Status" class="text-slate-700" />
                        <select
                            id="status"
                            v-model="form.status"
                            required
                            class="mt-1.5 block w-full py-2 px-3 border border-slate-300 bg-white rounded-lg text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option v-for="status in availableStatuses" :key="status" :value="status">
                                {{ formatStatusLabel(status) }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.status" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                    <div>
                        <InputLabel for="starts_at" value="Start Date" class="text-slate-700" />
                        <TextInput
                            id="starts_at"
                            v-model="form.starts_at"
                            type="date"
                            class="mt-1.5 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.starts_at" />
                    </div>

                    <div>
                        <InputLabel for="ends_at" value="End Date (Optional)" class="text-slate-700" />
                        <TextInput
                            id="ends_at"
                            v-model="form.ends_at"
                            type="date"
                            class="mt-1.5 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg text-sm"
                        />
                        <InputError class="mt-2" :message="form.errors.ends_at" />
                    </div>
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
                        {{ isEditing ? 'Save Changes' : 'Create Subscription' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Archive (Soft Delete) Confirmation Modal -->
        <Modal :show="isDeleteModalOpen" @close="closeDeleteModal" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900">Archive Subscription</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Are you sure you want to archive the <span class="font-semibold text-slate-900">{{ subscriptionToDelete?.plan_name }}</span> subscription for
                    <span class="font-semibold text-slate-900">{{ subscriptionToDelete?.company?.name || 'this organization' }}</span>?
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
                <h2 class="text-lg font-bold text-slate-900">Restore Subscription</h2>
                <p class="mt-2 text-sm text-slate-600">
                    Do you want to restore the <span class="font-semibold text-slate-900">{{ subscriptionToRestore?.plan_name }}</span> subscription for
                    <span class="font-semibold text-slate-900">{{ subscriptionToRestore?.company?.name || 'this organization' }}</span> back to the active directory?
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
