<script setup lang="ts">
import PanfuLayout from '@/Layouts/PanfuLayout.vue';
import type { FlashClient, MetaContent, NavigationItem } from '@/types/panfu';
import { onMounted } from 'vue';

declare global {
    interface Window {
        RufflePlayer?: {
            config?: Record<string, unknown>;
        };
    }
}

const props = defineProps<{
    client: FlashClient;
}>();

const meta: MetaContent = {
    title: 'Panfu',
    description: 'Lokalny klient Panfu uruchamiany przez Ruffle.',
};

const navigation: NavigationItem[] = [
    { label: 'Strona główna', href: '/' },
    { label: 'Profil', href: '/profile' },
];

onMounted(() => {
    window.RufflePlayer = window.RufflePlayer || {};
    window.RufflePlayer.config = {
        ...(window.RufflePlayer.config || {}),
        allowNetworking: 'all',
        allowScriptAccess: true,
        autoplay: 'on',
        base: props.client.baseUrl,
        credentialAllowList: [window.location.origin],
        logLevel: 'warn',
        openUrlMode: 'deny',
        publicPath: '/vendor/ruffle/',
        showSwfDownload: false,
        socketProxy: [
            { host: '127.0.0.1', port: 9595, proxyUrl: 'ws://localhost:9596' },
            { host: 'localhost', port: 9595, proxyUrl: 'ws://localhost:9596' },
        ],
        unmuteOverlay: 'hidden',
        upgradeToHttps: false,
        urlRewriteRules: [
            [/^http:\/\/amf\.old\.panfu\.test\/?$/, props.client.informationServerUrl],
            [
                /^http:\/\/amf\.old\.panfu\.test\/(.+)$/,
                `${props.client.informationServerUrl}$1`,
            ],
        ],
    };

    if (!document.querySelector(`script[src="${props.client.ruffleScript}"]`)) {
        const script = document.createElement('script');
        script.src = props.client.ruffleScript;
        script.async = true;
        document.head.appendChild(script);
    }
});
</script>

<template>
    <PanfuLayout
        :meta="meta"
        logo="/vendor/panfu-me/assets/panfu-logo-BkIF66dU.svg"
        :navigation="navigation"
        main-class="panfu-main--play"
    >
        <section class="panfu-play">
            <div class="panfu-play__bar">
                <h1>{{ client.title }}</h1>
                <span>{{ client.serverName }}</span>
            </div>

            <div class="panfu-play__stage">
                <object
                    class="panfu-play__object"
                    :data="client.swfUrl"
                    type="application/x-shockwave-flash"
                    width="950"
                    height="600"
                >
                    <param name="movie" :value="client.swfUrl" />
                    <param name="base" :value="client.baseUrl" />
                    <param name="quality" value="high" />
                    <param name="allowNetworking" value="all" />
                    <param name="allowScriptAccess" value="always" />
                    <param name="allowFullScreen" value="true" />
                    <param name="flashvars" :value="client.flashvarsQuery" />
                </object>
            </div>
        </section>
    </PanfuLayout>
</template>
