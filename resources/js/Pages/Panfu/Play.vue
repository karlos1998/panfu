<script setup lang="ts">
import PanfuLayout from '@/Layouts/PanfuLayout.vue';
import type {
    FlashClient,
    MetaContent,
    NavigationItem,
    ShopCatalogue,
    ShopCategory,
    ShopItem,
} from '@/types/panfu';
import { computed, onMounted, onUnmounted, ref } from 'vue';

declare global {
    interface Window {
        flash_callback_exit_fullscreen?: () => Promise<void>;
        flash_callback_shop?: (category?: string) => void;
        flash_callback_toggle_fullscreen?: () => Promise<void>;
        RufflePlayer?: {
            config?: Record<string, unknown>;
        };
    }
}

interface PanfuFlashObject extends HTMLObjectElement {
    onShopClosed?: () => void;
    purchaseItem?: (payload: { itemHash: string; itemId: number }) => boolean | void;
}

const props = defineProps<{
    client: FlashClient;
}>();

const meta: MetaContent = {
    title: 'Panfu',
    description: 'Lokalny klient Panfu.',
};

const navigation: NavigationItem[] = [
    { label: 'Strona główna', href: '/' },
    { label: 'Profil', href: '/profile' },
];

const categoryLabels: Record<string, string> = {
    clothes: 'Ubrania',
    furniture: 'Meble',
    pets: 'Zwierzaki',
    playercard: 'Karta gracza',
};

const subcategoryLabels: Record<string, string> = {
    all: 'Wszystko',
    face: 'Twarz',
    feet: 'Buty',
    flags: 'Flagi',
    floor: 'Podłoga',
    hand: 'Akcesoria',
    head: 'Głowa',
    lowerbody: 'Dół',
    upperbody: 'Góra',
    wall: 'Ściany',
};

const isShopOpen = ref(false);
const isShopLoading = ref(false);
const selectedCategory = ref<string | null>(null);
const selectedSubcategory = ref<string | null>(null);
const selectedItem = ref<ShopItem | null>(null);
const shop = ref<ShopCatalogue | null>(null);
const shopError = ref<string | null>(null);

const shopCategories = computed(() => Object.keys(shop.value?.items ?? {}));
const currentCategory = computed<ShopCategory | null>(() => {
    if (!shop.value || !selectedCategory.value) {
        return null;
    }

    return shop.value.items[selectedCategory.value] ?? null;
});

const currentSubcategories = computed<Record<string, ShopItem[]>>(() => {
    const category = currentCategory.value;

    if (!category) {
        return {};
    }

    const groups: Record<string, ShopItem[]> = {};

    if (category.items?.length) {
        groups.all = category.items;
    }

    if (category.subcategories && !Array.isArray(category.subcategories)) {
        Object.entries(category.subcategories).forEach(([key, items]) => {
            groups[key] = items;
        });
    }

    return groups;
});

const currentItems = computed(() => {
    if (!selectedSubcategory.value) {
        return [];
    }

    return currentSubcategories.value[selectedSubcategory.value] ?? [];
});

const canPurchase = computed(() => {
    if (!selectedItem.value || !shop.value) {
        return false;
    }

    return selectedItem.value.price <= shop.value.coins;
});

const formatCoins = (value: number) => new Intl.NumberFormat('pl-PL').format(value);

const labelFor = (value: string) =>
    subcategoryLabels[value] ??
    categoryLabels[value] ??
    value
        .replaceAll('_', ' ')
        .replaceAll('-', ' ')
        .toLowerCase()
        .replace(/(^|\s)\S/g, (letter) => letter.toUpperCase());

const getFlashObject = () =>
    document.querySelector<PanfuFlashObject>('.panfu-play__object');

const selectSubcategory = (subcategory: string) => {
    selectedSubcategory.value = subcategory;
    selectedItem.value = null;
};

const selectCategory = (category: string) => {
    selectedCategory.value = category;
    selectedItem.value = null;

    const groups = currentSubcategories.value;
    selectedSubcategory.value = Object.keys(groups)[0] ?? null;
};

const loadShop = async () => {
    if (shop.value || isShopLoading.value) {
        return;
    }

    isShopLoading.value = true;
    shopError.value = null;

    try {
        const response = await fetch('/api/shop', {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Shop request failed: ${response.status}`);
        }

        shop.value = (await response.json()) as ShopCatalogue;

        if (!selectedCategory.value) {
            const firstCategory = shopCategories.value[0];

            if (firstCategory) {
                selectCategory(firstCategory);
            }
        }
    } catch {
        shopError.value = 'Nie udało się wczytać sklepu.';
    } finally {
        isShopLoading.value = false;
    }
};

const openShop = (category?: string) => {
    isShopOpen.value = true;

    void loadShop().then(() => {
        if (category && shop.value?.items[category]) {
            selectCategory(category);
        }
    });
};

const closeShop = () => {
    isShopOpen.value = false;
    selectedItem.value = null;

    try {
        getFlashObject()?.onShopClosed?.();
    } catch {
        // Ruffle may drop the callback while the SWF is unloading.
    }
};

const removePurchasedItem = (itemId: number) => {
    if (!shop.value || !selectedCategory.value) {
        return;
    }

    const category = shop.value.items[selectedCategory.value];
    category.items = category.items.filter((item) => item.id !== itemId);

    const subcategories = category.subcategories;

    if (subcategories && !Array.isArray(subcategories)) {
        Object.keys(subcategories).forEach((key) => {
            subcategories[key] = subcategories[key].filter(
                (item: ShopItem) => item.id !== itemId,
            );
        });
    }
};

const purchaseSelectedItem = () => {
    if (!selectedItem.value || !shop.value || !canPurchase.value) {
        return;
    }

    const flashObject = getFlashObject();

    if (typeof flashObject?.purchaseItem !== 'function') {
        shopError.value = 'Sklep w grze nie jest jeszcze gotowy.';
        return;
    }

    try {
        const bought = flashObject.purchaseItem({
            itemId: selectedItem.value.id,
            itemHash: selectedItem.value.hash,
        });

        if (bought !== false) {
            shop.value.coins -= selectedItem.value.price;
            removePurchasedItem(selectedItem.value.id);
            selectedItem.value = null;
            shopError.value = null;
        }
    } catch {
        shopError.value = 'Zakup nie powiódł się.';
    }
};

const configureRuffle = () => {
    window.RufflePlayer = window.RufflePlayer || {};
    window.RufflePlayer.config = {
        ...(window.RufflePlayer.config || {}),
        allowNetworking: 'all',
        allowScriptAccess: true,
        autoplay: 'on',
        base: props.client.baseUrl,
        credentialAllowList: [window.location.origin],
        favorFlash: false,
        logLevel: 'warn',
        openUrlMode: 'deny',
        publicPath: '/vendor/ruffle/',
        showSwfDownload: false,
        splashScreen: false,
        socketProxy: [
            { host: '127.0.0.1', port: 9595, proxyUrl: props.client.socketProxyUrl },
            { host: 'localhost', port: 9595, proxyUrl: props.client.socketProxyUrl },
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
};

const toggleFullscreen = async () => {
    if (document.fullscreenElement) {
        await document.exitFullscreen();
        return;
    }

    await getFlashObject()?.requestFullscreen?.();
};

const exitFullscreen = async () => {
    if (document.fullscreenElement) {
        await document.exitFullscreen();
    }
};

onMounted(() => {
    configureRuffle();

    window.flash_callback_shop = openShop;
    window.flash_callback_toggle_fullscreen = toggleFullscreen;
    window.flash_callback_exit_fullscreen = exitFullscreen;
});

onUnmounted(() => {
    delete window.flash_callback_shop;
    delete window.flash_callback_toggle_fullscreen;
    delete window.flash_callback_exit_fullscreen;
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

            <div
                v-if="isShopOpen"
                class="panfu-shop-backdrop"
                role="presentation"
                @click.self="closeShop"
            >
                <section class="panfu-shop" role="dialog" aria-modal="true" aria-label="Sklep">
                    <header class="panfu-shop__header">
                        <div>
                            <h2>Sklep</h2>
                            <span v-if="shop">{{ formatCoins(shop.coins) }} monet</span>
                        </div>

                        <button
                            class="panfu-shop__close"
                            type="button"
                            aria-label="Zamknij sklep"
                            @click="closeShop"
                        >
                            x
                        </button>
                    </header>

                    <nav v-if="shopCategories.length" class="panfu-shop__tabs" aria-label="Kategorie">
                        <button
                            v-for="category in shopCategories"
                            :key="category"
                            type="button"
                            :class="{ 'is-active': selectedCategory === category }"
                            @click="selectCategory(category)"
                        >
                            {{ labelFor(category) }}
                        </button>
                    </nav>

                    <div class="panfu-shop__body">
                        <div v-if="isShopLoading" class="panfu-shop__state">Ładowanie...</div>
                        <div v-else-if="shopError" class="panfu-shop__state">{{ shopError }}</div>
                        <div v-else class="panfu-shop__content">
                            <aside
                                v-if="Object.keys(currentSubcategories).length > 1"
                                class="panfu-shop__subtabs"
                                aria-label="Podkategorie"
                            >
                                <button
                                    v-for="(_, subcategory) in currentSubcategories"
                                    :key="subcategory"
                                    type="button"
                                    :class="{ 'is-active': selectedSubcategory === subcategory }"
                                    @click="selectSubcategory(subcategory)"
                                >
                                    {{ labelFor(subcategory) }}
                                </button>
                            </aside>

                            <div class="panfu-shop__items">
                                <button
                                    v-for="item in currentItems"
                                    :key="item.id"
                                    class="panfu-shop-item"
                                    :class="{ 'is-selected': selectedItem?.id === item.id }"
                                    type="button"
                                    @click="selectedItem = item"
                                >
                                    <span class="panfu-shop-item__icon">{{ item.id }}</span>
                                    <span class="panfu-shop-item__name">{{ labelFor(item.name) }}</span>
                                    <span class="panfu-shop-item__price">
                                        {{ formatCoins(item.price) }}
                                    </span>
                                </button>

                                <div v-if="!currentItems.length" class="panfu-shop__state">
                                    Brak przedmiotów.
                                </div>
                            </div>
                        </div>
                    </div>

                    <footer class="panfu-shop__footer">
                        <div v-if="selectedItem">
                            <strong>{{ labelFor(selectedItem.name) }}</strong>
                            <span>{{ formatCoins(selectedItem.price) }} monet</span>
                        </div>
                        <div v-else />

                        <button
                            class="panfu-shop__buy"
                            type="button"
                            :disabled="!canPurchase"
                            @click="purchaseSelectedItem"
                        >
                            Kup
                        </button>
                    </footer>
                </section>
            </div>
        </section>
    </PanfuLayout>
</template>
