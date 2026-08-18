<script setup lang="ts">
import PanelCard from '@/Components/PanelCard.vue';
import type { AccountSettingsData } from '@/types/panfu';
import { computed } from 'vue';

const props = defineProps<{ account: AccountSettingsData }>();

const number = (value: number | null) => new Intl.NumberFormat('pl-PL').format(value ?? 0);

const stats = computed(() => [
    { label: 'Poziom', value: String(props.account.socialLevel) },
    { label: 'Punkty', value: number(props.account.socialScore) },
    { label: 'Monety', value: number(props.account.coins) },
    { label: 'Gold Panda', value: props.account.goldPanda ? 'Tak' : 'Nie' },
    { label: 'Utworzono', value: props.account.createdAt ?? '—' },
    { label: 'Ostatnie logowanie', value: props.account.lastLogin ?? '—' },
]);
</script>

<template>
    <PanelCard title="Statystyki" :padded="false">
        <dl class="panfu-account-stats">
            <div v-for="stat in stats" :key="stat.label">
                <dt>{{ stat.label }}</dt>
                <dd>{{ stat.value }}</dd>
            </div>
        </dl>
    </PanelCard>
</template>
