<script setup lang="ts">
import PanfuLayout from '@/Layouts/PanfuLayout.vue';
import type { FooterContent, MetaContent, NavigationItem } from '@/types/panfu';
import { Link } from '@inertiajs/vue3';

const meta: MetaContent = {
    title: 'Panfu',
    description: 'Log in or join the local Panfu world.',
};

const navigation: NavigationItem[] = [
    { label: 'Home', href: '/', active: true },
    { label: 'Registration', href: '/register' },
    { label: 'Login', href: '/login' },
];

const footer: FooterContent = {
    copyright: '© 2016-2026 Panfu.me. All rights reserved.',
    disclaimer: 'Panfu.me is not affiliated with or endorsed by Goodbeans GmbH.',
    links: [
        { label: 'Panfu Team', href: '#' },
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
