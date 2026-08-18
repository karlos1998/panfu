<script setup lang="ts">
import AdminEmptyState from '@/Components/Admin/AdminEmptyState.vue';
import InputError from '@/Components/InputError.vue';
import type { InventoryEntry, ItemOption } from '@/types/admin';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InventoryRow from './InventoryRow.vue';

const props = defineProps<{ userId: number; inventory: InventoryEntry[]; items: ItemOption[] }>();
const search = ref('');
const form = useForm({ item_id: null as number | null, active: false, bought: true, x: 0, y: 0, rot: 0, room: 0 });

const availableItems = computed(() => {
    const owned = new Set(props.inventory.map((entry) => entry.itemId));
    return props.items.filter((item) => !owned.has(item.id));
});

const filteredInventory = computed(() => {
    const query = search.value.trim().toLocaleLowerCase('pl');
    return query === '' ? props.inventory : props.inventory.filter((entry) =>
        entry.name.toLocaleLowerCase('pl').includes(query) || String(entry.itemId).includes(query),
    );
});

const add = () => form.post(`/admin/users/${props.userId}/inventory`, {
    preserveScroll: true,
    onSuccess: () => form.reset(),
});
</script>

<template>
    <div class="grid gap-6 xl:grid-cols-[minmax(280px,360px)_minmax(0,1fr)]">
        <aside>
            <h3 class="text-sm font-semibold text-slate-900">Dodaj przedmiot</h3>
            <p class="mt-1 text-sm text-slate-500">Wybierz element katalogu i ustaw jego stan początkowy.</p>
            <form class="mt-5 space-y-4 rounded-xl bg-slate-50 p-4" @submit.prevent="add">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">Przedmiot</span>
                    <select v-model="form.item_id" class="w-full rounded-xl border-slate-300 text-sm" required>
                        <option :value="null" disabled>Wybierz z katalogu…</option>
                        <option v-for="item in availableItems" :key="item.id" :value="item.id">#{{ item.id }} · {{ item.name ?? 'Bez nazwy' }}</option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.item_id" />
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="text-xs font-medium text-slate-500">X<input v-model.number="form.x" class="mt-1 w-full rounded-lg border-slate-300 text-sm" type="number" /></label>
                    <label class="text-xs font-medium text-slate-500">Y<input v-model.number="form.y" class="mt-1 w-full rounded-lg border-slate-300 text-sm" type="number" /></label>
                    <label class="text-xs font-medium text-slate-500">Obrót<input v-model.number="form.rot" class="mt-1 w-full rounded-lg border-slate-300 text-sm" type="number" /></label>
                    <label class="text-xs font-medium text-slate-500">Pokój<input v-model.number="form.room" class="mt-1 w-full rounded-lg border-slate-300 text-sm" type="number" min="0" /></label>
                </div>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input v-model="form.active" class="rounded text-blue-600" type="checkbox" /> Aktywny</label>
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input v-model="form.bought" class="rounded text-blue-600" type="checkbox" /> Kupiony</label>
                </div>
                <button class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60" type="submit" :disabled="form.processing || availableItems.length === 0">Dodaj do ekwipunku</button>
            </form>
        </aside>

        <section>
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Ekwipunek ({{ inventory.length }})</h3>
                    <p class="mt-1 text-sm text-slate-500">Ubrania, akcesoria i wyposażenie domu.</p>
                </div>
                <input v-model="search" class="rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" type="search" placeholder="Szukaj po nazwie lub ID" />
            </div>
            <div v-if="filteredInventory.length" class="grid gap-3 2xl:grid-cols-2">
                <InventoryRow v-for="entry in filteredInventory" :key="entry.id" :user-id="userId" :entry="entry" />
            </div>
            <AdminEmptyState v-else title="Brak przedmiotów" description="Ta panda nie ma pasujących przedmiotów w ekwipunku." />
        </section>
    </div>
</template>
