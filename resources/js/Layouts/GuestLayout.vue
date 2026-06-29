<script setup lang="ts">
import PanfuLayout from '@/Layouts/PanfuLayout.vue';
import type { FooterContent, MetaContent, NavigationItem } from '@/types/panfu';
import { Link } from '@inertiajs/vue3';

const meta: MetaContent = {
    title: 'Panfu',
    description: 'Zaloguj się lub dołącz do lokalnego świata Panfu.',
};

const navigation: NavigationItem[] = [
    { label: 'Strona główna', href: '/' },
    { label: 'Rejestracja', href: '/register', variant: 'primary' },
    { label: 'Zaloguj się', href: '/login', variant: 'secondary' },
];

const footer: FooterContent = {
    copyright: 'Panfu local preservation project',
    links: [
        { label: 'Strona główna', href: '/' },
        { label: 'Gra', href: '/play' },
    ],
};
</script>

<template>
    <PanfuLayout
        :meta="meta"
        logo="/vendor/panfu-me/assets/panfu-logo-BkIF66dU.svg"
        :navigation="navigation"
        :footer="footer"
        main-class="panfu-main--auth"
    >
        <section class="panfu-auth">
            <Link class="panfu-auth__logo" href="/">
                <img
                    src="/vendor/panfu-me/assets/panfu-logo-BkIF66dU.svg"
                    alt="Panfu"
                />
            </Link>

            <div class="panfu-auth__panel">
                <slot />
            </div>
        </section>
    </PanfuLayout>
</template>
