<script setup lang="ts">
import type { PandaAvatarData } from '@/types/panfu';
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    avatar: PandaAvatarData;
    name: string;
    size?: number;
}>(), {
    size: 43,
});

const avatarStyle = computed(() => ({
    width: `${props.size}px`,
    height: `${props.size}px`,
    flexBasis: `${props.size}px`,
    backgroundImage: `url(${JSON.stringify(props.avatar.url)})`,
    backgroundPosition: `50% ${(-10 * props.size) / 43}px`,
    backgroundSize: `${(80 * props.size) / 43}px`,
}));
</script>

<template>
    <span
        class="panfu-panda-avatar"
        :style="avatarStyle"
        :aria-label="`Awatar pandy ${name}`"
        role="img"
    />
</template>
