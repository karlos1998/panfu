<script setup lang="ts">
import AdminEmptyState from '@/Components/Admin/AdminEmptyState.vue';
import InputError from '@/Components/InputError.vue';
import type { PlayerState } from '@/types/admin';
import { useForm } from '@inertiajs/vue3';
import StateRow from './StateRow.vue';

const props = defineProps<{ userId: number; states: PlayerState[] }>();
const form = useForm({ category: 0, name: 0, value: 0 });
const add = () => form.post(`/admin/users/${props.userId}/states`, { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
    <div class="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
        <aside>
            <h3 class="text-sm font-semibold text-slate-900">Dodaj stan gry</h3>
            <p class="mt-1 text-sm text-slate-500">Stany przechowują postęp zadań, osiągnięć i aktywności klienta.</p>
            <form class="mt-5 space-y-4 rounded-xl bg-slate-50 p-4" @submit.prevent="add">
                <label class="block text-sm font-medium text-slate-700">Kategoria<input v-model.number="form.category" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" type="number" min="0" required /><InputError class="mt-1" :message="form.errors.category" /></label>
                <label class="block text-sm font-medium text-slate-700">Nazwa / ID<input v-model.number="form.name" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" type="number" min="0" required /><InputError class="mt-1" :message="form.errors.name" /></label>
                <label class="block text-sm font-medium text-slate-700">Wartość<input v-model.number="form.value" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm" type="number" required /><InputError class="mt-1" :message="form.errors.value" /></label>
                <button class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700" type="submit" :disabled="form.processing">Zapisz stan</button>
            </form>
        </aside>
        <section>
            <h3 class="text-sm font-semibold text-slate-900">Postęp i osiągnięcia ({{ states.length }})</h3>
            <p class="mt-1 text-sm text-slate-500">Edycja ma natychmiastowy wpływ na dane zwracane klientowi gry.</p>
            <div v-if="states.length" class="mt-5 space-y-3">
                <StateRow v-for="state in states" :key="state.id" :user-id="userId" :state="state" />
            </div>
            <AdminEmptyState v-else title="Brak zapisanych stanów" description="Ta panda nie rozpoczęła jeszcze aktywności zapisujących postęp." />
        </section>
    </div>
</template>
