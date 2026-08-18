<script setup lang="ts">
import BlogCommentCard from '@/Components/Blog/BlogCommentCard.vue';
import BlogCommentForm from '@/Components/Blog/BlogCommentForm.vue';
import BlogPostCard from '@/Components/Blog/BlogPostCard.vue';
import PanfuLayout from '@/Layouts/PanfuLayout.vue';
import type { BlogCommentData, BlogPostData } from '@/types/blog';
import type { MetaContent } from '@/types/panfu';
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    post: BlogPostData;
    comments: BlogCommentData[];
    previous: { title: string; url: string } | null;
    next: { title: string; url: string } | null;
}>();

const meta: MetaContent = { title: `${props.post.title} - Panfu.me`, description: props.post.title };
</script>

<template>
    <PanfuLayout :meta="meta" logo="/vendor/panfu-me/assets/panfu-logo-BkIF66dU.svg" main-class="panfu-main--blog">
        <section class="panfu-blog">
            <nav class="panfu-blog-adjacent" aria-label="Sąsiednie wpisy">
                <Link v-if="previous" :href="previous.url">« Poprzedni post</Link><span v-else />
                <Link v-if="next" :href="next.url">Następny post »</Link>
            </nav>
            <div class="panfu-blog-grid">
                <main class="panfu-blog-feed"><BlogPostCard :post="post" /></main>
                <aside id="comments" class="panfu-blog-sidebar">
                    <BlogCommentForm :post-url="post.url" />
                    <BlogCommentCard v-for="comment in comments" :key="comment.id" :comment="comment" />
                    <div v-if="!comments.length" class="panfu-blog-empty-card">Ten wpis nie ma jeszcze komentarzy.</div>
                </aside>
            </div>
        </section>
    </PanfuLayout>
</template>
