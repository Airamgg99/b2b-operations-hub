<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const emit = defineEmits(['openMobile']);

const page = usePage();
const user = computed(() => page.props.auth?.user);
</script>

<template>
    <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 z-30">
        <!-- Mobile Hamburger Button -->
        <button
            @click="emit('openMobile')"
            class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <div class="hidden md:block text-sm font-medium text-slate-500">
            Multi-tenant Operations Hub
        </div>

        <!-- User Session Dropdown -->
        <div class="ms-3 relative" v-if="user">
            <Dropdown align="right" width="48">
                <template #trigger>
                    <span class="inline-flex rounded-md">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-slate-700 bg-white hover:text-slate-900 focus:outline-none transition ease-in-out duration-150"
                        >
                            <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold">
                                {{ user.name.charAt(0).toUpperCase() }}
                            </div>
                            <span class="hidden sm:inline-block">{{ user.name }}</span>
                            <svg class="ms-1 -me-0.5 h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </span>
                </template>

                <template #content>
                    <div class="px-4 py-2 border-b border-slate-100">
                        <p class="text-xs text-slate-500 truncate">{{ user.email }}</p>
                    </div>
                    <DropdownLink :href="route('profile.edit')">
                        Profile
                    </DropdownLink>
                    <DropdownLink :href="route('logout')" method="post" as="button">
                        Log Out
                    </DropdownLink>
                </template>
            </Dropdown>
        </div>
    </header>
</template>
