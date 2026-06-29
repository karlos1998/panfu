<script setup lang="ts">
import type { HeroContent } from '@/types/panfu';
import { Link } from '@inertiajs/vue3';

defineProps<{
    hero: HeroContent;
}>();
</script>

<template>
    <section class="home-board" aria-label="Panfu">
        <h1 class="home-board__title">
            W tej chwili gra
            <strong>{{ hero.playersOnline }}</strong>
            pand!
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

        <Link class="home-board__button" :href="hero.cta.href">
            {{ hero.cta.label }}
        </Link>
    </section>
</template>
