<script setup lang="ts">
import type { PublicRoomDetails, RoomClient, RoomDebugFrame } from '@/types/admin';
import { computed, ref } from 'vue';
import FlashAssetPreview from './FlashAssetPreview.vue';

const props = defineProps<{ room: PublicRoomDetails; client: RoomClient }>();

const showWalkArea = ref(true);
const showBlocked = ref(true);
const showGrid = ref(true);
const showSpawns = ref(true);
const showHotspots = ref(true);
const selectedFrame = ref(0);
const cursor = ref<{ x: number; y: number } | null>(null);

const frame = computed<RoomDebugFrame | null>(() => props.room.debug.walkAreaFrames[selectedFrame.value] ?? null);
const frameTransform = computed(() => {
    const transform = frame.value?.transform;
    return transform ? `matrix(${transform.a} ${transform.b} ${transform.c} ${transform.d} ${transform.tx} ${transform.ty})` : undefined;
});
const hotspotLabel = (id: string, target: string, destination: { number: number } | null) =>
    id.startsWith('hotspot-') ? (destination ? `→ ${destination.number}` : target) : id;
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
            <label class="panfu-room-toggle"><input v-model="showBlocked" type="checkbox" /> <span class="panfu-room-legend panfu-room-legend--blocked" /> Tło poza walkarea</label>
            <label class="panfu-room-toggle"><input v-model="showSpawns" type="checkbox" /> <span class="panfu-room-legend panfu-room-legend--spawn" /> Spawny</label>
            <label class="panfu-room-toggle"><input v-model="showHotspots" type="checkbox" /> <span class="panfu-room-legend panfu-room-legend--hotspot" /> Hotspoty</label>
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
            <svg v-if="showWalkArea && frame" class="panfu-room-stage__walkarea" :viewBox="`0 0 ${client.stageWidth} ${client.stageHeight}`" aria-hidden="true">
                <image :href="frame.url" :x="frame.x" :y="frame.y" :width="frame.width" :height="frame.height" :transform="frameTransform" />
            </svg>
            <div v-if="showGrid" class="panfu-room-stage__grid" />

            <svg v-if="showSpawns" class="panfu-room-stage__zones panfu-room-stage__zones--spawns" :viewBox="`0 0 ${client.stageWidth} ${client.stageHeight}`" aria-label="Strefy spawnów z głównego config.xml">
                <g v-for="spawn in room.spawns" :key="`${spawn.from}-${spawn.x}-${spawn.y}`">
                    <title>{{ `Spawn z ${spawn.from}: x ${spawn.x}, y ${spawn.y}, promień ${spawn.radiusX} × ${spawn.radiusY}` }}</title>
                    <ellipse :cx="spawn.x" :cy="spawn.y" :rx="spawn.radiusX || 7" :ry="spawn.radiusY || 7" />
                    <text :x="spawn.x" :y="spawn.y + 3">S</text>
                </g>
            </svg>
            <svg v-if="showHotspots" class="panfu-room-stage__zones panfu-room-stage__zones--hotspots" :viewBox="`0 0 ${client.stageWidth} ${client.stageHeight}`" aria-label="Hotspoty z konfiguracji pokoju">
                <g v-for="hotspot in room.hotspots" :key="`${hotspot.id}-${hotspot.target}-${hotspot.x}-${hotspot.y}`" :class="`panfu-room-hotspot--${hotspot.type}`">
                    <title>{{ `${hotspot.id}: ${hotspot.type} → ${hotspot.destination?.label ?? hotspot.target}; x ${hotspot.x}, y ${hotspot.y}, r ${hotspot.radius}` }}</title>
                    <circle :cx="hotspot.x" :cy="hotspot.y" :r="hotspot.radius || 7" />
                    <text :x="hotspot.x" :y="hotspot.y + 3">{{ hotspotLabel(hotspot.id, hotspot.target, hotspot.destination) }}</text>
                </g>
            </svg>
            <div class="panfu-room-stage__inspector" />
        </div>

        <p class="panfu-room-debug-source">
            <strong>Źródła:</strong> zielona geometria — symbol <code>walkarea</code> ze SWF; pomarańczowe elipsy — spawny z głównego <code>conf/config.xml</code>; niebieskie i fioletowe okręgi — hotspoty z XML tego pokoju.
        </p>

        <p v-if="!frame" class="panfu-room-debug-warning">
            Ten SWF nie ma wygenerowanej warstwy walkarea. Podgląd, spawny i metadane nadal są dostępne.
        </p>
    </div>
</template>
