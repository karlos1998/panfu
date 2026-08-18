<script setup lang="ts">
import type { PandaAvatarData } from '@/types/blog';

defineProps<{ avatar: PandaAvatarData; name: string }>();
</script>

<template>
    <span class="panfu-panda-avatar" :aria-label="`Awatar pandy ${name}`" role="img">
        <img v-if="avatar.background" class="panfu-panda-avatar__layer panfu-panda-avatar__background" :src="avatar.background" alt="" />
        <img
            class="panfu-panda-avatar__layer panfu-panda-avatar__base"
            :src="avatar.base"
            alt=""
        />
        <img v-for="layer in avatar.layers" :key="layer" class="panfu-panda-avatar__layer" :src="layer" alt="" />
    </span>
</template>
