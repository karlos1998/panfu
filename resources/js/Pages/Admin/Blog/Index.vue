<script setup lang="ts">
import AdminCard from '@/Components/Admin/AdminCard.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Paginated } from '@/types/admin';
import { Link, useForm } from '@inertiajs/vue3';

interface Post { id: number; title: string; slug: string; categoryName: string; publishedAt: string | null; commentsCount: number; url: string }
interface Category { id: number; name: string; slug: string; sortOrder: number; isActive: boolean; postsCount: number }
interface Comment { id: number; authorName: string; body: string; postTitle: string; createdAt: string }
const props = defineProps<{ posts: Paginated<Post>; categories: Category[]; comments: Comment[] }>();

const categoryForm = useForm({ name: '', slug: '', sort_order: 60, is_active: true });
const createCategory = () => categoryForm.post('/admin/blog/categories', { onSuccess: () => categoryForm.reset() });
const updateCategory = (category: Category) => useForm({
    name: category.name, slug: category.slug, sort_order: category.sortOrder, is_active: category.isActive,
}).patch(`/admin/blog/categories/${category.id}`);
</script>

<template>
    <AdminLayout title="Blog">
        <header class="panfu-admin-page-header">
            <div><h1>Blog</h1><p>Publikuj wpisy Markdown i zarządzaj sekcjami bloga.</p></div>
            <Link class="panfu-admin-primary-button" href="/admin/blog/posts/create">Nowy wpis</Link>
        </header>

        <AdminCard title="Wpisy" description="Wersje robocze, publikacje i komentarze." :padded="false">
            <div class="panfu-admin-blog-posts">
                <Link v-for="post in posts.data" :key="post.id" :href="post.url" class="panfu-admin-blog-post-row">
                    <div><strong>{{ post.title }}</strong><span>{{ post.categoryName }} · /{{ post.slug }}</span></div>
                    <span>{{ post.publishedAt ? 'Opublikowany' : 'Wersja robocza' }}</span>
                    <span>{{ post.commentsCount }} komentarzy</span>
                </Link>
                <p v-if="!posts.data.length" class="panfu-admin-blog-empty">Brak wpisów.</p>
            </div>
            <AdminPagination :pagination="posts" />
        </AdminCard>

        <AdminCard title="Najnowsze komentarze" description="Podgląd i moderacja wypowiedzi graczy." :padded="false">
            <div class="panfu-admin-blog-comments">
                <div v-for="comment in comments" :key="comment.id" class="panfu-admin-blog-comment-row">
                    <div><strong>{{ comment.authorName }} · {{ comment.postTitle }}</strong><span>{{ comment.body }}</span></div>
                    <time>{{ comment.createdAt }}</time>
                    <Link as="button" method="delete" :href="`/admin/blog/comments/${comment.id}`">Usuń</Link>
                </div>
                <p v-if="!comments.length" class="panfu-admin-blog-empty">Brak komentarzy.</p>
            </div>
        </AdminCard>

        <AdminCard title="Kategorie" description="Kolejność i widoczność filtrów na blogu.">
            <div class="panfu-admin-blog-categories">
                <form v-for="category in categories" :key="category.id" class="panfu-admin-blog-category" @submit.prevent="updateCategory(category)">
                    <input v-model="category.name" aria-label="Nazwa kategorii" />
                    <input v-model="category.slug" aria-label="Slug kategorii" />
                    <input v-model.number="category.sortOrder" type="number" aria-label="Kolejność" />
                    <label><input v-model="category.isActive" type="checkbox" /> Widoczna</label>
                    <span>{{ category.postsCount }} wpisów</span>
                    <button type="submit">Zapisz</button>
                    <Link v-if="category.postsCount === 0" as="button" method="delete" :href="`/admin/blog/categories/${category.id}`">Usuń</Link>
                </form>
            </div>
            <form class="panfu-admin-blog-category panfu-admin-blog-category--new" @submit.prevent="createCategory">
                <input v-model="categoryForm.name" required placeholder="Nowa kategoria" />
                <input v-model="categoryForm.slug" placeholder="slug (automatyczny)" />
                <input v-model.number="categoryForm.sort_order" type="number" min="0" />
                <label><input v-model="categoryForm.is_active" type="checkbox" /> Widoczna</label>
                <span />
                <button type="submit" :disabled="categoryForm.processing">Dodaj</button>
            </form>
        </AdminCard>
    </AdminLayout>
</template>
