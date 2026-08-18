<script setup lang="ts">
import AdminBadge from '@/Components/Admin/AdminBadge.vue';
import AdminEmptyState from '@/Components/Admin/AdminEmptyState.vue';
import type { HomeFurniture, RoomClient } from '@/types/admin';
import { computed, ref } from 'vue';
import FlashAssetPreview from './FlashAssetPreview.vue';

const props = defineProps<{ furniture: HomeFurniture[]; client: RoomClient }>();

const search = ref('');
const room = ref<string>('');
const placement = ref('');
const selected = ref<HomeFurniture | null>(null);
const rooms = computed(() => [...new Set(props.furniture.map((item) => item.room))].sort((a, b) => a - b));
const filtered = computed(() => props.furniture.filter((item) => {
    const matchesSearch = search.value === '' || `${item.name} ${item.itemId}`.toLowerCase().includes(search.value.toLowerCase());
    const matchesRoom = room.value === '' || item.room === Number(room.value);
    const matchesPlacement = placement.value === '' || (placement.value === 'placed' ? item.placed : !item.placed);
    return matchesSearch && matchesRoom && matchesPlacement;
}));
</script>

<template>
    <div class="panfu-home-furniture">
        <div class="panfu-room-toolbar">
            <label>
                Szukaj
                <input v-model="search" type="search" placeholder="Nazwa lub ID" />
            </label>
            <label>
                Pokój
                <select v-model="room">
                    <option value="">Wszystkie</option>
                    <option v-for="roomNumber in rooms" :key="roomNumber" :value="roomNumber">Pokój {{ roomNumber }}</option>
                </select>
            </label>
            <label>
                Stan
                <select v-model="placement">
                    <option value="">Wszystkie</option>
                    <option value="placed">Ustawione</option>
                    <option value="stored">W schowku</option>
                </select>
            </label>
        </div>

        <div v-if="filtered.length" class="panfu-home-furniture__grid">
            <button
                v-for="item in filtered"
                :key="item.inventoryId"
                type="button"
                class="panfu-home-furniture-card"
                :class="{ 'panfu-home-furniture-card--active': selected?.inventoryId === item.inventoryId }"
                @click="selected = item"
            >
                <span class="panfu-home-furniture-card__id">#{{ item.itemId }}</span>
                <strong>{{ item.name }}</strong>
                <span>Pokój {{ item.room }} · x {{ item.x }}, y {{ item.y }} · obrót {{ item.rotation }}</span>
                <span class="flex flex-wrap gap-1.5">
                    <AdminBadge :tone="item.placed ? 'green' : 'slate'">{{ item.placed ? 'Ustawiony' : 'Schowany' }}</AdminBadge>
                    <AdminBadge v-if="item.premium" tone="amber">Premium</AdminBadge>
                    <AdminBadge v-if="!item.modelUrl" tone="red">Brak modelu SWF</AdminBadge>
                </span>
            </button>
        </div>
        <AdminEmptyState v-else title="Brak pasujących mebli" description="Zmień filtry albo sprawdź ekwipunek użytkownika.">
            <template #icon>▣</template>
        </AdminEmptyState>

        <aside v-if="selected" class="panfu-home-furniture-preview">
            <div>
                <span class="panfu-home-furniture-preview__eyebrow">Podgląd SWF</span>
                <h3>{{ selected.name }} <small>#{{ selected.itemId }}</small></h3>
                <p>Model mebla jest wyświetlany osobno; marker na scenie pokazuje jego zapisaną pozycję w domku.</p>
            </div>
            <div class="panfu-home-furniture-preview__stage">
                <FlashAssetPreview
                    v-if="selected.modelUrl"
                    :key="selected.modelUrl"
                    :url="selected.modelUrl"
                    :ruffle-script="client.ruffleScript"
                    base-url="/vendor/openpanfu/rooms/home/"
                    :label="`Mebel ${selected.name}`"
                />
                <span v-else>Brak pliku modelu.</span>
            </div>
            <button type="button" class="panfu-home-furniture-preview__close" @click="selected = null">Zamknij</button>
        </aside>
    </div>
</template>
