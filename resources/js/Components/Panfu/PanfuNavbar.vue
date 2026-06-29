<script setup lang="ts">
import type { NavigationItem } from '@/types/panfu';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    logo: string;
    items: NavigationItem[];
}>();

const page = usePage();
const menuOpen = ref(false);

const visibleItems = computed(() => {
    const isAuthenticated = Boolean(page.props.auth.user);

    return props.items.filter((item) => {
        if (isAuthenticated && ['Registration', 'Login', 'Rejestracja', 'Zaloguj się'].includes(item.label)) {
            return false;
        }

        return true;
    });
});

const accountItems = computed<NavigationItem[]>(() => [
    { label: 'Ustawienia konta', href: '/profile' },
    { label: 'Wyloguj się', href: '/logout', method: 'post' },
]);

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
                            :href="item.href"
                            :class="[
                                'panfu-navbar__link',
                                item.active ? 'panfu-navbar__link--active' : '',
                                item.children?.length ? 'panfu-navbar__link--dropdown' : '',
                            ]"
                        >
                            {{ item.label }}
                        </Link>

                        <div v-if="item.children?.length" class="panfu-navbar__dropdown">
                            <Link
                                v-for="child in item.children"
                                :key="child.label"
                                :href="child.href"
                                :class="[
                                    'panfu-navbar__dropdown-link',
                                    child.active ? 'panfu-navbar__dropdown-link--active' : '',
                                ]"
                            >
                                {{ child.label }}
                            </Link>
                        </div>
                    </div>

                    <Link
                        v-if="$page.props.auth.user"
                        class="panfu-navbar__link"
                        href="/play"
                    >
                        Graj
                    </Link>

                    <div
                        v-if="$page.props.auth.user"
                        class="panfu-navbar__item panfu-navbar__item--dropdown"
                    >
                        <Link class="panfu-navbar__link panfu-navbar__link--dropdown" href="/profile">
                            Moje konto
                        </Link>

                        <div class="panfu-navbar__dropdown">
                            <Link
                                v-for="item in accountItems"
                                :key="item.label"
                                :href="item.href"
                                :method="item.method ?? 'get'"
                                :as="item.method === 'post' ? 'button' : 'a'"
                                class="panfu-navbar__dropdown-link"
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
