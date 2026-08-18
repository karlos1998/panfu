<script setup lang="ts">
import AdminCard from '@/Components/Admin/AdminCard.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

interface Category { id: number; name: string }
interface Post { id: number; title: string; slug: string; categoryId: number; body: string; publishedAt: string | null }
const props = defineProps<{ post: Post | null; categories: Category[] }>();
const form = useForm({
    title: props.post?.title ?? '',
    slug: props.post?.slug ?? '',
    blog_category_id: props.post?.categoryId ?? props.categories[0]?.id ?? 0,
    body: props.post?.body ?? '',
    published_at: props.post?.publishedAt ?? '',
});
const submit = () => props.post
    ? form.patch(`/admin/blog/posts/${props.post.slug}`)
    : form.post('/admin/blog/posts');
</script>

<template>
    <AdminLayout :title="post ? 'Edycja wpisu' : 'Nowy wpis'">
        <Link class="panfu-admin-back-link" href="/admin/blog">← Wróć do bloga</Link>
        <header class="panfu-admin-page-header">
            <div><h1>{{ post ? 'Edytuj wpis' : 'Nowy wpis' }}</h1><p>Treść jest zapisywana i bezpiecznie renderowana jako Markdown.</p></div>
            <Link v-if="post?.slug && post.publishedAt" class="panfu-admin-secondary-button" :href="`/blog/${post.slug}`">Zobacz wpis</Link>
        </header>

        <form class="panfu-admin-blog-editor" @submit.prevent="submit">
            <AdminCard title="Publikacja" description="Tytuł, adres i sekcja wpisu.">
                <div class="panfu-admin-blog-editor__grid">
                    <label>Tytuł<input v-model="form.title" required /><small>{{ form.errors.title }}</small></label>
                    <label>Slug<input v-model="form.slug" placeholder="uzupełni się z tytułu" /><small>{{ form.errors.slug }}</small></label>
                    <label>Kategoria<select v-model="form.blog_category_id"><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select></label>
                    <label>Data publikacji<input v-model="form.published_at" type="datetime-local" /><small>Puste pole oznacza wersję roboczą.</small></label>
                </div>
            </AdminCard>

            <AdminCard title="Treść Markdown" description="Nagłówki, listy, linki, obrazy, cytaty i fragmenty kodu.">
                <textarea v-model="form.body" class="panfu-admin-markdown-editor" required spellcheck="true" placeholder="# Tytuł sekcji&#10;&#10;Treść wpisu..." />
                <small class="panfu-admin-field-error">{{ form.errors.body }}</small>
            </AdminCard>

            <div class="panfu-admin-blog-editor__actions">
                <button class="panfu-admin-primary-button" type="submit" :disabled="form.processing">Zapisz wpis</button>
                <Link v-if="post" as="button" method="delete" class="panfu-admin-danger-button" :href="`/admin/blog/posts/${post.slug}`">Usuń wpis</Link>
            </div>
        </form>
    </AdminLayout>
</template>
