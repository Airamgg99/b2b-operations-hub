<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 bg-white border-t border-slate-200 sm:px-6">
        <div class="flex flex-wrap items-center gap-1">
            <template v-for="(link, key) in links" :key="key">
                <span
                    v-if="link.url === null"
                    class="px-3 py-1.5 text-xs font-medium text-slate-400 bg-slate-50 border border-slate-200 rounded-lg cursor-not-allowed"
                    v-html="link.label"
                />
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    :class="[
                        'px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors',
                        link.active
                            ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
                    ]"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
