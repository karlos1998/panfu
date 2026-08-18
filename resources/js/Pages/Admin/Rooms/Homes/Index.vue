<script setup lang="ts">
import AdminBadge from '@/Components/Admin/AdminBadge.vue';
import AdminCard from '@/Components/Admin/AdminCard.vue';
import AdminEmptyState from '@/Components/Admin/AdminEmptyState.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import RoomsSubnav from '@/Components/Admin/Rooms/RoomsSubnav.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Paginated, PlayerHomeSummary } from '@/types/admin';
import { Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

interface Filters { search: string; status: string; sort: string }

const props = defineProps<{ homes: Paginated<PlayerHomeSummary>; filters: Filters }>();
const form = reactive({ ...props.filters });
const applyFilters = () => router.get('/admin/rooms/homes', form, { preserveState: true, replace: true });
const clearFilters = () => {
    Object.assign(form, { search: '', status: '', sort: 'latest' });
    applyFilters();
};
</script>

<template>
    <AdminLayout title="Domki graczy">
        <RoomsSubnav />

        <header class="panfu-admin-page-header">
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Pokoje prywatne</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Domki graczy</h1>
            <p class="mt-2 text-sm text-slate-500">Wariant domku i meble są odczytywane z ekwipunku gracza wraz z pozycją, obrotem i numerem pomieszczenia.</p>
        </header>

        <AdminCard class="mb-4" title="Filtry" description="Znajdź domek po pandzie albo stanie wyposażenia">
            <form class="grid gap-3 md:grid-cols-[minmax(220px,2fr)_1fr_1fr_auto]" @submit.prevent="applyFilters">
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Panda</span>
                    <input v-model="form.search" class="w-full rounded-xl border-slate-300 text-sm" type="search" placeholder="Nazwa, e-mail lub ID" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Wyposażenie</span>
                    <select v-model="form.status" class="w-full rounded-xl border-slate-300 text-sm">
                        <option value="">Dowolne</option>
                        <option value="furnished">Ma meble</option>
                        <option value="placed">Ma ustawione meble</option>
                        <option value="empty">Brak mebli</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Sortowanie</span>
                    <select v-model="form.sort" class="w-full rounded-xl border-slate-300 text-sm">
                        <option value="latest">Najnowsi</option>
                        <option value="name">Nazwa A–Z</option>
                        <option value="furniture">Najwięcej mebli</option>
                    </select>
                </label>
                <div class="flex items-end gap-2">
                    <button class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white" type="submit">Filtruj</button>
                    <button class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-medium text-slate-600" type="button" @click="clearFilters">Wyczyść</button>
                </div>
            </form>
        </AdminCard>

        <AdminCard :padded="false">
            <div v-if="homes.data.length" class="divide-y divide-slate-100">
                <Link v-for="home in homes.data" :key="home.userId" :href="`/admin/rooms/homes/${home.userId}`" class="panfu-home-list-row">
                    <div class="panfu-home-list-row__avatar">{{ home.name.charAt(0).toUpperCase() }}</div>
                    <div class="min-w-0 flex-1">
                        <strong>{{ home.name }} <span>#{{ home.userId }}</span></strong>
                        <p>{{ home.email }}</p>
                    </div>
                    <div class="panfu-home-list-row__background">
                        <span>Wariant domku</span>
                        <strong>{{ home.backgroundName }}</strong>
                        <small>#{{ home.backgroundId }}</small>
                    </div>
                    <div class="panfu-home-list-row__badges">
                        <AdminBadge tone="blue">{{ home.furnitureCount }} mebli</AdminBadge>
                        <AdminBadge :tone="home.placedFurnitureCount ? 'green' : 'slate'">{{ home.placedFurnitureCount }} ustawionych</AdminBadge>
                    </div>
                    <span class="panfu-home-list-row__action">Otwórz →</span>
                </Link>
            </div>
            <AdminEmptyState v-else title="Brak domków" description="Nie ma graczy pasujących do wybranych filtrów.">
                <template #icon>⌂</template>
            </AdminEmptyState>
            <AdminPagination :pagination="homes" />
        </AdminCard>
    </AdminLayout>
</template>
