<script setup lang="ts">
import PanfuFooter from '@/Components/Panfu/PanfuFooter.vue';
import PanfuNavbar from '@/Components/Panfu/PanfuNavbar.vue';
import type {
    FooterContent,
    MetaContent,
    NavigationItem,
} from '@/types/panfu';
import type { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
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

const page = usePage<PageProps>();
const resolvedNavigation = computed(() =>
    props.navigation.length ? props.navigation : page.props.panfu.chrome.navigation,
);
const resolvedFooter = computed(() => props.footer ?? page.props.panfu.chrome.footer);
</script>

<template>
    <Head :title="props.meta.title">
        <meta head-key="description" name="description" :content="props.meta.description" />
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
        <PanfuNavbar :logo="props.logo" :items="resolvedNavigation" />
        <main :class="['panfu-main', props.mainClass]">
            <slot />
        </main>
        <PanfuFooter v-if="resolvedFooter" :footer="resolvedFooter" />
    </div>
</template>
