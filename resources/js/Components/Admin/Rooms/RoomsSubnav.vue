<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <nav class="panfu-admin-room-nav" aria-label="Rodzaj pokojów">
        <Link
            href="/admin/rooms/homes"
            class="panfu-admin-room-nav__link"
            :class="{ 'panfu-admin-room-nav__link--active': route().current('admin.rooms.homes.*') }"
        >
            Domki graczy
        </Link>
        <Link
            href="/admin/rooms/public"
            class="panfu-admin-room-nav__link"
            :class="{ 'panfu-admin-room-nav__link--active': route().current('admin.rooms.public.*') }"
        >
            Pokoje publiczne
        </Link>
    </nav>
</template>
