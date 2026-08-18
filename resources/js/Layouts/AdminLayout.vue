<script setup lang="ts">
import PanfuLayout from '@/Layouts/PanfuLayout.vue';
import type { PageProps } from '@/types';
import type { MetaContent } from '@/types/panfu';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ title: string }>();

const page = usePage<PageProps>();
const success = computed(() => page.props.flash?.success);
const meta = computed<MetaContent>(() => ({
    title: `${props.title} - Panfu.me`,
    description: 'Panel administracyjny Panfu.me.',
}));

const navigation = [
    { label: 'Pulpit', href: '/admin', route: 'admin.dashboard' },
    { label: 'Użytkownicy', href: '/admin/users', route: 'admin.users.*' },
    { label: 'Pokoje', href: '/admin/rooms/homes', route: 'admin.rooms.*' },
];
</script>

<template>
    <PanfuLayout
        :meta="meta"
        logo="/vendor/panfu-me/assets/panfu-logo-BkIF66dU.svg"
        main-class="panfu-main--trees"
    >
        <section class="panfu-admin">
            <nav class="panfu-admin-nav" aria-label="Panel administratora">
                <strong class="panfu-admin-nav__title">Administracja</strong>
                <div class="panfu-admin-nav__links">
                    <Link
                        v-for="item in navigation"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'panfu-admin-nav__link',
                            route().current(item.route) ? 'panfu-admin-nav__link--active' : '',
                        ]"
                    >
                        {{ item.label }}
                    </Link>
                </div>
            </nav>

            <div v-if="success" class="panfu-admin-alert">
                {{ success }}
            </div>

            <slot />
        </section>
    </PanfuLayout>
</template>
