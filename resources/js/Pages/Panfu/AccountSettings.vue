<script setup lang="ts">
import AccountDeleteCard from '@/Components/Panfu/Account/AccountDeleteCard.vue';
import AccountDiscordCard from '@/Components/Panfu/Account/AccountDiscordCard.vue';
import AccountSettingsCard from '@/Components/Panfu/Account/AccountSettingsCard.vue';
import AccountStatsCard from '@/Components/Panfu/Account/AccountStatsCard.vue';
import PanfuLayout from '@/Layouts/PanfuLayout.vue';
import type { AccountSettingsData, MetaContent } from '@/types/panfu';
import { Head } from '@inertiajs/vue3';

defineProps<{
    account: AccountSettingsData;
    mustVerifyEmail?: boolean;
    status?: string;
}>();

const meta: MetaContent = {
    title: 'Ustawienia konta - Panfu.me',
    description: 'Zarządzaj swoim lokalnym kontem Panfu.',
};
</script>

<template>
    <PanfuLayout
        :meta="meta"
        logo="/vendor/panfu-me/assets/panfu-logo-BkIF66dU.svg"
        main-class="panfu-main--trees"
    >
        <Head title="Ustawienia konta - Panfu.me" />

        <section class="panfu-account">
            <AccountSettingsCard
                :account="account"
                :must-verify-email="mustVerifyEmail"
                :status="status"
            />
            <AccountStatsCard :account="account" />
            <AccountDiscordCard />
            <AccountDeleteCard />
        </section>
    </PanfuLayout>
</template>
