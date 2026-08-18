<script setup lang="ts">
import AdminBadge from '@/Components/Admin/AdminBadge.vue';
import AdminCard from '@/Components/Admin/AdminCard.vue';
import RoomDebugStage from '@/Components/Admin/Rooms/RoomDebugStage.vue';
import RoomsSubnav from '@/Components/Admin/Rooms/RoomsSubnav.vue';
import RoomTechnicalDetails from '@/Components/Admin/Rooms/RoomTechnicalDetails.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { PublicRoomDetails, RoomClient } from '@/types/admin';
import { Link } from '@inertiajs/vue3';

defineProps<{ room: PublicRoomDetails; client: RoomClient }>();
const size = (bytes: number | null) => bytes === null ? '—' : `${(bytes / 1024 / 1024).toFixed(2)} MB`;
</script>

<template>
    <AdminLayout :title="`Pokój ${room.label}`">
        <RoomsSubnav />
        <Link href="/admin/rooms/public" class="panfu-admin-back-link mb-4 inline-flex items-center gap-2">← Wróć do pokojów publicznych</Link>

        <header class="panfu-admin-page-header panfu-public-room-header">
            <div class="panfu-public-room-header__number">{{ room.number }}</div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Pokój publiczny</p>
                <h1>{{ room.label }}</h1>
                <p><code>{{ room.id }}</code> · {{ room.key }}</p>
            </div>
            <div class="panfu-public-room-header__flags">
                <AdminBadge :tone="room.allowed ? 'green' : 'red'">{{ room.allowed ? 'Dostępny' : 'Wyłączony' }}</AdminBadge>
                <AdminBadge :tone="room.restrictToWalkArea ? 'blue' : 'slate'">{{ room.restrictToWalkArea ? 'Granica walkarea aktywna' : 'Wyjście poza walkarea dozwolone' }}</AdminBadge>
                <AdminBadge v-if="room.vehicleArea" tone="amber">Pojazdy</AdminBadge>
            </div>
        </header>

        <AdminCard class="mb-4" title="Debugger sceny" description="SWF pokoju z warstwami diagnostycznymi w układzie współrzędnych 772 × 480">
            <RoomDebugStage :room="room" :client="client" />
        </AdminCard>

        <div class="panfu-room-metrics">
            <article><span>Plik SWF</span><strong>{{ size(room.assetSize) }}</strong><small>{{ room.assetExists ? 'dostępny' : 'brak pliku' }}</small></article>
            <article><span>Walkarea</span><strong>{{ room.debug.walkAreaFrames.length }}</strong><small>unikalnych wariantów · symbol {{ room.debug.walkAreaCharacterId ?? '—' }}</small></article>
            <article><span>Spawny</span><strong>{{ room.spawns.length }}</strong><small>z głównego config.xml</small></article>
            <article><span>Hotspoty</span><strong>{{ room.hotspots.length }}</strong><small>stref z XML pokoju</small></article>
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <AdminCard title="Punkty wejścia" description="Pozycje i promienie spawnów z różnych pokojów">
                <div v-if="room.spawns.length" class="panfu-room-coordinate-list">
                    <article v-for="spawn in room.spawns" :key="`${spawn.from}-${spawn.x}-${spawn.y}`"><strong>z {{ spawn.from }}</strong><code>x {{ spawn.x }} · y {{ spawn.y }}</code><span>promień {{ spawn.radiusX }} × {{ spawn.radiusY }} · obrót {{ spawn.rotation ?? '—' }}</span></article>
                </div>
                <p v-else class="text-sm text-slate-500">Brak zdefiniowanych spawnów.</p>
            </AdminCard>
            <AdminCard title="Hotspoty i akcje" description="Dokładne środki, promienie i cele z sekcji module.hotspots XML pokoju">
                <div class="panfu-room-coordinate-list">
                    <article v-for="hotspot in room.hotspots" :key="`${hotspot.id}-${hotspot.target}-${hotspot.x}-${hotspot.y}`">
                        <strong>{{ hotspot.id }}</strong>
                        <code>x {{ hotspot.x }} · y {{ hotspot.y }} · r {{ hotspot.radius }}</code>
                        <span>
                            {{ hotspot.type }} →
                            <Link v-if="hotspot.destination" :href="`/admin/rooms/public/${hotspot.destination.id}`">#{{ hotspot.destination.number }} {{ hotspot.destination.label }}</Link>
                            <template v-else>{{ hotspot.target || 'akcja lokalna' }}</template>
                            <template v-if="hotspot.angle !== null"> · kąt {{ hotspot.angle }}°</template>
                        </span>
                    </article>
                    <p v-if="!room.hotspots.length" class="text-sm text-slate-500">Brak hotspotów w konfiguracji pokoju.</p>
                </div>
            </AdminCard>
        </div>

        <AdminCard class="mt-4" title="Konfiguracja techniczna" description="Elementy, pliki, dźwięki i warianty czasowe pokoju">
            <RoomTechnicalDetails :room="room" />
        </AdminCard>
    </AdminLayout>
</template>
