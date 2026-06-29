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
            <p>{{ footer.copyright }}</p>

            <nav class="panfu-footer__links" aria-label="Stopka">
                <Link
                    v-for="link in footer.links"
                    :key="link.label"
                    :href="link.href"
                    class="panfu-footer__link"
                >
                    {{ link.label }}
                </Link>
            </nav>
        </div>
    </footer>
</template>
