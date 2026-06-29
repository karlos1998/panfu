<script setup lang="ts">
import type { FooterContent } from '@/types/panfu';
import { Link } from '@inertiajs/vue3';

defineProps<{
    footer: FooterContent;
}>();
</script>

<template>
    <footer class="panfu-footer">
        <div class="panfu-footer__inner">
            <div class="panfu-footer__copy">
                <p>{{ footer.copyright }}</p>
                <p>{{ footer.disclaimer }}</p>
            </div>

            <nav class="panfu-footer__links" aria-label="Footer">
                <div>
                    <template v-for="(link, index) in footer.links" :key="link.label">
                        <Link :href="link.href" class="panfu-footer__link">
                            {{ link.label }}
                        </Link>
                        <span v-if="index < footer.links.length - 1"> | </span>
                    </template>
                </div>

                <div>
                    <template v-for="(link, index) in footer.legalLinks" :key="link.label">
                        <Link :href="link.href" class="panfu-footer__link">
                            {{ link.label }}
                        </Link>
                        <span v-if="index < footer.legalLinks.length - 1"> | </span>
                    </template>
                </div>
            </nav>
        </div>
    </footer>
</template>
