<script setup lang="ts">
import type { Paginated } from '@/types/admin';
import { Link } from '@inertiajs/vue3';

defineProps<{ pagination: Paginated<unknown> }>();
</script>

<template>
    <div v-if="pagination.last_page > 1" class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500">
            Wyniki {{ pagination.from }}–{{ pagination.to }} z {{ pagination.total }}
        </p>
        <nav class="flex flex-wrap gap-1" aria-label="Paginacja">
            <component
                :is="link.url ? Link : 'span'"
                v-for="link in pagination.links"
                :key="link.label"
                :href="link.url ?? undefined"
                preserve-scroll
                class="min-w-9 rounded-lg px-3 py-2 text-center text-sm font-medium"
                :class="[
                    link.active ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 ring-1 ring-inset ring-slate-200',
                    !link.url ? 'cursor-not-allowed opacity-40' : 'hover:bg-slate-50',
                ]"
                v-html="link.label"
            />
        </nav>
    </div>
</template>
