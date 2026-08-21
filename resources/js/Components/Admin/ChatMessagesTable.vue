<script setup lang="ts">
import AdminBadge from '@/Components/Admin/AdminBadge.vue';
import AdminEmptyState from '@/Components/Admin/AdminEmptyState.vue';
import type { AdminChatMessage } from '@/types/admin';
import { Link } from '@inertiajs/vue3';

withDefaults(defineProps<{
    messages: AdminChatMessage[];
    showPlayer?: boolean;
}>(), {
    showPlayer: true,
});

const formatDate = (value: string | null) => value
    ? new Intl.DateTimeFormat('pl-PL', { dateStyle: 'medium', timeStyle: 'medium' }).format(new Date(value))
    : '—';
</script>

<template>
    <div v-if="messages.length" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Data</th>
                    <th v-if="showPlayer" class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Panda</th>
                    <th class="min-w-80 px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Wiadomość</th>
                    <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Pokój</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
                <tr v-for="chatMessage in messages" :key="chatMessage.id" class="align-top hover:bg-slate-50/70">
                    <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-500">{{ formatDate(chatMessage.createdAt) }}</td>
                    <td v-if="showPlayer" class="whitespace-nowrap px-5 py-4 text-sm">
                        <Link v-if="chatMessage.userId" :href="`/admin/users/${chatMessage.userId}`" class="font-semibold text-blue-700 hover:text-blue-900 hover:underline">
                            {{ chatMessage.playerName }}
                        </Link>
                        <span v-else class="font-semibold text-slate-700">{{ chatMessage.playerName }}</span>
                        <span class="ml-1 text-xs text-slate-400">{{ chatMessage.userId ? `#${chatMessage.userId}` : 'konto usunięte' }}</span>
                    </td>
                    <td class="px-5 py-4 text-sm leading-6 text-slate-800">
                        <p class="max-w-3xl whitespace-pre-wrap break-words">{{ chatMessage.message }}</p>
                    </td>
                    <td class="whitespace-nowrap px-5 py-4 text-sm">
                        <Link v-if="chatMessage.room.adminUrl" :href="chatMessage.room.adminUrl" class="inline-flex rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <AdminBadge :tone="chatMessage.room.type === 'home' ? 'amber' : 'blue'">{{ chatMessage.room.label }} →</AdminBadge>
                        </Link>
                        <AdminBadge v-else tone="slate">{{ chatMessage.room.label }}</AdminBadge>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <AdminEmptyState v-else title="Brak wiadomości" description="Nie znaleziono wiadomości pasujących do wybranych kryteriów.">
        <template #icon>💬</template>
    </AdminEmptyState>
</template>
