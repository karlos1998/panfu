<script setup lang="ts">
import type { PanelCardSeverity } from '@/types/ui';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        severity?: PanelCardSeverity;
        padded?: boolean;
        tag?: 'article' | 'section' | 'div';
    }>(),
    {
        title: '',
        description: '',
        severity: 'default',
        padded: true,
        tag: 'section',
    },
);
</script>

<template>
    <component
        :is="tag"
        class="panfu-panel-card"
        :class="`panfu-panel-card--${severity}`"
    >
        <header
            v-if="title || description || $slots.title || $slots.actions"
            class="panfu-panel-card__header"
        >
            <div class="panfu-panel-card__heading">
                <h2 v-if="title || $slots.title" class="panfu-panel-card__title">
                    <slot name="title">{{ title }}</slot>
                </h2>
                <p v-if="description" class="panfu-panel-card__description">
                    {{ description }}
                </p>
            </div>
            <slot name="actions" />
        </header>

        <div
            class="panfu-panel-card__body"
            :class="{ 'panfu-panel-card__body--padded': padded }"
        >
            <slot />
        </div>

        <footer v-if="$slots.footer" class="panfu-panel-card__footer">
            <slot name="footer" />
        </footer>
    </component>
</template>
