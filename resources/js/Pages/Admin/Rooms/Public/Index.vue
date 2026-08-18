<script setup lang="ts">
import AdminBadge from '@/Components/Admin/AdminBadge.vue';
import AdminCard from '@/Components/Admin/AdminCard.vue';
import AdminEmptyState from '@/Components/Admin/AdminEmptyState.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import RoomsSubnav from '@/Components/Admin/Rooms/RoomsSubnav.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Paginated, PublicRoomSummary } from '@/types/admin';
import { Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

interface Filters { search: string; status: string; sort: string }

const props = defineProps<{ rooms: Paginated<PublicRoomSummary>; filters: Filters }>();
const form = reactive({ ...props.filters });
const applyFilters = () => router.get('/admin/rooms/public', form, { preserveState: true, replace: true });
const clearFilters = () => {
    Object.assign(form, { search: '', status: '', sort: 'number' });
    applyFilters();
};
const size = (bytes: number | null) => bytes === null ? '—' : `${(bytes / 1024 / 1024).toFixed(1)} MB`;
</script>

<template>
    <AdminLayout title="Pokoje publiczne">
        <RoomsSubnav />

        <header class="panfu-admin-page-header">
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Świat gry</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Pokoje publiczne</h1>
            <p class="mt-2 text-sm text-slate-500">Katalog z głównego config.xml, pliki pokoi i narzędzia do analizy walkarea, spawnów oraz elementów interaktywnych.</p>
        </header>

        <AdminCard class="mb-4" title="Filtry" description="Przeszukuj konfigurację świata gry">
            <form class="grid gap-3 md:grid-cols-[minmax(220px,2fr)_1fr_1fr_auto]" @submit.prevent="applyFilters">
                <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Pokój</span><input v-model="form.search" class="w-full rounded-xl border-slate-300 text-sm" type="search" placeholder="ID, klucz lub numer" /></label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Właściwość</span><select v-model="form.status" class="w-full rounded-xl border-slate-300 text-sm"><option value="">Dowolna</option><option value="allowed">Dostępny</option><option value="disabled">Wyłączony</option><option value="collision">Ograniczony do walkarea</option><option value="vehicle">Pojazdy</option><option value="missing">Brak SWF</option></select></label>
                <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Sortowanie</span><select v-model="form.sort" class="w-full rounded-xl border-slate-300 text-sm"><option value="number">Numer pokoju</option><option value="name">Nazwa A–Z</option></select></label>
                <div class="flex items-end gap-2"><button class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white" type="submit">Filtruj</button><button class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-medium text-slate-600" type="button" @click="clearFilters">Wyczyść</button></div>
            </form>
        </AdminCard>

        <AdminCard :padded="false">
            <div v-if="rooms.data.length" class="panfu-public-room-list">
                <Link v-for="room in rooms.data" :key="room.id" :href="`/admin/rooms/public/${room.id}`" class="panfu-public-room-row">
                    <div class="panfu-public-room-row__number">{{ room.number }}</div>
                    <div class="min-w-0 flex-1"><strong>{{ room.label }}</strong><p><code>{{ room.id }}</code> · {{ room.key }}</p></div>
                    <div class="panfu-public-room-row__flags"><AdminBadge :tone="room.allowed ? 'green' : 'red'">{{ room.allowed ? 'Dostępny' : 'Wyłączony' }}</AdminBadge><AdminBadge v-if="room.restrictToWalkArea" tone="blue">Walkarea</AdminBadge><AdminBadge v-if="room.vehicleArea" tone="amber">Pojazdy</AdminBadge></div>
                    <div class="panfu-public-room-row__files"><span>SWF {{ size(room.assetSize) }}</span><AdminBadge :tone="room.assetExists && room.configExists ? 'green' : 'red'">{{ room.assetExists && room.configExists ? 'Pliki OK' : 'Braki' }}</AdminBadge></div>
                    <span class="panfu-public-room-row__action">Debuguj →</span>
                </Link>
            </div>
            <AdminEmptyState v-else title="Brak pokojów" description="Żaden pokój nie pasuje do wybranych filtrów."><template #icon>⌗</template></AdminEmptyState>
            <AdminPagination :pagination="rooms" />
        </AdminCard>
    </AdminLayout>
</template>
