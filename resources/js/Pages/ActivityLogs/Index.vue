<script setup>
import { Head } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import {
    ClockIcon,
    UserIcon,
    DocumentTextIcon,
    ArrowPathIcon,
    PlusCircleIcon,
    TrashIcon,
    InformationCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    logs: Object,
});

const getEventStyles = (eventName) => {
    switch (eventName) {
        case 'created':
            return { color: 'text-emerald-600 bg-emerald-50 border-emerald-200', icon: PlusCircleIcon };
        case 'updated':
            return { color: 'text-indigo-600 bg-indigo-50 border-indigo-200', icon: ArrowPathIcon };
        case 'deleted':
            return { color: 'text-rose-600 bg-rose-50 border-rose-200', icon: TrashIcon };
        default:
            return { color: 'text-slate-600 bg-slate-50 border-slate-200', icon: InformationCircleIcon };
    }
};
</script>

<template>
    <Head title="Activity Audit Log" />

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                        <ClockIcon class="h-8 w-8 text-indigo-600" />
                        Activity Audit Log
                    </h2>
                    <p class="mt-2 text-sm text-slate-500 max-w-2xl">
                        A detailed history of all system events and user actions within your organization.
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-700">Recent System Activity</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-white">
                            <tr>
                                <th scope="col" class="py-3.5 pl-6 pr-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Date & Time
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Actor
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Action
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Resource
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50/50 transition-colors duration-150">
                                <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm text-slate-600">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-2 rounded-full bg-slate-300"></div>
                                        {{ log.created_at }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs mr-3 border border-indigo-200">
                                            {{ log.causer_name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="font-medium text-slate-900">{{ log.causer_name }}</div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    <span :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium border', getEventStyles(log.event).color]">
                                        <component :is="getEventStyles(log.event).icon" class="h-3.5 w-3.5" />
                                        {{ log.event }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    <div class="flex items-center gap-2 text-slate-700 font-medium">
                                        <DocumentTextIcon class="h-4 w-4 text-slate-400" />
                                        {{ log.subject_type }}
                                    </div>
                                </td>
                            </tr>

                            <!-- Colspan ajustado a 4 columnas -->
                            <tr v-if="logs.data.length === 0">
                                <td colspan="4" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                                            <ClockIcon class="h-6 w-6 text-slate-400" aria-hidden="true" />
                                        </div>
                                        <h3 class="mt-2 text-sm font-semibold text-slate-900">No activity recorded</h3>
                                        <p class="mt-1 text-sm text-slate-500">Your organization's audit trail is currently empty.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6" v-if="logs.links">
                <Pagination :links="logs.links" />
            </div>

        </div>
    </div>
</template>
