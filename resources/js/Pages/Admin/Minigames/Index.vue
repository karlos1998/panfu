<script setup lang="ts">
import AdminBadge from '@/Components/Admin/AdminBadge.vue';
import AdminCard from '@/Components/Admin/AdminCard.vue';
import AdminMetricCard from '@/Components/Admin/AdminMetricCard.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { AdminMinigame } from '@/types/admin';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Metrics {
    total: number;
    enabled: number;
    customMultiplier: number;
}

const props = defineProps<{ minigames: AdminMinigame[]; metrics: Metrics }>();
const search = ref('');

const filteredMinigames = computed(() => {
    const query = search.value.trim().toLocaleLowerCase('pl-PL');

    if (query === '') {
        return props.minigames;
    }

    return props.minigames.filter((game) => {
        const rooms = game.rooms.map((room) => `${room.id} ${room.label}`).join(' ');

        return `${game.id} ${game.name} ${rooms}`.toLocaleLowerCase('pl-PL').includes(query);
    });
});

const exampleReward = (game: AdminMinigame) => Math.floor(100 * Number(game.coinMultiplier));
const typeLabel = (game: AdminMinigame) => game.type === 'multi'
    ? 'Wieloosobowa'
    : game.type === 'single'
        ? 'Jednoosobowa'
        : 'Poza katalogiem';
</script>

<template>
    <AdminLayout title="Minigry">
        <header class="panfu-admin-page-header mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Ekonomia gry</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Minigry</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-500">Podgląd katalogu minigier oraz obowiązujących przeliczników punktów na monety.</p>
            </div>
            <label class="block w-full lg:w-80">
                <span class="sr-only">Szukaj minigry</span>
                <input v-model="search" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" type="search" placeholder="Szukaj po nazwie, ID lub pokoju…" />
            </label>
        </header>

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <AdminMetricCard label="Wszystkie minigry" :value="metrics.total" detail="rekordów w tabeli nagród" tone="blue" icon="G" />
            <AdminMetricCard label="Aktywne wypłaty" :value="metrics.enabled" detail="gier przyznających monety" tone="emerald" icon="✓" />
            <AdminMetricCard label="Własny przelicznik" :value="metrics.customMultiplier" detail="odstępstwa od 0,0500" tone="amber" icon="×" />
        </div>

        <div v-if="filteredMinigames.length" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <AdminCard v-for="game in filteredMinigames" :key="game.id" :padded="false" class="overflow-hidden">
                <div class="aspect-[386/240] overflow-hidden bg-slate-100">
                    <img v-if="game.thumbnailUrl" :src="game.thumbnailUrl" :alt="`Miniatura gry ${game.name}`" class="h-full w-full object-cover" loading="lazy" />
                    <div v-else class="flex h-full items-center justify-center text-sm font-semibold text-slate-400">Brak miniatury</div>
                </div>

                <div class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Gra #{{ game.id }}</p>
                            <h2 class="mt-1 truncate text-lg font-bold text-slate-900">{{ game.name }}</h2>
                        </div>
                        <AdminBadge :tone="game.enabled ? 'green' : 'red'">{{ game.enabled ? 'Aktywna' : 'Wyłączona' }}</AdminBadge>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <AdminBadge tone="slate">{{ typeLabel(game) }}</AdminBadge>
                        <AdminBadge v-if="game.adapter" tone="blue">{{ game.adapter }}</AdminBadge>
                    </div>

                    <div class="mt-4">
                        <p class="text-xs text-slate-400">Dostępna w pokojach</p>
                        <div v-if="game.rooms.length" class="mt-2 flex flex-wrap gap-2">
                            <Link
                                v-for="room in game.rooms"
                                :key="room.id"
                                :href="`/admin/rooms/public/${room.id}`"
                                class="rounded-full transition hover:-translate-y-0.5 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                <AdminBadge :tone="room.allowed ? 'blue' : 'slate'">#{{ room.number }} {{ room.label }} →</AdminBadge>
                            </Link>
                        </div>
                        <p v-else class="mt-2 text-sm font-medium text-slate-400">Brak bezpośredniego przypisania</p>
                    </div>

                    <dl class="mt-5 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4 text-sm">
                        <div>
                            <dt class="text-xs text-slate-400">Mnożnik monet</dt>
                            <dd class="mt-1 font-mono font-bold text-slate-900">{{ game.coinMultiplier }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-400">Przykład</dt>
                            <dd class="mt-1 font-semibold text-slate-900">100 pkt → {{ exampleReward(game) }} monet</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-xs text-slate-400">Limit na rundę</dt>
                            <dd class="mt-1 font-semibold text-slate-700">{{ game.maxCoinsPerRound === null ? 'Globalny limit serwera' : `${game.maxCoinsPerRound} monet` }}</dd>
                        </div>
                    </dl>
                </div>
            </AdminCard>
        </div>

        <AdminCard v-else>
            <p class="py-8 text-center text-sm text-slate-500">Nie znaleziono minigry pasującej do wyszukiwania.</p>
        </AdminCard>
    </AdminLayout>
</template>
