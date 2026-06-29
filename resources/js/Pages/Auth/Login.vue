<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <GuestLayout active="login" title="Logowanie - Panfu.me">
        <Head title="Logowanie - Panfu.me" />

        <div class="panfu-auth-row panfu-auth-row--login">
            <div class="panfu-auth-side panfu-auth-side--login" aria-hidden="true">
                <img
                    src="/vendor/panfu-me/assets/login-panda-DRflpxZs.png"
                    width="192"
                    height="290"
                    alt=""
                />
            </div>

            <section class="panfu-auth-card panfu-auth-card--login">
                <div class="panfu-auth-card__body">
                    <h1 class="panfu-auth-card__title">Logowanie</h1>
                    <p class="panfu-auth-card__subtitle">
                        Zaloguj się na swoje konto Panfu, aby kontynuować.
                    </p>

                    <div v-if="status" class="panfu-auth-status">
                        {{ status }}
                    </div>

                    <form class="panfu-form" @submit.prevent="submit">
                        <div class="panfu-form__group">
                            <label class="panfu-form__label" for="login">
                                Nazwa pandy lub Adres e-mail
                            </label>
                            <input
                                id="login"
                                v-model="form.login"
                                class="panfu-form__control"
                                type="text"
                                required
                                autofocus
                                autocomplete="username"
                            />
                            <InputError class="panfu-form__error" :message="form.errors.login" />
                        </div>

                        <div class="panfu-form__group">
                            <label class="panfu-form__label" for="password">
                                Hasło
                            </label>
                            <input
                                id="password"
                                v-model="form.password"
                                class="panfu-form__control"
                                type="password"
                                required
                                autocomplete="current-password"
                            />
                            <InputError class="panfu-form__error" :message="form.errors.password" />
                        </div>

                        <label class="panfu-form__check">
                            <input
                                v-model="form.remember"
                                class="panfu-form__check-input"
                                name="remember"
                                type="checkbox"
                            />
                            <span>Zapamiętaj mnie</span>
                        </label>

                        <div class="panfu-form__actions">
                            <button
                                class="panfu-form__primary"
                                type="submit"
                                :disabled="form.processing"
                            >
                                Logowanie
                            </button>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="panfu-form__link"
                            >
                                Zapomniałeś hasła?
                            </Link>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </GuestLayout>
</template>
