<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    isCollapsed: {
        type: Boolean,
        required: true,
    },
    isMobileOpen: {
        type: Boolean,
        required: true,
    },
});

const emit = defineEmits(['toggleCollapse', 'closeMobile']);
</script>

<template>
    <!-- Mobile Backdrop -->
    <div
        v-if="isMobileOpen"
        @click="emit('closeMobile')"
        class="fixed inset-0 bg-slate-900/50 z-40 md:hidden transition-opacity"
    ></div>

    <!-- Sidebar -->
    <aside
        :class="[
            'fixed md:static inset-y-0 left-0 z-50 bg-slate-900 text-slate-100 flex flex-col transition-all duration-300 ease-in-out shrink-0',
            isMobileOpen ? 'translate-x-0 w-64' : '-translate-x-full md:translate-x-0',
            isCollapsed ? 'md:w-20' : 'md:w-64'
        ]"
    >
        <!-- Brand & Collapse Trigger -->
        <div
            :class="[
                'h-16 flex items-center border-b border-slate-800 transition-all',
                isCollapsed ? 'justify-center px-2' : 'justify-between px-4'
            ]"
        >
            <span v-if="!isCollapsed" class="text-lg font-bold tracking-wider text-white truncate">
                B2B <span class="text-indigo-400">HUB</span>
            </span>

            <button
                @click="emit('toggleCollapse')"
                class="hidden md:flex p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors focus:outline-none"
                title="Toggle sidebar"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-3 py-4 space-y-2 overflow-y-auto">
            <Link
                :href="route('dashboard')"
                :title="isCollapsed ? 'Dashboard' : ''"
                :class="[
                    'flex items-center rounded-lg text-sm font-medium transition-colors',
                    isCollapsed ? 'justify-center p-2.5' : 'px-3 py-2.5',
                    route().current('dashboard')
                        ? 'bg-indigo-600 text-white'
                        : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                ]"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span v-if="!isCollapsed" class="ml-3 truncate">Dashboard</span>
            </Link>

            <Link
                href="#"
                :title="isCollapsed ? 'Companies' : ''"
                :class="[
                    'flex items-center rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition-colors',
                    isCollapsed ? 'justify-center p-2.5' : 'px-3 py-2.5'
                ]"
            >
                <svg class="w-5 h-5 shrink-0 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span v-if="!isCollapsed" class="ml-3 truncate">Companies</span>
            </Link>
        </nav>
    </aside>
</template>
