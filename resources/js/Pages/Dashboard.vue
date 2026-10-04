<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    metrics: {
        type: Object,
        required: true,
    },
    alerts: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const userName = page.props.auth.user.name.split(' ')[0];
const userRoles = page.props.auth.user.roles || [];
const isSuperAdmin = userRoles.includes('Super Admin');

// Date formatter
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
    });
};

// Alert logic helpers
const isPastDue = (status) => status === 'past_due';

const getDaysRemaining = (endsAt) => {
    if (!endsAt) return null;
    const diffTime = Math.abs(new Date(endsAt) - new Date());
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Welcome back, {{ userName }}</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Here's a snapshot of your B2B platform's operational status today.
                </p>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <!-- Active Companies Widget -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-indigo-50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Active Organizations</p>
                    <div class="flex items-baseline gap-2 mt-1">
                        <h2 class="text-2xl font-bold text-slate-900">{{ metrics.active_companies }}</h2>
                        <span class="text-xs text-slate-400 font-medium">/ {{ metrics.total_companies }} total</span>
                    </div>
                </div>
            </div>

            <!-- Active Subscriptions Widget -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Active Subscriptions</p>
                    <div class="flex items-baseline gap-2 mt-1">
                        <h2 class="text-2xl font-bold text-slate-900">{{ metrics.active_subscriptions }}</h2>
                    </div>
                </div>
            </div>

            <!-- Total Users Widget -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-start gap-4">
                <div class="w-12 h-12 rounded-full bg-sky-50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Registered Users</p>
                    <div class="flex items-baseline gap-2 mt-1">
                        <h2 class="text-2xl font-bold text-slate-900">{{ metrics.total_users }}</h2>
                    </div>
                </div>
            </div>

            <!-- Action Required Widget -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-start gap-4">
                <div :class="[
                    'w-12 h-12 rounded-full flex items-center justify-center shrink-0',
                    alerts.length > 0 ? 'bg-amber-50' : 'bg-slate-50'
                ]">
                    <svg :class="['w-6 h-6', alerts.length > 0 ? 'text-amber-600' : 'text-slate-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Action Required</p>
                    <div class="flex items-baseline gap-2 mt-1">
                        <h2 class="text-2xl font-bold text-slate-900">{{ alerts.length }}</h2>
                        <span class="text-xs text-slate-400 font-medium">alerts</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Layout (Alerts / Tasks) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Alerts Section (Spans 2 columns on large screens) -->
            <div :class="[
                    isSuperAdmin ? 'lg:col-span-2' : 'lg:col-span-3',
                    'bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col'
                ]">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900">Operational Alerts</h3>
                    <span v-if="alerts.length > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                        {{ alerts.length }} pending
                    </span>
                </div>

                <div class="flex-1 p-0">
                    <ul v-if="alerts.length > 0" class="divide-y divide-slate-100">
                        <li v-for="alert in alerts" :key="alert.id" class="p-5 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3.5">
                                    <div :class="[
                                        'mt-0.5 w-2 h-2 rounded-full shrink-0',
                                        isPastDue(alert.status) ? 'bg-red-500' : 'bg-amber-500'
                                    ]"></div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">
                                            {{ alert.company ? alert.company.name : 'Unassigned Organization' }}
                                            <span class="text-slate-500 font-normal border-l border-slate-300 ml-2 pl-2">
                                                {{ alert.plan_name }} Plan
                                            </span>
                                        </p>
                                        <p class="text-xs text-slate-500 mt-1">
                                            <template v-if="isPastDue(alert.status)">
                                                Subscription is <strong class="text-red-600">Past Due</strong>. Please check payment status.
                                            </template>
                                            <template v-else>
                                                Subscription ends in <strong class="text-amber-600">{{ getDaysRemaining(alert.ends_at) }} days</strong> ({{ formatDate(alert.ends_at) }}).
                                            </template>
                                        </p>
                                    </div>
                                </div>

                                <Link
                                    :href="route('subscriptions.index')"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg transition-colors border border-indigo-100 shrink-0"
                                >
                                    Manage
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </div>
                        </li>
                    </ul>

                    <!-- Empty State for Alerts -->
                    <div v-else class="flex flex-col items-center justify-center p-12 text-center">
                        <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mb-4 border border-emerald-100">
                            <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h4 class="text-base font-bold text-slate-900">All Clear</h4>
                        <p class="text-sm text-slate-500 mt-1 max-w-sm">
                            There are no past due subscriptions or contracts expiring in the next 30 days.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Links / Next Steps -->
            <div v-if="isSuperAdmin" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                    <h3 class="text-base font-bold text-slate-900">Quick Actions</h3>
                </div>
                <div class="p-5 flex flex-col gap-3">
                    <Link :href="route('companies.index')" class="flex items-center justify-between p-3 rounded-lg border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 group transition-all">
                        <div class="flex items-center gap-3 text-sm font-semibold text-slate-700 group-hover:text-indigo-700">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Register New Company
                        </div>
                    </Link>

                    <Link :href="route('users.index')" class="flex items-center justify-between p-3 rounded-lg border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/50 group transition-all">
                        <div class="flex items-center gap-3 text-sm font-semibold text-slate-700 group-hover:text-indigo-700">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            Invite User
                        </div>
                    </Link>

                    <div class="mt-4 p-4 bg-slate-50 rounded-lg border border-slate-200 text-center">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Platform Status</p>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Systems Operational
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
