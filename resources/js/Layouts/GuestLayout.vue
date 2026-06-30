<script setup lang="ts">
import PanfuLayout from '@/Layouts/PanfuLayout.vue';
import type { MetaContent } from '@/types/panfu';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        active?: 'home' | 'register' | 'login';
        title?: string;
        description?: string;
    }>(),
    {
        active: 'home',
        title: 'Panfu.me',
        description: 'Dołącz do lokalnego świata Panfu.',
    },
);

const meta = computed<MetaContent>(() => ({
    title: props.title,
    description: props.description,
}));

</script>

<template>
    <PanfuLayout
        :meta="meta"
        logo="/vendor/panfu-me/assets/panfu-logo-BkIF66dU.svg"
        main-class="panfu-main--trees"
    >
        <section class="panfu-auth-page">
            <slot />
        </section>
    </PanfuLayout>
</template>
