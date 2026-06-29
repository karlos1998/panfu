<script setup lang="ts">
import HomeBoard from '@/Components/Panfu/HomeBoard.vue';
import PanfuLayout from '@/Layouts/PanfuLayout.vue';
import type {
    AboutContent,
    FooterContent,
    HeroContent,
    MetaContent,
    NavigationItem,
    NewsContent,
    PanfuAssets,
} from '@/types/panfu';
import { Link } from '@inertiajs/vue3';

defineProps<{
    meta: MetaContent;
    navigation: NavigationItem[];
    hero: HeroContent;
    news: NewsContent;
    about: AboutContent;
    footer: FooterContent;
    assets: PanfuAssets;
}>();
</script>

<template>
    <PanfuLayout
        :meta="meta"
        :logo="assets.logo"
        :navigation="navigation"
        :footer="footer"
        main-class="panfu-main--trees"
    >
        <section class="panfu-home-hero">
            <div class="panfu-home-hero__media" aria-hidden="true">
                <video
                    :poster="assets.grasslands"
                    :src="assets.heroVideo"
                    autoplay
                    muted
                    loop
                    playsinline
                />
            </div>

            <HomeBoard :hero="hero" />
        </section>

        <section id="blog" class="panfu-section panfu-section--news">
            <div class="panfu-section__inner panfu-news">
                <article class="panfu-news__article">
                    <p class="panfu-eyebrow">{{ news.eyebrow }}</p>
                    <h2>{{ news.title }}</h2>
                    <p>{{ news.excerpt }}</p>
                    <Link class="panfu-button panfu-button--green" :href="news.button.href">
                        {{ news.button.label }}
                    </Link>
                </article>

                <figure class="panfu-news__image">
                    <img :src="assets.grasslands" alt="Panfu" />
                </figure>
            </div>
        </section>

        <section class="panfu-section panfu-section--about">
            <div class="panfu-section__inner panfu-about">
                <div class="panfu-about__copy">
                    <h2>{{ about.title }}</h2>
                    <p>{{ about.intro }}</p>
                </div>

                <ul class="panfu-about__list">
                    <li v-for="point in about.points" :key="point">
                        {{ point }}
                    </li>
                </ul>
            </div>
        </section>
    </PanfuLayout>
</template>
