<script setup lang="ts">
import BlogSidebarCard from '@/Components/Blog/BlogSidebarCard.vue';
import PandaAvatar from '@/Components/Panfu/PandaAvatar.vue';
import type { LatestCommentData, TopCommenterData } from '@/types/blog';
import { Link } from '@inertiajs/vue3';

defineProps<{ topCommenters: TopCommenterData[]; latestComments: LatestCommentData[] }>();
</script>

<template>
    <aside class="panfu-blog-sidebar">
        <BlogSidebarCard title="♕ NAJLEPSI KOMENTUJĄCY">
            <div v-if="topCommenters.length" class="panfu-blog-people">
                <div v-for="person in topCommenters" :key="person.name" class="panfu-blog-person">
                    <PandaAvatar :avatar="person.avatar" :name="person.name" />
                    <div><strong>{{ person.name }}</strong><span>{{ person.commentsCount }} {{ person.commentsCount === 1 ? 'komentarz' : 'komentarze' }}</span></div>
                </div>
            </div>
            <p v-else class="panfu-blog-empty">Jeszcze nikt nie komentował.</p>
        </BlogSidebarCard>

        <BlogSidebarCard title="☁ NAJNOWSZE KOMENTARZE">
            <div v-if="latestComments.length" class="panfu-blog-people">
                <div v-for="comment in latestComments" :key="comment.id" class="panfu-blog-person panfu-blog-person--comment">
                    <PandaAvatar :avatar="comment.avatar" :name="comment.authorName" />
                    <div>
                        <strong>{{ comment.authorName }} › <Link :href="`${comment.post.url}#comments`">{{ comment.post.title }}</Link></strong>
                        <span>{{ comment.body }}</span>
                    </div>
                </div>
            </div>
            <p v-else class="panfu-blog-empty">Brak komentarzy.</p>
        </BlogSidebarCard>
    </aside>
</template>
