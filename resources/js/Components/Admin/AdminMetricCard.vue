<script setup lang="ts">
import PanelCard from '@/Components/PanelCard.vue';
import type { AdminMetricTone } from '@/types/ui';

withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        detail?: string;
        icon?: string;
        tone?: AdminMetricTone;
    }>(),
    {
        detail: '',
        icon: '',
        tone: 'slate',
    },
);
</script>

<template>
    <PanelCard class="panfu-admin-metric-card">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-500">{{ label }}</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">{{ value }}</p>
                <p v-if="detail" class="mt-1 text-xs text-slate-400">{{ detail }}</p>
            </div>
            <div
                v-if="icon"
                class="panfu-admin-metric-card__icon"
                :class="`panfu-admin-metric-card__icon--${tone}`"
            >
                {{ icon }}
            </div>
        </div>
    </PanelCard>
</template>
