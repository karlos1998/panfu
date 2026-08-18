<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import PanelCard from '@/Components/PanelCard.vue';
import type { AccountSettingsData } from '@/types/panfu';
import { useForm } from '@inertiajs/vue3';

const props = withDefaults(
    defineProps<{
        account: AccountSettingsData;
        mustVerifyEmail?: boolean;
        status?: string;
    }>(),
    {
        mustVerifyEmail: false,
        status: '',
    },
);

const form = useForm({
    current_password: '',
    name: props.account.name,
    email: props.account.email,
    gender: props.account.gender,
    new_password: '',
    new_password_confirmation: '',
});

const submit = () => {
    form.patch('/account/settings', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('current_password', 'new_password', 'new_password_confirmation');
        },
    });
};
</script>

<template>
    <PanelCard title="Ustawienia konta" :padded="false">
        <form class="panfu-account-form" @submit.prevent="submit">
            <div class="panfu-form__group">
                <label class="panfu-form__label" for="current_password">Aktualne hasło</label>
                <input
                    id="current_password"
                    v-model="form.current_password"
                    class="panfu-form__control"
                    type="password"
                    required
                    autocomplete="current-password"
                />
                <p class="panfu-form__hint">Wpisz aktualne hasło, aby potwierdzić zmiany.</p>
                <InputError class="panfu-form__error" :message="form.errors.current_password" />
            </div>

            <div class="panfu-form__group">
                <label class="panfu-form__label" for="name">Nazwa pandy</label>
                <input
                    id="name"
                    v-model="form.name"
                    class="panfu-form__control"
                    type="text"
                    required
                    autocomplete="name"
                />
                <InputError class="panfu-form__error" :message="form.errors.name" />
            </div>

            <div class="panfu-form__group">
                <label class="panfu-form__label" for="new_password">Nowe hasło</label>
                <input
                    id="new_password"
                    v-model="form.new_password"
                    class="panfu-form__control"
                    type="password"
                    autocomplete="new-password"
                />
                <InputError class="panfu-form__error" :message="form.errors.new_password" />
            </div>

            <div class="panfu-form__group">
                <label class="panfu-form__label" for="email">Adres e-mail</label>
                <input
                    id="email"
                    v-model="form.email"
                    class="panfu-form__control"
                    type="email"
                    required
                    autocomplete="username"
                />
                <InputError class="panfu-form__error" :message="form.errors.email" />
            </div>

            <div class="panfu-form__group">
                <label class="panfu-form__label" for="new_password_confirmation">Potwierdź hasło</label>
                <input
                    id="new_password_confirmation"
                    v-model="form.new_password_confirmation"
                    class="panfu-form__control"
                    type="password"
                    autocomplete="new-password"
                />
                <InputError
                    class="panfu-form__error"
                    :message="form.errors.new_password_confirmation"
                />
            </div>

            <div class="panfu-form__group">
                <label class="panfu-form__label" for="gender">Płeć</label>
                <select
                    id="gender"
                    v-model="form.gender"
                    class="panfu-form__control panfu-form__select"
                    required
                >
                    <option value="boy">Chłopak</option>
                    <option value="girl">Dziewczyna</option>
                </select>
                <InputError class="panfu-form__error" :message="form.errors.gender" />
            </div>

            <div class="panfu-account-form__actions">
                <button class="panfu-form__primary" type="submit" :disabled="form.processing">
                    Zapisz zmiany
                </button>
                <span v-if="status" class="panfu-account__status">{{ status }}</span>
                <span v-if="mustVerifyEmail" class="panfu-account__status">
                    Adres e-mail wymaga weryfikacji.
                </span>
            </div>
        </form>
    </PanelCard>
</template>
