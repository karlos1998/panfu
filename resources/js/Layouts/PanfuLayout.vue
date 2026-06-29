<script setup lang="ts">
import PanfuFooter from '@/Components/Panfu/PanfuFooter.vue';
import PanfuNavbar from '@/Components/Panfu/PanfuNavbar.vue';
import type {
    FooterContent,
    MetaContent,
    NavigationItem,
} from '@/types/panfu';
import { Head } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        meta: MetaContent;
        logo: string;
        navigation?: NavigationItem[];
        footer?: FooterContent;
        mainClass?: string;
    }>(),
    {
        navigation: () => [],
        footer: undefined,
        mainClass: '',
    },
);
</script>

<template>
    <Head :title="meta.title">
        <meta head-key="description" name="description" :content="meta.description" />
        <link
            rel="apple-touch-icon"
            sizes="180x180"
            href="/vendor/panfu-me/favicons/apple-touch-icon.png"
        />
        <link
            rel="icon"
            type="image/png"
            sizes="32x32"
            href="/vendor/panfu-me/favicons/favicon-32x32.png"
        />
        <link
            rel="icon"
            type="image/png"
            sizes="16x16"
            href="/vendor/panfu-me/favicons/favicon-16x16.png"
        />
    </Head>

    <div class="panfu-shell">
        <PanfuNavbar :logo="logo" :items="navigation" />
        <main :class="['panfu-main', mainClass]">
            <slot />
        </main>
        <PanfuFooter v-if="footer" :footer="footer" />
    </div>
</template>
