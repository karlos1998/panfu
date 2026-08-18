<script setup lang="ts">
import type { BlogPostData } from '@/types/blog';
import { Link } from '@inertiajs/vue3';

defineProps<{ post: BlogPostData }>();
</script>

<template>
    <article class="panfu-blog-card panfu-blog-post">
        <header class="panfu-blog-card__header panfu-blog-post__meta">
            <strong>▣ POST NA BLOGU</strong>
            <div>
                <span>◷ {{ post.publishedLabel }}</span>
                <Link :href="`/blog?category=${post.category.slug}`">◇ {{ post.category.name }}</Link>
                <Link :href="`${post.url}#comments`">☁ {{ post.commentsCount }} {{ post.commentsCount === 1 ? 'komentarz' : 'komentarzy' }}</Link>
            </div>
        </header>
        <div class="panfu-blog-card__body panfu-blog-post__body">
            <Link class="panfu-blog-post__title" :href="post.url">{{ post.title }}</Link>
            <div class="panfu-blog-markdown" v-html="post.bodyHtml" />
        </div>
    </article>
</template>
