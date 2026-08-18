<script setup lang="ts">
defineProps<{ title: string }>();
</script>

<template>
    <section class="panfu-blog-card panfu-blog-sidebar-card">
        <header class="panfu-blog-card__header"><strong>{{ title }}</strong></header>
        <div class="panfu-blog-card__body"><slot /></div>
    </section>
</template>
