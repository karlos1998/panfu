<script setup lang="ts">
import type { HomeBackground, HomeFurniture, RoomClient } from '@/types/admin';
import { computed, ref } from 'vue';
import FlashAssetPreview from './FlashAssetPreview.vue';

const props = defineProps<{
    background: HomeBackground;
    furniture: HomeFurniture[];
    client: RoomClient;
}>();

const selectedRoom = ref(0);
const showStored = ref(false);
const roomFurniture = computed(() => props.furniture.filter((item) =>
    item.room === selectedRoom.value && (showStored.value || item.placed),
));
const roomNumbers = computed(() => [...new Set([0, ...props.furniture.map((item) => item.room)])].sort((a, b) => a - b));
const markerStyle = (item: HomeFurniture) => ({
    left: `${(item.x / props.client.stageWidth) * 100}%`,
    top: `${(item.y / props.client.stageHeight) * 100}%`,
});
</script>

<template>
    <div>
        <div class="panfu-room-toolbar">
            <label>
                Pomieszczenie
                <select v-model.number="selectedRoom">
                    <option v-for="room in roomNumbers" :key="room" :value="room">Pokój {{ room }}</option>
                </select>
            </label>
            <label class="panfu-room-toggle">
                <input v-model="showStored" type="checkbox" />
                Pokaż także schowane
            </label>
            <span class="panfu-room-toolbar__hint">Pozycje pochodzą z x/y zapisanych w inventories.</span>
        </div>

        <div class="panfu-room-stage" :style="{ aspectRatio: `${client.stageWidth} / ${client.stageHeight}` }">
            <FlashAssetPreview
                v-if="background.swfUrl"
                class="panfu-room-stage__flash"
                :url="background.swfUrl"
                :ruffle-script="client.ruffleScript"
                base-url="/vendor/openpanfu/rooms/home/"
                :label="`Domek: ${background.name}`"
            />
            <div v-else class="panfu-room-stage__missing">Brak pliku SWF dla tego wariantu domku.</div>

            <button
                v-for="item in roomFurniture"
                :key="item.inventoryId"
                type="button"
                class="panfu-home-marker"
                :class="{ 'panfu-home-marker--stored': !item.placed }"
                :style="markerStyle(item)"
                :title="`${item.name} · x ${item.x}, y ${item.y}, obrót ${item.rotation}`"
            >
                {{ item.itemId }}
            </button>
        </div>
    </div>
</template>
