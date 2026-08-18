<script setup lang="ts">
import BlogPostCard from '@/Components/Blog/BlogPostCard.vue';
import BlogSidebar from '@/Components/Blog/BlogSidebar.vue';
import PanfuLayout from '@/Layouts/PanfuLayout.vue';
import type { Paginated } from '@/types/admin';
import type { BlogCategoryData, BlogPostData, LatestCommentData, TopCommenterData } from '@/types/blog';
import type { MetaContent } from '@/types/panfu';
import { Link } from '@inertiajs/vue3';

defineProps<{
    categories: BlogCategoryData[];
    activeCategory: string | null;
    posts: Paginated<BlogPostData>;
    topCommenters: TopCommenterData[];
    latestComments: LatestCommentData[];
}>();

const meta: MetaContent = { title: 'Blog - Panfu.me', description: 'Aktualności, konkursy, poradniki i życie społeczności Panfu.' };
</script>

<template>
    <PanfuLayout :meta="meta" logo="/vendor/panfu-me/assets/panfu-logo-BkIF66dU.svg" main-class="panfu-main--blog">
        <section class="panfu-blog">
            <nav class="panfu-blog-categories" aria-label="Kategorie bloga">
                <Link
                    v-for="category in categories"
                    :key="category.slug"
                    :href="`/blog?category=${category.slug}`"
                    :class="{ 'panfu-blog-category--active': activeCategory === category.slug }"
                >{{ category.name }}</Link>
            </nav>

            <div class="panfu-blog-grid">
                <main class="panfu-blog-feed">
                    <BlogPostCard v-for="post in posts.data" :key="post.id" :post="post" />
                    <div v-if="!posts.data.length" class="panfu-blog-empty-card">W tej kategorii nie ma jeszcze wpisów.</div>
                    <nav v-if="posts.last_page > 1" class="panfu-blog-pagination" aria-label="Paginacja">
                        <component
                            :is="link.url ? Link : 'span'"
                            v-for="link in posts.links"
                            :key="link.label"
                            :href="link.url ?? undefined"
                            :class="{ 'panfu-blog-pagination__active': link.active }"
                            v-html="link.label"
                        />
                    </nav>
                </main>
                <BlogSidebar :top-commenters="topCommenters" :latest-comments="latestComments" />
            </div>
        </section>
    </PanfuLayout>
</template>
