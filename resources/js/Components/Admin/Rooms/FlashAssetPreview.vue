<script setup lang="ts">
import { onMounted } from 'vue';

const props = withDefaults(defineProps<{
    url: string;
    ruffleScript: string;
    baseUrl?: string;
    label?: string;
}>(), {
    baseUrl: '/',
    label: 'Podgląd zasobu Flash',
});

const loadRuffle = () => {
    const ruffleWindow = window as Window & { RufflePlayer?: { config?: Record<string, unknown> } };
    ruffleWindow.RufflePlayer = ruffleWindow.RufflePlayer || {};
    ruffleWindow.RufflePlayer.config = {
        ...(ruffleWindow.RufflePlayer.config || {}),
        allowNetworking: 'all',
        autoplay: 'on',
        publicPath: '/vendor/ruffle/',
        showSwfDownload: false,
        splashScreen: false,
        unmuteOverlay: 'hidden',
    };

    if (!document.querySelector(`script[src="${props.ruffleScript}"]`)) {
        const script = document.createElement('script');
        script.src = props.ruffleScript;
        script.async = true;
        document.head.appendChild(script);
    }
};

onMounted(loadRuffle);
</script>

<template>
    <object :key="url" :data="url" type="application/x-shockwave-flash" :aria-label="label">
        <param name="movie" :value="url" />
        <param name="base" :value="baseUrl" />
        <param name="allowScriptAccess" value="always" />
        <param name="wmode" value="transparent" />
    </object>
</template>
