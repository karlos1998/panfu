<script setup lang="ts">
import AdminBadge from '@/Components/Admin/AdminBadge.vue';
import AdminEmptyState from '@/Components/Admin/AdminEmptyState.vue';
import InputError from '@/Components/InputError.vue';
import type { SelectOption, UserOption, UserRelation } from '@/types/admin';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    userId: number;
    relations: UserRelation[];
    users: UserOption[];
    relationTypes: SelectOption<number>[];
}>();

const filter = ref<'all' | 'friends' | 'blocked'>('all');
const form = useForm({ related_user_id: null as number | null, type: 1 });
const relatedIds = computed(() => new Set(props.relations.map((relation) => relation.userId)));
const availableUsers = computed(() => props.users.filter((user) => !relatedIds.value.has(user.id)));
const visibleRelations = computed(() => props.relations.filter((relation) => {
    if (filter.value === 'friends') return relation.type === 1;
    if (filter.value === 'blocked') return relation.type === 2;
    return true;
}));

const add = () => form.post(`/admin/users/${props.userId}/relations`, { preserveScroll: true, onSuccess: () => form.reset() });
const remove = (relation: UserRelation) => {
    if (window.confirm(`Usunąć relację z pandą „${relation.name}”?`)) {
        router.delete(`/admin/users/${props.userId}/relations/${relation.id}`, { preserveScroll: true });
    }
};
</script>

<template>
    <div class="grid gap-6 xl:grid-cols-[340px_minmax(0,1fr)]">
        <aside>
            <h3 class="text-sm font-semibold text-slate-900">Dodaj relację</h3>
            <p class="mt-1 text-sm text-slate-500">Znajomość jest zapisywana dwukierunkowo; blokada dotyczy tylko tej pandy.</p>
            <form class="mt-5 space-y-4 rounded-xl bg-slate-50 p-4" @submit.prevent="add">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">Druga panda</span>
                    <select v-model="form.related_user_id" class="w-full rounded-xl border-slate-300 text-sm" required>
                        <option :value="null" disabled>Wybierz użytkownika…</option>
                        <option v-for="user in availableUsers" :key="user.id" :value="user.id">{{ user.name }} · {{ user.email }}</option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.related_user_id" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">Typ relacji</span>
                    <select v-model="form.type" class="w-full rounded-xl border-slate-300 text-sm">
                        <option v-for="type in relationTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.type" />
                </label>
                <button class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60" type="submit" :disabled="form.processing || availableUsers.length === 0">Dodaj relację</button>
            </form>
        </aside>
        <section>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Relacje ({{ relations.length }})</h3>
                    <p class="mt-1 text-sm text-slate-500">Lista znajomych i zablokowanych pand.</p>
                </div>
                <div class="flex rounded-lg bg-slate-100 p-1 text-xs font-semibold">
                    <button v-for="option in [{ value: 'all', label: 'Wszystkie' }, { value: 'friends', label: 'Znajomi' }, { value: 'blocked', label: 'Blokady' }]" :key="option.value" class="rounded-md px-3 py-1.5" :class="filter === option.value ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" type="button" @click="filter = option.value as typeof filter">{{ option.label }}</button>
                </div>
            </div>
            <div v-if="visibleRelations.length" class="mt-5 divide-y divide-slate-100 rounded-xl border border-slate-200">
                <div v-for="relation in visibleRelations" :key="relation.id" class="flex items-center gap-3 p-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 font-bold text-blue-700">{{ relation.name.charAt(0).toUpperCase() }}</div>
                    <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-slate-900">{{ relation.name }}</p><p class="truncate text-xs text-slate-500">{{ relation.email ?? `Panda #${relation.userId}` }}</p></div>
                    <AdminBadge :tone="relation.type === 1 ? 'green' : 'red'">{{ relation.typeLabel }}</AdminBadge>
                    <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50" type="button" @click="remove(relation)">Usuń</button>
                </div>
            </div>
            <AdminEmptyState v-else title="Brak relacji" description="Ta panda nie ma relacji w wybranej kategorii." />
        </section>
    </div>
</template>
