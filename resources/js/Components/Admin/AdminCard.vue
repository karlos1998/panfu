<script setup lang="ts">
withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        padded?: boolean;
    }>(),
    { title: '', description: '', padded: true },
);
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header
            v-if="title || description || $slots.actions"
            class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 v-if="title" class="text-base font-semibold text-slate-900">{{ title }}</h2>
                <p v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</p>
            </div>
            <slot name="actions" />
        </header>
        <div :class="padded ? 'p-5' : ''">
            <slot />
        </div>
    </section>
</template>
