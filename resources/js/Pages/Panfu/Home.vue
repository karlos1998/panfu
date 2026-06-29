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
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    meta: MetaContent;
    navigation: NavigationItem[];
    hero: HeroContent;
    news: NewsContent;
    about: AboutContent;
    footer: FooterContent;
    assets: PanfuAssets;
}>();

const page = usePage();
const heroHref = computed(() => (page.props.auth.user ? '/play' : '/register'));
</script>

<template>
    <PanfuLayout
        :meta="meta"
        :logo="assets.logo"
        :navigation="navigation"
        :footer="footer"
        main-class="panfu-main--trees"
    >
        <section class="panfu-home">
            <div class="panfu-home__hero-row">
                <Link class="panfu-home__video-link" :href="heroHref" aria-label="Panfu">
                    <video width="540" height="333" autoplay muted loop playsinline>
                        <source :src="assets.heroVideo" type="video/webm" />
                    </video>
                </Link>

                <HomeBoard :hero="hero" />
            </div>

            <div class="panfu-home__cards">
                <article id="blog" class="panfu-card">
                    <header class="panfu-card__header">
                        <span class="panfu-fa panfu-fa--newspaper" aria-hidden="true" />
                        {{ news.eyebrow }}
                    </header>

                    <div class="panfu-card__body">
                        <Link class="panfu-card__article-link" :href="news.button.href">
                            <h2 class="panfu-card__title">{{ news.title }}</h2>
                            <p class="panfu-card__text">{{ news.excerpt }}</p>
                        </Link>
                    </div>

                    <footer class="panfu-card__footer">
                        <Link class="panfu-outline-button" :href="news.button.href">
                            {{ news.button.label }}
                        </Link>
                    </footer>
                </article>

                <article class="panfu-card">
                    <header class="panfu-card__header">
                        <span class="panfu-fa panfu-fa--question" aria-hidden="true" />
                        {{ about.title }}
                    </header>

                    <div class="panfu-card__body">
                        <ul class="panfu-check-list">
                            <li
                                v-for="(point, index) in about.points"
                                :key="point"
                                :class="{ 'panfu-check-list__item--last': index === about.points.length - 1 }"
                            >
                                {{ point }}
                            </li>
                        </ul>
                    </div>

                    <footer class="panfu-card__footer">
                        <Link class="panfu-outline-button" :href="about.button.href">
                            {{ about.button.label }}
                        </Link>
                    </footer>
                </article>

                <article class="panfu-card panfu-card--discord" aria-label="Discord">
                    <iframe
                        class="panfu-discord-widget"
                        title="Discord"
                        src="https://discord.com/widget?id=423866952394473474&theme=light"
                    />
                </article>
            </div>
        </section>
    </PanfuLayout>
</template>
