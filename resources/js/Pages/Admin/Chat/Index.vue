<script setup lang="ts">
import AdminCard from '@/Components/Admin/AdminCard.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import ChatMessagesTable from '@/Components/Admin/ChatMessagesTable.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { AdminChatMessage, Paginated, SelectOption } from '@/types/admin';
import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';

interface Filters {
    nickname: string;
    room: string;
}

const props = defineProps<{
    messages: Paginated<AdminChatMessage>;
    filters: Filters;
    rooms: SelectOption[];
}>();

const form = reactive({ ...props.filters });

const applyFilters = () => {
    router.get('/admin/chat', form, { preserveState: true, replace: true });
};

const clearFilters = () => {
    Object.assign(form, { nickname: '', room: '' });
    applyFilters();
};
</script>

<template>
    <AdminLayout title="Historia czatu">
        <div class="panfu-admin-page-header mb-8">
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Moderacja</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Historia czatu</h1>
            <p class="mt-2 text-sm text-slate-500">Wiadomości wysłane w grze, od najnowszych do najstarszych.</p>
        </div>

        <AdminCard class="mb-6" title="Filtry" description="Zawęź historię do wybranej pandy lub pokoju">
            <form class="grid gap-4 lg:grid-cols-[minmax(240px,1fr)_minmax(260px,1fr)_auto]" @submit.prevent="applyFilters">
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Nick gracza</span>
                    <input v-model="form.nickname" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" type="search" placeholder="Np. KarolPanda" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Pokój</span>
                    <select v-model="form.room" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Wszystkie pokoje</option>
                        <option v-for="room in rooms" :key="room.value" :value="room.value">{{ room.label }}</option>
                    </select>
                </label>
                <div class="flex items-end gap-2">
                    <button class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700" type="submit">Filtruj</button>
                    <button class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50" type="button" @click="clearFilters">Wyczyść</button>
                </div>
            </form>
        </AdminCard>

        <AdminCard :padded="false">
            <ChatMessagesTable :messages="messages.data" />
            <AdminPagination :pagination="messages" />
        </AdminCard>
    </AdminLayout>
</template>
