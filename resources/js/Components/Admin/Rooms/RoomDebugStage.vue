<script setup lang="ts">
import type { PublicRoomDetails, RoomClient, RoomDebugFrame } from '@/types/admin';
import { computed, ref } from 'vue';
import FlashAssetPreview from './FlashAssetPreview.vue';

const props = defineProps<{ room: PublicRoomDetails; client: RoomClient }>();

const showWalkArea = ref(true);
const showBlocked = ref(true);
const showGrid = ref(true);
const showSpawns = ref(true);
const showMarkers = ref(true);
const selectedFrame = ref(0);
const cursor = ref<{ x: number; y: number } | null>(null);

const frame = computed<RoomDebugFrame | null>(() => props.room.debug.walkAreaFrames[selectedFrame.value] ?? null);
const frameStyle = computed(() => frame.value ? {
    left: `${(frame.value.x / props.client.stageWidth) * 100}%`,
    top: `${(frame.value.y / props.client.stageHeight) * 100}%`,
    width: `${(frame.value.width / props.client.stageWidth) * 100}%`,
    height: `${(frame.value.height / props.client.stageHeight) * 100}%`,
} : {});
const pointStyle = (x: number, y: number) => ({
    left: `${(x / props.client.stageWidth) * 100}%`,
    top: `${(y / props.client.stageHeight) * 100}%`,
});
const trackCursor = (event: MouseEvent) => {
    const element = event.currentTarget as HTMLElement;
    const bounds = element.getBoundingClientRect();
    cursor.value = {
        x: Math.max(0, Math.min(props.client.stageWidth, Math.round(((event.clientX - bounds.left) / bounds.width) * props.client.stageWidth))),
        y: Math.max(0, Math.min(props.client.stageHeight, Math.round(((event.clientY - bounds.top) / bounds.height) * props.client.stageHeight))),
    };
};
</script>

<template>
    <div>
        <div class="panfu-room-toolbar panfu-room-toolbar--debug">
            <label class="panfu-room-toggle"><input v-model="showWalkArea" type="checkbox" /> <span class="panfu-room-legend panfu-room-legend--walk" /> Walkarea</label>
            <label class="panfu-room-toggle"><input v-model="showBlocked" type="checkbox" /> <span class="panfu-room-legend panfu-room-legend--blocked" /> Poza walkarea</label>
            <label class="panfu-room-toggle"><input v-model="showSpawns" type="checkbox" /> <span class="panfu-room-legend panfu-room-legend--spawn" /> Spawny</label>
            <label class="panfu-room-toggle"><input v-model="showMarkers" type="checkbox" /> <span class="panfu-room-legend panfu-room-legend--marker" /> Elementy</label>
            <label class="panfu-room-toggle"><input v-model="showGrid" type="checkbox" /> Siatka</label>
            <label v-if="room.debug.walkAreaFrames.length > 1">
                Wariant
                <select v-model.number="selectedFrame">
                    <option v-for="(_, index) in room.debug.walkAreaFrames" :key="index" :value="index">{{ index + 1 }}</option>
                </select>
            </label>
            <output class="panfu-room-coordinates">{{ cursor ? `x ${cursor.x} · y ${cursor.y}` : 'Najedź na scenę' }}</output>
        </div>

        <div
            class="panfu-room-stage panfu-room-stage--debug"
            :style="{ aspectRatio: `${client.stageWidth} / ${client.stageHeight}` }"
            @mousemove="trackCursor"
            @mouseleave="cursor = null"
        >
            <FlashAssetPreview
                v-if="room.roomSwfUrl"
                class="panfu-room-stage__flash"
                :url="room.roomSwfUrl"
                :ruffle-script="client.ruffleScript"
                :base-url="`/vendor/openpanfu/rooms/${room.id}/`"
                :label="`Pokój ${room.label}`"
            />
            <div v-else class="panfu-room-stage__missing">Brak głównego pliku room.swf.</div>

            <div v-if="showBlocked && frame" class="panfu-room-stage__blocked" />
            <img v-if="showWalkArea && frame" :src="frame.url" alt="" class="panfu-room-stage__walkarea" :style="frameStyle" />
            <div v-if="showGrid" class="panfu-room-stage__grid" />

            <template v-if="showSpawns">
                <span
                    v-for="spawn in room.spawns"
                    :key="`${spawn.from}-${spawn.x}-${spawn.y}`"
                    class="panfu-room-point panfu-room-point--spawn"
                    :style="pointStyle(spawn.x, spawn.y)"
                    :title="`Spawn z ${spawn.from}: ${spawn.x}, ${spawn.y}`"
                >S</span>
            </template>
            <template v-if="showMarkers">
                <span
                    v-for="marker in room.debug.markers"
                    :key="`${marker.name}-${marker.characterId}`"
                    class="panfu-room-point panfu-room-point--marker"
                    :style="pointStyle(marker.x, marker.y)"
                    :title="`${marker.name} · symbol ${marker.characterId} · ${marker.x}, ${marker.y}`"
                >{{ marker.name }}</span>
            </template>
            <div class="panfu-room-stage__inspector" />
        </div>

        <p v-if="!frame" class="panfu-room-debug-warning">
            Ten SWF nie ma wygenerowanej warstwy walkarea. Podgląd, spawny i metadane nadal są dostępne.
        </p>
    </div>
</template>
