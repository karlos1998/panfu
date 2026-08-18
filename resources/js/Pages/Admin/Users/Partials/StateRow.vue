<script setup lang="ts">
import type { PlayerState } from '@/types/admin';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps<{ userId: number; state: PlayerState }>();
const form = useForm({ category: props.state.category, name: props.state.name, value: props.state.value });

const save = () => form.patch(`/admin/users/${props.userId}/states/${props.state.id}`, { preserveScroll: true });
const remove = () => {
    if (window.confirm('Usunąć ten stan gry?')) {
        router.delete(`/admin/users/${props.userId}/states/${props.state.id}`, { preserveScroll: true });
    }
};
</script>

<template>
    <form class="grid items-end gap-3 rounded-xl border border-slate-200 p-4 sm:grid-cols-[1fr_1fr_1fr_auto]" @submit.prevent="save">
        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kategoria<input v-model.number="form.category" class="mt-1.5 w-full rounded-lg border-slate-300 text-sm" type="number" min="0" required /></label>
        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nazwa / ID<input v-model.number="form.name" class="mt-1.5 w-full rounded-lg border-slate-300 text-sm" type="number" min="0" required /></label>
        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Wartość<input v-model.number="form.value" class="mt-1.5 w-full rounded-lg border-slate-300 text-sm" type="number" required /></label>
        <div class="flex gap-2">
            <button class="rounded-lg bg-blue-600 px-3 py-2.5 text-xs font-semibold text-white hover:bg-blue-700" type="submit" :disabled="form.processing">Zapisz</button>
            <button class="rounded-lg border border-red-200 px-3 py-2.5 text-xs font-semibold text-red-600 hover:bg-red-50" type="button" @click="remove">Usuń</button>
        </div>
    </form>
</template>
