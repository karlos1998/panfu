<script setup lang="ts">
import type { NavigationItem } from '@/types/panfu';
import type { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    logo: string;
    items: NavigationItem[];
}>();

const page = usePage<PageProps>();
const menuOpen = ref(false);
const openDropdown = ref<string | null>(null);

const visibleItems = computed(() => {
    const isAuthenticated = Boolean(page.props.auth.user);

    return props.items.filter((item) => {
        if (isAuthenticated && ['/register', '/login'].includes(item.href)) {
            return false;
        }

        return true;
    });
});

const accountItems = computed<NavigationItem[]>(() => [
    { label: page.props.panfu.chrome.account.settings, href: '/account/settings' },
    { label: page.props.panfu.chrome.account.logout, href: '/logout', method: 'post' },
]);

const accountName = computed(() => page.props.auth.user?.name ?? '');
const accountLabel = computed(() => page.props.panfu.chrome.account.label);
const accountGreeting = computed(() =>
    page.props.panfu.chrome.account.greeting.replace(':name', accountName.value),
);

const isCurrent = (href: string) => {
    if (href === '#') {
        return false;
    }

    if (href === '/') {
        return page.url === '/';
    }

    return page.url === href || page.url.startsWith(`${href}/`);
};

const toggleDropdown = (key: string) => {
    openDropdown.value = openDropdown.value === key ? null : key;
};

const closeMenus = () => {
    openDropdown.value = null;
    menuOpen.value = false;
};

const socialItems = [
    { label: 'Facebook', href: 'https://www.facebook.com/Panfu.me/', icon: 'facebook' },
    { label: 'Instagram', href: 'https://www.instagram.com/teampanfu/', icon: 'instagram' },
    { label: 'Twitter', href: 'https://x.com/teampanfu', icon: 'x-twitter' },
    { label: 'YouTube', href: 'https://www.youtube.com/@teampanfu', icon: 'youtube' },
    { label: 'TikTok', href: 'https://www.tiktok.com/@teampanfu', icon: 'tiktok' },
    { label: 'Discord', href: 'https://discord.gg/6sRx62m6RK', icon: 'discord' },
];
</script>

<template>
    <header class="panfu-navbar">
        <div class="panfu-navbar__inner">
            <Link class="panfu-navbar__brand" href="/" aria-label="Panfu">
                <img :src="logo" alt="Panfu" />
            </Link>

            <button
                class="panfu-navbar__toggle"
                type="button"
                aria-label="Menu"
                :aria-expanded="menuOpen"
                @click="menuOpen = !menuOpen"
            >
                <span />
                <span />
                <span />
            </button>

            <div
                :class="[
                    'panfu-navbar__links',
                    menuOpen ? 'panfu-navbar__links--open' : '',
                ]"
                aria-label="Main navigation"
            >
                <nav class="panfu-navbar__primary" aria-label="Primary">
                    <div
                        v-for="item in visibleItems"
                        :key="item.label"
                        :class="[
                            'panfu-navbar__item',
                            item.children?.length ? 'panfu-navbar__item--dropdown' : '',
                        ]"
                    >
                        <Link
                            v-if="!item.children?.length"
                            :href="item.href"
                            :class="[
                                'panfu-navbar__link',
                                item.active || isCurrent(item.href) ? 'panfu-navbar__link--active' : '',
                            ]"
                            @click="closeMenus"
                        >
                            {{ item.label }}
                        </Link>

                        <button
                            v-else
                            :class="[
                                'panfu-navbar__link',
                                'panfu-navbar__button',
                                'panfu-navbar__link--dropdown',
                                item.active ? 'panfu-navbar__link--active' : '',
                            ]"
                            type="button"
                            :aria-expanded="openDropdown === item.label"
                            @click="toggleDropdown(item.label)"
                        >
                            {{ item.label }}
                        </button>

                        <div
                            v-if="item.children?.length"
                            :class="[
                                'panfu-navbar__dropdown',
                                openDropdown === item.label ? 'panfu-navbar__dropdown--open' : '',
                            ]"
                        >
                            <Link
                                v-for="child in item.children"
                                :key="child.label"
                                :href="child.href"
                                :method="child.method ?? 'get'"
                                :as="child.method === 'post' ? 'button' : 'a'"
                                :class="[
                                    'panfu-navbar__dropdown-link',
                                    child.active ? 'panfu-navbar__dropdown-link--active' : '',
                                ]"
                                @click="closeMenus"
                            >
                                {{ child.label }}
                            </Link>
                        </div>
                    </div>

                    <Link
                        v-if="$page.props.auth.user"
                        class="panfu-navbar__link"
                        href="/play"
                        :class="{ 'panfu-navbar__link--active': isCurrent('/play') }"
                        @click="closeMenus"
                    >
                        Graj
                    </Link>

                    <div
                        v-if="$page.props.auth.user"
                        class="panfu-navbar__item panfu-navbar__item--dropdown"
                    >
                        <button
                            :class="[
                                'panfu-navbar__link',
                                'panfu-navbar__button',
                                'panfu-navbar__link--dropdown',
                                isCurrent('/account/settings') ? 'panfu-navbar__link--active' : '',
                            ]"
                            type="button"
                            :aria-expanded="openDropdown === 'account'"
                            @click="toggleDropdown('account')"
                        >
                            {{ accountLabel }}
                        </button>

                        <div
                            :class="[
                                'panfu-navbar__dropdown',
                                openDropdown === 'account' ? 'panfu-navbar__dropdown--open' : '',
                            ]"
                        >
                            <span class="panfu-navbar__dropdown-header">
                                {{ accountGreeting }}
                            </span>
                            <Link
                                v-for="item in accountItems"
                                :key="item.label"
                                :href="item.href"
                                :method="item.method ?? 'get'"
                                :as="item.method === 'post' ? 'button' : 'a'"
                                class="panfu-navbar__dropdown-link"
                                @click="closeMenus"
                            >
                                {{ item.label }}
                            </Link>
                        </div>
                    </div>
                </nav>

                <hr class="panfu-navbar__separator" />

                <nav class="panfu-navbar__social" aria-label="Social links">
                    <a
                        v-for="item in socialItems"
                        :key="item.label"
                        class="panfu-navbar__social-link"
                        :href="item.href"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <span
                            :class="['panfu-fa panfu-fa--brand', `panfu-fa--${item.icon}`]"
                            aria-hidden="true"
                        />
                        <small>{{ item.label }}</small>
                    </a>
                </nav>
            </div>
        </div>
    </header>
</template>
