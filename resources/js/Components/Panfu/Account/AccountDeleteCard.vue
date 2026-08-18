<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import PanelCard from '@/Components/PanelCard.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });

const deleteAccount = () => {
    form.delete('/profile', {
        preserveScroll: true,
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <PanelCard title="Usuń konto" severity="danger">
        <form @submit.prevent="deleteAccount">
            <p class="panfu-account-card-copy">
                Twoje konto zostanie trwale usunięte po potwierdzeniu hasłem.
            </p>
            <div class="panfu-account-delete">
                <input
                    v-model="form.password"
                    class="panfu-form__control"
                    type="password"
                    placeholder="Hasło"
                    autocomplete="current-password"
                    required
                />
                <button
                    class="panfu-account-button panfu-account-button--danger"
                    type="submit"
                    :disabled="form.processing"
                >
                    Usuń konto
                </button>
            </div>
            <InputError class="panfu-form__error" :message="form.errors.password" />
        </form>
    </PanelCard>
</template>
