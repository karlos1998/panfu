<script setup lang="ts">
import AdminBadge from '@/Components/Admin/AdminBadge.vue';
import AdminEmptyState from '@/Components/Admin/AdminEmptyState.vue';
import type { UserSession } from '@/types/admin';
import { router } from '@inertiajs/vue3';

const props = defineProps<{ userId: number; sessions: UserSession[] }>();
const formatDate = (value: string) => new Intl.DateTimeFormat('pl-PL', { dateStyle: 'medium', timeStyle: 'medium' }).format(new Date(value));
const revoke = (session: UserSession) => {
    if (window.confirm('Unieważnić tę sesję i wylogować urządzenie?')) {
        router.delete(`/admin/users/${props.userId}/sessions/${encodeURIComponent(session.id)}`, { preserveScroll: true });
    }
};
</script>

<template>
    <div>
        <div>
            <h3 class="text-sm font-semibold text-slate-900">Sesje logowania ({{ sessions.length }})</h3>
            <p class="mt-1 text-sm text-slate-500">Sesje WWW zapisane w bazie. Możesz wylogować pojedyncze urządzenie.</p>
        </div>
        <div v-if="sessions.length" class="mt-5 divide-y divide-slate-100 rounded-xl border border-slate-200">
            <article v-for="session in sessions" :key="session.id" class="flex flex-col gap-4 p-4 lg:flex-row lg:items-center">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-lg">▣</div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-semibold text-slate-900">{{ session.ipAddress ?? 'Nieznany adres IP' }}</p>
                        <AdminBadge :tone="session.active ? 'green' : 'slate'">{{ session.active ? 'Aktywna' : 'Wygasła' }}</AdminBadge>
                        <AdminBadge v-if="session.current" tone="blue">Bieżąca</AdminBadge>
                    </div>
                    <p class="mt-1 truncate text-xs text-slate-500" :title="session.userAgent ?? ''">{{ session.userAgent ?? 'Brak informacji o przeglądarce' }}</p>
                    <p class="mt-1 text-xs text-slate-400">Ostatnia aktywność: {{ formatDate(session.lastActivity) }}</p>
                </div>
                <button class="self-start rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 lg:self-auto" type="button" @click="revoke(session)">Unieważnij</button>
            </article>
        </div>
        <AdminEmptyState v-else title="Brak sesji w bazie" description="Użytkownik nie ma zapisanych sesji WWW albo aplikacja korzysta z plikowego sterownika sesji." />
    </div>
</template>
