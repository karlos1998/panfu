<script setup lang="ts">
defineProps<{ title: string; description: string }>();
</script>

<template>
    <div class="px-5 py-12 text-center">
        <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-400">
            <slot name="icon">—</slot>
        </div>
        <h3 class="text-sm font-semibold text-slate-800">{{ title }}</h3>
        <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ description }}</p>
    </div>
</template>
