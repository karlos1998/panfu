<script setup lang="ts">
import PanfuLayout from '@/Layouts/PanfuLayout.vue';
import type { FooterContent, MetaContent, NavigationItem } from '@/types/panfu';
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

const navigation = computed<NavigationItem[]>(() => [
    { label: 'Strona główna', href: '/', active: props.active === 'home' },
    { label: 'Blog', href: '/#blog' },
    {
        label: 'Język',
        href: '#',
        children: [
            { label: 'Deutsch', href: '#' },
            { label: 'English', href: '#' },
            { label: 'Polski', href: '#', active: true },
        ],
    },
    { label: 'Rejestracja', href: '/register', active: props.active === 'register' },
    { label: 'Zaloguj się', href: '/login', active: props.active === 'login' },
]);

const footer: FooterContent = {
    copyright: '© 2016-2026 Panfu.me. Wszystkie prawa zastrzeżone.',
    disclaimer: 'Panfu.me nie jest powiązane ani wspierane przez Goodbeans GmbH.',
    links: [
        { label: 'Preferencje plików cookie', href: '#' },
        { label: 'Zespół Panfu', href: '#' },
        { label: 'Oloko', href: '#' },
        { label: 'Status', href: '#' },
    ],
    legalLinks: [
        { label: 'Imprint', href: '#' },
        { label: 'Privacy Policy', href: '#' },
        { label: 'Terms of Service', href: '#' },
    ],
};
</script>

<template>
    <PanfuLayout
        :meta="meta"
        logo="/vendor/panfu-me/assets/panfu-logo-BkIF66dU.svg"
        :navigation="navigation"
        :footer="footer"
        main-class="panfu-main--trees"
    >
        <section class="panfu-auth-page">
            <slot />
        </section>
    </PanfuLayout>
</template>
