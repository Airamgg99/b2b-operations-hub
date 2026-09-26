<script setup>
import { ref } from 'vue';
import Sidebar from './Partials/Sidebar.vue';
import Header from './Partials/Header.vue';

const isSidebarCollapsed = ref(false);
const isMobileMenuOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col md:flex-row">
        <Sidebar
            :is-collapsed="isSidebarCollapsed"
            :is-mobile-open="isMobileMenuOpen"
            @toggle-collapse="isSidebarCollapsed = !isSidebarCollapsed"
            @close-mobile="isMobileMenuOpen = false"
        />

        <div class="flex-1 flex flex-col min-w-0">
            <Header @open-mobile="isMobileMenuOpen = true" />

            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
