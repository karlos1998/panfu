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
        if (isAuthenticated && ['Rejestracja', 'Zaloguj się'].includes(item.label)) {
            return false;
        }

        return true;
    });
});
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

            <nav
                :class="[
                    'panfu-navbar__links',
                    menuOpen ? 'panfu-navbar__links--open' : '',
                ]"
                aria-label="Główna nawigacja"
            >
                <Link
                    v-for="item in visibleItems"
                    :key="item.label"
                    :href="item.href"
                    :class="[
                        'panfu-navbar__link',
                        item.variant ? `panfu-navbar__link--${item.variant}` : '',
                    ]"
                >
                    {{ item.label }}
                </Link>

                <Link
                    v-if="$page.props.auth.user"
                    class="panfu-navbar__link panfu-navbar__link--primary"
                    href="/play"
                >
                    Graj
                </Link>
            </nav>
        </div>
    </header>
</template>
