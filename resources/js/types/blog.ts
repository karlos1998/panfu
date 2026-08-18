import type { PandaAvatarData } from '@/types/panfu';

export interface BlogCategoryData { name: string; slug: string }

export interface BlogPostData {
    id: number;
    title: string;
    slug: string;
    url: string;
    bodyHtml: string;
    publishedAt: string;
    publishedLabel: string;
    category: BlogCategoryData;
    commentsCount: number;
}

export interface BlogCommentData {
    id: number;
    authorName: string;
    body: string;
    createdLabel: string;
    avatar: PandaAvatarData;
}

export interface TopCommenterData {
    name: string;
    commentsCount: number;
    avatar: PandaAvatarData;
}

export interface LatestCommentData extends BlogCommentData {
    post: { title: string; url: string };
}
