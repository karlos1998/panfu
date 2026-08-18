<script setup lang="ts">
import AdminBadge from '@/Components/Admin/AdminBadge.vue';
import AdminCard from '@/Components/Admin/AdminCard.vue';
import AdminEmptyState from '@/Components/Admin/AdminEmptyState.vue';
import AdminPagination from '@/Components/Admin/AdminPagination.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { AdminUserSummary, Paginated, SelectOption, UserRole } from '@/types/admin';
import { Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

interface Filters {
    search: string;
    role: string;
    status: string;
    sort: string;
}

const props = defineProps<{
    users: Paginated<AdminUserSummary>;
    filters: Filters;
    roles: SelectOption<UserRole>[];
}>();

const form = reactive({ ...props.filters });
const number = (value: number) => new Intl.NumberFormat('pl-PL').format(value);

const applyFilters = () => {
    router.get('/admin/users', form, { preserveState: true, replace: true });
};

const clearFilters = () => {
    Object.assign(form, { search: '', role: '', status: '', sort: 'latest' });
    applyFilters();
};
</script>

<template>
    <AdminLayout title="Użytkownicy">
        <div class="panfu-admin-page-header mb-8">
            <p class="text-sm font-semibold uppercase tracking-widest text-blue-600">Społeczność</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Użytkownicy</h1>
            <p class="mt-2 text-sm text-slate-500">Wyszukuj pandy i przechodź do pełnego zarządzania ich kontem.</p>
        </div>

        <AdminCard class="mb-6" title="Filtry" description="Szukaj po nazwie, e-mailu albo identyfikatorze">
            <form class="grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(240px,2fr)_1fr_1fr_1fr_auto]" @submit.prevent="applyFilters">
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Szukaj</span>
                    <input v-model="form.search" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" type="search" placeholder="Nazwa, e-mail lub ID" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Rola</span>
                    <select v-model="form.role" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Wszystkie</option>
                        <option v-for="roleOption in roles" :key="roleOption.value" :value="roleOption.value">{{ roleOption.label }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</span>
                    <select v-model="form.status" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Dowolny</option>
                        <option value="goldpanda">Gold Panda</option>
                        <option value="sheriff">Szeryf</option>
                        <option value="online">W grze</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Sortowanie</span>
                    <select v-model="form.sort" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="latest">Najnowsi</option>
                        <option value="oldest">Najstarsi</option>
                        <option value="name">Nazwa A–Z</option>
                        <option value="coins">Najwięcej monet</option>
                    </select>
                </label>
                <div class="flex items-end gap-2">
                    <button class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700" type="submit">Filtruj</button>
                    <button class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50" type="button" @click="clearFilters">Wyczyść</button>
                </div>
            </form>
        </AdminCard>

        <AdminCard :padded="false">
            <div v-if="users.data.length" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Panda</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Postęp</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Zasoby</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Akcja</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="relative flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 font-bold text-blue-700">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                        <span v-if="user.online" class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-emerald-500" title="W grze" />
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">{{ user.name }} <span class="font-normal text-slate-400">#{{ user.id }}</span></div>
                                        <div class="text-xs text-slate-500">{{ user.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <AdminBadge :tone="user.role === 'admin' ? 'blue' : 'slate'">{{ user.roleLabel }}</AdminBadge>
                                    <AdminBadge v-if="user.goldPanda" tone="amber">Gold</AdminBadge>
                                    <AdminBadge v-if="user.sheriff" tone="green">Szeryf</AdminBadge>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                <div class="font-semibold text-slate-800">Poziom {{ user.socialLevel }}</div>
                                <div class="text-xs text-slate-400">{{ number(user.coins) }} monet</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-500">
                                <div>{{ user.inventoryCount }} przedm. · {{ user.relationsCount }} rel.</div>
                                <div>{{ user.statesCount }} stanów gry</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <Link :href="`/admin/users/${user.id}`" class="inline-flex rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">Zarządzaj</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <AdminEmptyState v-else title="Nie znaleziono pand" description="Zmień kryteria filtrowania i spróbuj ponownie.">
                <template #icon>⌕</template>
            </AdminEmptyState>
            <AdminPagination :pagination="users" />
        </AdminCard>
    </AdminLayout>
</template>
