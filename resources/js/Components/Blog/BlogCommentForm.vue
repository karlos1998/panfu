<script setup lang="ts">
import BlogSidebarCard from '@/Components/Blog/BlogSidebarCard.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';
import { computed } from 'vue';

const props = defineProps<{ postUrl: string }>();
const page = usePage<PageProps>();
const form = useForm({ body: '' });
const remaining = computed(() => 255 - form.body.length);

const submit = () => form.post(`${props.postUrl}/comments`, {
    preserveScroll: true,
    onSuccess: () => form.reset(),
});
</script>

<template>
    <BlogSidebarCard title="✎ NAPISZ KOMENTARZ">
        <form v-if="page.props.auth.user" class="panfu-blog-comment-form" @submit.prevent="submit">
            <textarea v-model="form.body" maxlength="255" placeholder="Napisz swój komentarz tutaj..." />
            <p v-if="form.errors.body" class="panfu-blog-form-error">{{ form.errors.body }}</p>
            <div><button type="submit" :disabled="form.processing">Wyślij komentarz</button><span>{{ remaining }} / 255</span></div>
        </form>
        <p v-else class="panfu-blog-login-notice">
            Musisz być zalogowany, aby napisać komentarz.
            <Link :href="`/login?redirect_to=${encodeURIComponent(postUrl.slice(1))}`">Zaloguj się tutaj.</Link>
        </p>
    </BlogSidebarCard>
</template>
