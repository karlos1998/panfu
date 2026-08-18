<script setup lang="ts">
import PandaAvatar from '@/Components/Panfu/PandaAvatar.vue';
import type { BlogCommentData } from '@/types/blog';

defineProps<{ comment: BlogCommentData }>();
</script>

<template>
    <article class="panfu-blog-card panfu-blog-comment">
        <header class="panfu-blog-card__header panfu-blog-comment__header">
            <PandaAvatar :avatar="comment.avatar" :name="comment.authorName" />
            <div><strong>{{ comment.authorName }}</strong><span>{{ comment.createdLabel }}</span></div>
        </header>
        <div class="panfu-blog-card__body">{{ comment.body }}</div>
    </article>
</template>
