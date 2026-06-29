<script setup lang="ts">
import type { HeroContent } from '@/types/panfu';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    hero: HeroContent;
}>();

const page = usePage();

const cta = computed(() => {
    if (page.props.auth.user) {
        return {
            href: '/play',
            label: 'Graj teraz!',
        };
    }

    return props.hero.cta;
});
</script>

<template>
    <section class="home-board board" aria-label="Panfu">
        <h1 class="home-board__title">
            W tej chwili gra {{ hero.playersOnline }} pand!
        </h1>

        <ul class="home-board__list">
            <li
                v-for="feature in hero.features"
                :key="feature.text"
                class="home-board__item"
            >
                <span :class="['home-board__icon', `home-board__icon--${feature.icon}`]" />
                <span>{{ feature.text }}</span>
            </li>
        </ul>

        <Link class="home-board__button" :href="cta.href">
            {{ cta.label }}
        </Link>
    </section>
</template>
