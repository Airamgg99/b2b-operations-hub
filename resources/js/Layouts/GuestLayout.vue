<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="relative min-h-screen flex flex-col justify-center items-center bg-slate-900 px-4 py-12 sm:px-6 lg:px-8 overflow-hidden">
        <!-- Ambient Corporate Glow (Decorative Background) -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-indigo-600/20 blur-3xl"></div>
            <div class="absolute -bottom-40 -right-40 w-96 h-96 rounded-full bg-indigo-500/10 blur-3xl"></div>
        </div>

        <!-- Corporate Brand Header -->
        <div class="relative z-10 sm:mx-auto sm:w-full sm:max-w-md text-center">
            <Link href="/" class="inline-flex items-center gap-3 group focus:outline-none">
                <div class="w-11 h-11 rounded-xl bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-600/30 group-hover:bg-indigo-500 transition-colors">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <span class="text-2xl font-bold tracking-wider text-white">
                    B2B <span class="text-indigo-400">HUB</span>
                </span>
            </Link>
            <p class="mt-2 text-xs font-medium uppercase tracking-widest text-slate-400">
                Multi-tenant Operations Hub
            </p>
        </div>

        <!-- High-Contrast Auth Card -->
        <div class="relative z-10 mt-8 w-full sm:max-w-md bg-white px-6 py-8 shadow-2xl shadow-slate-950/60 border border-slate-800/10 rounded-2xl sm:px-10">
            <slot />
        </div>

        <!-- Corporate Footer -->
        <p class="relative z-10 mt-8 text-center text-xs text-slate-500">
            &copy; {{ new Date().getFullYear() }} B2B Operations Hub. All rights reserved.
        </p>
    </div>
</template>
