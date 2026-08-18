<script setup lang="ts">
import AdminBadge from '@/Components/Admin/AdminBadge.vue';
import InputError from '@/Components/InputError.vue';
import type { InventoryEntry } from '@/types/admin';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{ userId: number; entry: InventoryEntry }>();
const editing = ref(false);
const form = useForm({
    active: props.entry.active,
    bought: props.entry.bought,
    x: props.entry.x,
    y: props.entry.y,
    rot: props.entry.rotation,
    room: props.entry.room,
});

const save = () => form.patch(`/admin/users/${props.userId}/inventory/${props.entry.id}`, {
    preserveScroll: true,
    onSuccess: () => { editing.value = false; },
});

const remove = () => {
    if (window.confirm(`Usunąć „${props.entry.name}” z ekwipunku?`)) {
        router.delete(`/admin/users/${props.userId}/inventory/${props.entry.id}`, { preserveScroll: true });
    }
};
</script>

<template>
    <article class="rounded-xl border border-slate-200 p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-sm font-bold text-violet-700">#{{ entry.itemId }}</div>
                <div class="min-w-0">
                    <h4 class="truncate text-sm font-semibold text-slate-900">{{ entry.name }}</h4>
                    <div class="mt-1 flex flex-wrap gap-1.5">
                        <AdminBadge v-if="entry.active" tone="green">Aktywny</AdminBadge>
                        <AdminBadge v-if="entry.premium" tone="amber">Premium</AdminBadge>
                        <AdminBadge>Typ {{ entry.type ?? '—' }}</AdminBadge>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50" type="button" @click="editing = !editing">{{ editing ? 'Anuluj' : 'Edytuj' }}</button>
                <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50" type="button" @click="remove">Usuń</button>
            </div>
        </div>

        <form v-if="editing" class="mt-4 border-t border-slate-100 pt-4" @submit.prevent="save">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <label class="text-xs font-medium text-slate-500">X<input v-model.number="form.x" class="mt-1 w-full rounded-lg border-slate-300 text-sm" type="number" /></label>
                <label class="text-xs font-medium text-slate-500">Y<input v-model.number="form.y" class="mt-1 w-full rounded-lg border-slate-300 text-sm" type="number" /></label>
                <label class="text-xs font-medium text-slate-500">Obrót<input v-model.number="form.rot" class="mt-1 w-full rounded-lg border-slate-300 text-sm" type="number" /></label>
                <label class="text-xs font-medium text-slate-500">Pokój<input v-model.number="form.room" class="mt-1 w-full rounded-lg border-slate-300 text-sm" type="number" min="0" /></label>
            </div>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input v-model="form.active" class="rounded text-blue-600" type="checkbox" /> Aktywny</label>
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input v-model="form.bought" class="rounded text-blue-600" type="checkbox" /> Kupiony</label>
                </div>
                <button class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700" type="submit" :disabled="form.processing">Zapisz</button>
            </div>
            <InputError class="mt-2" :message="form.errors.room" />
        </form>
    </article>
</template>
