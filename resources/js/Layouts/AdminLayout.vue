<script setup lang="ts">
import type { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps<{ title: string }>();

const page = usePage<PageProps>();
const sidebarOpen = ref(false);
const user = computed(() => page.props.auth.user);
const success = computed(() => page.props.flash?.success);

const navigation = [
    { label: 'Pulpit', href: '/admin', route: 'admin.dashboard', icon: '▦' },
    { label: 'Użytkownicy', href: '/admin/users', route: 'admin.users.*', icon: '●' },
];
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-slate-50 font-sans text-slate-800">
        <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" @click="sidebarOpen = false" />

        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-slate-800 bg-slate-950 text-white transition-transform lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-20 items-center gap-3 border-b border-white/10 px-6">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500 text-lg font-black shadow-lg shadow-blue-500/20">P</div>
                <div>
                    <div class="text-sm font-bold tracking-wide">Panfu Admin</div>
                    <div class="text-xs text-slate-400">Centrum zarządzania</div>
                </div>
            </div>

            <nav class="flex-1 space-y-1 p-4">
                <Link
                    v-for="item in navigation"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition"
                    :class="route().current(item.route) ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/30' : 'text-slate-300 hover:bg-white/5 hover:text-white'"
                    @click="sidebarOpen = false"
                >
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/10 text-xs">{{ item.icon }}</span>
                    {{ item.label }}
                </Link>
            </nav>

            <div class="border-t border-white/10 p-4">
                <Link href="/" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm text-slate-300 hover:bg-white/5 hover:text-white">
                    <span aria-hidden="true">←</span>
                    Wróć do Panfu
                </Link>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-8">
                <button class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" type="button" aria-label="Otwórz menu" @click="sidebarOpen = true">☰</button>
                <div class="hidden text-sm text-slate-500 sm:block">Panel administracyjny</div>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <div class="text-sm font-semibold text-slate-800">{{ user?.name }}</div>
                        <div class="text-xs text-slate-500">Administrator</div>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">
                        {{ user?.name?.charAt(0).toUpperCase() }}
                    </div>
                </div>
            </header>

            <main class="p-4 sm:p-8">
                <div v-if="success" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ success }}
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
