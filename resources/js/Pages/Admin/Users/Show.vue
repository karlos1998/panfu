<script setup lang="ts">
import AdminBadge from '@/Components/Admin/AdminBadge.vue';
import AdminCard from '@/Components/Admin/AdminCard.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type {
    GameServerOption,
    InventoryEntry,
    ItemOption,
    ManagedUser,
    PlayerState,
    SelectOption,
    UserOption,
    UserRelation,
    UserRole,
    UserSession,
} from '@/types/admin';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InventoryPanel from './Partials/InventoryPanel.vue';
import RelationsPanel from './Partials/RelationsPanel.vue';
import SessionsPanel from './Partials/SessionsPanel.vue';
import StatesPanel from './Partials/StatesPanel.vue';
import UserProfileForm from './Partials/UserProfileForm.vue';

interface Options {
    roles: SelectOption<UserRole>[];
    relationTypes: SelectOption<number>[];
    items: ItemOption[];
    users: UserOption[];
    gameServers: GameServerOption[];
}

const props = defineProps<{
    managedUser: ManagedUser;
    inventory: InventoryEntry[];
    states: PlayerState[];
    relations: UserRelation[];
    sessions: UserSession[];
    options: Options;
}>();

type Tab = 'profile' | 'inventory' | 'states' | 'relations' | 'sessions';
const activeTab = ref<Tab>('profile');
const tabs = computed(() => [
    { id: 'profile' as Tab, label: 'Konto', count: null },
    { id: 'inventory' as Tab, label: 'Przedmioty', count: props.inventory.length },
    { id: 'states' as Tab, label: 'Osiągnięcia', count: props.states.length },
    { id: 'relations' as Tab, label: 'Relacje', count: props.relations.length },
    { id: 'sessions' as Tab, label: 'Sesje', count: props.sessions.length },
]);

const number = (value: number) => new Intl.NumberFormat('pl-PL').format(value);
const formatDate = (value: string | null) => value
    ? new Intl.DateTimeFormat('pl-PL', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : '—';
</script>

<template>
    <AdminLayout :title="`Panda ${managedUser.name}`">
        <Link href="/admin/users" class="panfu-admin-back-link mb-5 inline-flex items-center gap-2 text-sm font-medium">← Wróć do użytkowników</Link>

        <section class="panfu-admin-user-summary mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="panfu-admin-user-summary__hero px-5 py-7 sm:px-7">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="panfu-admin-user-summary__avatar flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl text-2xl font-black">{{ managedUser.name.charAt(0).toUpperCase() }}</div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-2xl font-bold tracking-tight">{{ managedUser.name }}</h1>
                                <AdminBadge v-if="managedUser.role === 'admin'" tone="blue">Administrator</AdminBadge>
                                <AdminBadge v-if="managedUser.goldpanda" tone="amber">Gold Panda</AdminBadge>
                                <AdminBadge v-if="managedUser.sheriff" tone="green">Szeryf</AdminBadge>
                            </div>
                            <p class="panfu-admin-user-summary__email mt-1 text-sm">{{ managedUser.email }} · ID {{ managedUser.id }}</p>
                        </div>
                    </div>
                    <div class="panfu-admin-user-summary__stats grid grid-cols-3 gap-5 text-center sm:text-right">
                        <div><div class="text-xl font-bold">{{ managedUser.socialLevel }}</div><div class="text-xs">Poziom</div></div>
                        <div><div class="text-xl font-bold">{{ number(managedUser.coins) }}</div><div class="text-xs">Monety</div></div>
                        <div><div class="text-xl font-bold">{{ number(managedUser.socialScore) }}</div><div class="text-xs">Punkty</div></div>
                    </div>
                </div>
            </div>
            <div class="grid gap-px bg-slate-100 sm:grid-cols-3">
                <div class="bg-white px-5 py-3"><div class="text-xs uppercase tracking-wide text-slate-400">Utworzono</div><div class="mt-1 text-sm font-medium text-slate-700">{{ formatDate(managedUser.createdAt) }}</div></div>
                <div class="bg-white px-5 py-3"><div class="text-xs uppercase tracking-wide text-slate-400">Ostatnie logowanie w grze</div><div class="mt-1 text-sm font-medium text-slate-700">{{ managedUser.lastLogin ?? '—' }}</div></div>
                <div class="bg-white px-5 py-3"><div class="text-xs uppercase tracking-wide text-slate-400">Ostatnia zmiana konta</div><div class="mt-1 text-sm font-medium text-slate-700">{{ formatDate(managedUser.updatedAt) }}</div></div>
            </div>
        </section>

        <div class="panfu-admin-tabs mb-6 overflow-x-auto">
            <nav class="flex min-w-max gap-1" aria-label="Sekcje użytkownika">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    class="panfu-admin-tab text-sm font-semibold transition"
                    :class="activeTab === tab.id ? 'panfu-admin-tab--active' : ''"
                    type="button"
                    @click="activeTab = tab.id"
                >
                    {{ tab.label }}
                    <span v-if="tab.count !== null" class="panfu-admin-tab__count ml-1 rounded-full px-2 py-0.5 text-xs">{{ tab.count }}</span>
                </button>
            </nav>
        </div>

        <AdminCard v-if="activeTab === 'profile'" title="Konto pandy" description="Dane konta, uprawnienia i parametry rozgrywki">
            <UserProfileForm :user="managedUser" :roles="options.roles" :game-servers="options.gameServers" />
        </AdminCard>
        <AdminCard v-else-if="activeTab === 'inventory'" title="Zarządzanie przedmiotami" description="Pełny ekwipunek pandy wraz ze stanem wyposażenia">
            <InventoryPanel :user-id="managedUser.id" :inventory="inventory" :items="options.items" />
        </AdminCard>
        <AdminCard v-else-if="activeTab === 'states'" title="Postęp i osiągnięcia" description="Surowe stany wykorzystywane przez klienta gry">
            <StatesPanel :user-id="managedUser.id" :states="states" />
        </AdminCard>
        <AdminCard v-else-if="activeTab === 'relations'" title="Znajomi i blokady" description="Relacje tej pandy z pozostałymi użytkownikami">
            <RelationsPanel :user-id="managedUser.id" :relations="relations" :users="options.users" :relation-types="options.relationTypes" />
        </AdminCard>
        <AdminCard v-else title="Sesje logowania" description="Aktywność urządzeń i możliwość zdalnego wylogowania">
            <SessionsPanel :user-id="managedUser.id" :sessions="sessions" />
        </AdminCard>
    </AdminLayout>
</template>
