<script setup lang="ts">
import PanelCard from '@/Components/PanelCard.vue';
import type { PanelCardSeverity } from '@/types/ui';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        padded?: boolean;
        severity?: PanelCardSeverity;
    }>(),
    { title: '', description: '', padded: true, severity: 'default' },
);
</script>

<template>
    <PanelCard
        class="panfu-admin-card shadow-sm"
        :title="title"
        :description="description"
        :padded="padded"
        :severity="severity"
    >
        <template v-if="$slots.title" #title>
            <slot name="title" />
        </template>
        <template v-if="$slots.actions" #actions>
            <slot name="actions" />
        </template>
        <slot />
        <template v-if="$slots.footer" #footer>
            <slot name="footer" />
        </template>
    </PanelCard>
</template>
