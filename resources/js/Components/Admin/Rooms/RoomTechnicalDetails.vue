<script setup lang="ts">
import AdminBadge from '@/Components/Admin/AdminBadge.vue';
import AdminEmptyState from '@/Components/Admin/AdminEmptyState.vue';
import type { PublicRoomDetails } from '@/types/admin';
import { ref } from 'vue';

defineProps<{ room: PublicRoomDetails }>();
const tab = ref<'elements' | 'assets' | 'sounds' | 'dates'>('elements');
</script>

<template>
    <div>
        <div class="panfu-admin-tabs mb-4 flex flex-wrap gap-1">
            <button type="button" class="panfu-admin-tab" :class="{ 'panfu-admin-tab--active': tab === 'elements' }" @click="tab = 'elements'">Elementy <span class="panfu-admin-tab__count">{{ room.elements.length }}</span></button>
            <button type="button" class="panfu-admin-tab" :class="{ 'panfu-admin-tab--active': tab === 'assets' }" @click="tab = 'assets'">Zasoby <span class="panfu-admin-tab__count">{{ room.assets.length }}</span></button>
            <button type="button" class="panfu-admin-tab" :class="{ 'panfu-admin-tab--active': tab === 'sounds' }" @click="tab = 'sounds'">Dźwięki <span class="panfu-admin-tab__count">{{ room.sounds.length }}</span></button>
            <button type="button" class="panfu-admin-tab" :class="{ 'panfu-admin-tab--active': tab === 'dates' }" @click="tab = 'dates'">Daty <span class="panfu-admin-tab__count">{{ room.dates.length }}</span></button>
        </div>

        <div v-if="tab === 'elements' && room.elements.length" class="panfu-room-data-grid">
            <article v-for="element in room.elements" :key="`${element.id}-${element.type}`">
                <div class="flex items-start justify-between gap-2">
                    <strong>{{ element.id || '(bez nazwy)' }}</strong>
                    <div class="flex gap-1">
                        <AdminBadge v-if="element.button" tone="blue">przycisk</AdminBadge>
                        <AdminBadge :tone="element.visible ? 'green' : 'slate'">{{ element.visible ? 'widoczny' : 'ukryty' }}</AdminBadge>
                    </div>
                </div>
                <code v-if="element.type">type={{ element.type }}</code>
                <p v-if="element.messages.length">{{ element.messages.join(', ') }}</p>
            </article>
        </div>
        <div v-else-if="tab === 'assets' && room.assets.length" class="overflow-x-auto">
            <table class="panfu-room-data-table">
                <thead><tr><th>ID</th><th>Ścieżka</th><th>Preload</th><th>Plik</th></tr></thead>
                <tbody><tr v-for="asset in room.assets" :key="`${asset.id}-${asset.path}`"><td>{{ asset.id }}</td><td><code>{{ asset.path }}</code></td><td>{{ asset.preload ? 'tak' : 'nie' }}</td><td><AdminBadge :tone="asset.exists ? 'green' : asset.path.includes('$$') ? 'slate' : 'red'">{{ asset.exists ? 'jest' : asset.path.includes('$$') ? 'szablon' : 'brak' }}</AdminBadge></td></tr></tbody>
            </table>
        </div>
        <div v-else-if="tab === 'sounds' && room.sounds.length" class="overflow-x-auto">
            <table class="panfu-room-data-table">
                <thead><tr><th>ID</th><th>Ścieżka</th><th>Głośność</th><th>Pętle</th><th>Plik</th></tr></thead>
                <tbody><tr v-for="sound in room.sounds" :key="`${sound.id}-${sound.path}`"><td>{{ sound.id }}</td><td><code>{{ sound.path }}</code></td><td>{{ sound.volume ?? '—' }}</td><td>{{ sound.loops ?? '—' }}</td><td><AdminBadge :tone="sound.exists ? 'green' : sound.path.includes('$$') ? 'slate' : 'red'">{{ sound.exists ? 'jest' : sound.path.includes('$$') ? 'szablon' : 'brak' }}</AdminBadge></td></tr></tbody>
            </table>
        </div>
        <div v-else-if="tab === 'dates' && room.dates.length" class="panfu-room-data-grid">
            <article v-for="date in room.dates" :key="date.id">
                <strong>{{ date.id }}</strong>
                <p>{{ date.start || 'bez początku' }} → {{ date.finish || 'bez końca' }}</p>
            </article>
        </div>
        <AdminEmptyState v-else title="Brak danych" description="Konfiguracja pokoju nie zawiera wpisów w tej kategorii.">
            <template #icon>∅</template>
        </AdminEmptyState>
    </div>
</template>
