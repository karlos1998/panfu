<script setup lang="ts">
import AdminBadge from '@/Components/Admin/AdminBadge.vue';
import AdminCard from '@/Components/Admin/AdminCard.vue';
import HomeFurnitureList from '@/Components/Admin/Rooms/HomeFurnitureList.vue';
import HomePreviewStage from '@/Components/Admin/Rooms/HomePreviewStage.vue';
import RoomsSubnav from '@/Components/Admin/Rooms/RoomsSubnav.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { PlayerHomeDetails, RoomClient } from '@/types/admin';
import { Link } from '@inertiajs/vue3';

defineProps<{ home: PlayerHomeDetails; client: RoomClient }>();
</script>

<template>
    <AdminLayout :title="`Domek ${home.user.name}`">
        <RoomsSubnav />
        <Link href="/admin/rooms/homes" class="panfu-admin-back-link mb-4 inline-flex items-center gap-2">← Wróć do domków</Link>

        <header class="panfu-admin-page-header panfu-home-header">
            <div class="panfu-home-header__avatar">{{ home.user.name.charAt(0).toUpperCase() }}</div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Domek gracza</p>
                <h1>{{ home.user.name }} <span>#{{ home.user.id }}</span></h1>
                <p>{{ home.user.email }}</p>
            </div>
            <div class="panfu-home-header__metrics">
                <AdminBadge tone="blue">{{ home.furnitureCount }} mebli</AdminBadge>
                <AdminBadge tone="green">{{ home.placedFurnitureCount }} ustawionych</AdminBadge>
                <Link :href="`/admin/users/${home.user.id}`">Konto użytkownika →</Link>
            </div>
        </header>

        <AdminCard class="mb-4" title="Podgląd domku" :description="`${home.activeBackground.name} · markery pokazują zapisane pozycje mebli`">
            <HomePreviewStage :background="home.activeBackground" :furniture="home.furniture" :client="client" />
        </AdminCard>

        <AdminCard class="mb-4" title="Warianty domku" description="Przedmioty typu 0 należące do użytkownika">
            <div v-if="home.backgrounds.length" class="panfu-home-backgrounds">
                <article v-for="background in home.backgrounds" :key="background.itemId" :class="{ 'panfu-home-background--active': background.active }">
                    <span>#{{ background.itemId }}</span>
                    <strong>{{ background.name }}</strong>
                    <AdminBadge :tone="background.active ? 'green' : 'slate'">{{ background.active ? 'Aktywny' : 'Posiadany' }}</AdminBadge>
                    <small v-if="!background.swfUrl">Brak mapowania SWF</small>
                </article>
            </div>
            <p v-else class="text-sm text-slate-500">Gracz nie ma osobnego wariantu; klient używa domyślnego domku #100.</p>
        </AdminCard>

        <AdminCard title="Meble" description="Typy 13, 14, 17 i 50 z tabeli inventories">
            <HomeFurnitureList :furniture="home.furniture" :client="client" />
        </AdminCard>
    </AdminLayout>
</template>
