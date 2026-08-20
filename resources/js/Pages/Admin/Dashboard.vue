<script setup lang="ts">
import AdminBadge from '@/Components/Admin/AdminBadge.vue';
import AdminCard from '@/Components/Admin/AdminCard.vue';
import AdminMetricCard from '@/Components/Admin/AdminMetricCard.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { UserRole } from '@/types/admin';
import type { AdminMetricTone } from '@/types/ui';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Metrics {
    users: number;
    admins: number;
    goldPandas: number;
    sheriffs: number;
    activeSessions: number;
    inventoryItems: number;
    states: number;
    relations: number;
}

interface RecentUser {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    createdAt: string | null;
}

const props = defineProps<{ metrics: Metrics; recentUsers: RecentUser[] }>();

const number = (value: number) => new Intl.NumberFormat('pl-PL').format(value);
const cards = computed<Array<{
    label: string;
    value: number;
    detail: string;
    tone: AdminMetricTone;
    icon: string;
}>>(() => [
    {
        label: 'Wszystkie pandy',
        value: props.metrics.users,
        detail: `${props.metrics.admins} administratorów`,
        tone: 'blue',
        icon: 'P',
    },
    {
        label: 'Gold Panda',
        value: props.metrics.goldPandas,
        detail: 'aktywne członkostwa',
        tone: 'amber',
        icon: 'G',
    },
    {
        label: 'Aktywne sesje',
        value: props.metrics.activeSessions,
        detail: 'w czasie ważności sesji',
        tone: 'emerald',
        icon: 'S',
    },
    {
        label: 'Przedmioty pand',
        value: props.metrics.inventoryItems,
        detail: 'w ekwipunkach',
        tone: 'violet',
        icon: 'I',
    },
]);

const formatDate = (value: string | null) => value
    ? new Intl.DateTimeFormat('pl-PL', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : '—';
</script>

<template>
    <AdminLayout title="Pulpit administratora">
        <div class="panfu-admin-page-header mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Panfu</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Dzień dobry, Adminie</h1>
                <p class="mt-2 text-sm text-slate-500">Najważniejsze informacje o społeczności w jednym miejscu.</p>
            </div>
            <Link href="/admin/users" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                Zarządzaj użytkownikami
            </Link>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <AdminMetricCard
                v-for="card in cards"
                :key="card.label"
                :label="card.label"
                :value="number(card.value)"
                :detail="card.detail"
                :tone="card.tone"
                :icon="card.icon"
            />
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(280px,1fr)]">
            <AdminCard title="Ostatnio zarejestrowane pandy" description="Najnowsze konta w społeczności" :padded="false">
                <div class="divide-y divide-slate-100">
                    <Link v-for="user in recentUsers" :key="user.id" :href="`/admin/users/${user.id}`" class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 font-bold text-slate-600">{{ user.name.charAt(0).toUpperCase() }}</div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ user.name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ user.email }}</p>
                        </div>
                        <AdminBadge v-if="user.role === 'admin'" tone="blue">Admin</AdminBadge>
                        <AdminBadge v-else-if="user.role === 'moderator'" tone="green">Moderator</AdminBadge>
                        <time class="hidden text-xs text-slate-400 sm:block">{{ formatDate(user.createdAt) }}</time>
                    </Link>
                </div>
            </AdminCard>

            <AdminCard title="Dane gry" description="Stan zasobów wszystkich pand">
                <dl class="space-y-4">
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                        <dt class="text-sm text-slate-500">Szeryfowie</dt>
                        <dd class="font-bold text-slate-900">{{ number(metrics.sheriffs) }}</dd>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                        <dt class="text-sm text-slate-500">Stany i osiągnięcia</dt>
                        <dd class="font-bold text-slate-900">{{ number(metrics.states) }}</dd>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                        <dt class="text-sm text-slate-500">Relacje między pandami</dt>
                        <dd class="font-bold text-slate-900">{{ number(metrics.relations) }}</dd>
                    </div>
                </dl>
            </AdminCard>
        </div>
    </AdminLayout>
</template>
