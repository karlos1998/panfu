<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    gender: 'boy',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout active="register" title="Rejestracja - Panfu.me">
        <Head title="Rejestracja - Panfu.me" />

        <div class="panfu-auth-row panfu-auth-row--register">
            <section class="panfu-auth-card panfu-auth-card--register">
                <div class="panfu-auth-card__body">
                    <h1 class="panfu-auth-card__title">Stwórz swoją pandę</h1>
                    <p class="panfu-auth-card__subtitle">
                        Dołącz do naszej przyjaznej społeczności i graj z tysiącami innych pand!
                    </p>

                    <form class="panfu-form panfu-form--register" @submit.prevent="submit">
                        <div class="panfu-form__group">
                            <label class="panfu-form__label" for="name">
                                Nazwa pandy
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                class="panfu-form__control"
                                type="text"
                                minlength="3"
                                maxlength="15"
                                required
                                autofocus
                                autocomplete="name"
                            />
                            <p class="panfu-form__hint">
                                Możesz użyć liter, cyfr, kropek, myślników i podkreśleń.
                            </p>
                            <InputError class="panfu-form__error" :message="form.errors.name" />
                        </div>

                        <div class="panfu-form__group">
                            <label class="panfu-form__label" for="email">
                                Adres e-mail
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                class="panfu-form__control"
                                type="email"
                                required
                                autocomplete="username"
                            />
                            <p class="panfu-form__hint">
                                Twój adres email jest używany do logowania i odzyskiwania konta w razie potrzeby.
                            </p>
                            <InputError class="panfu-form__error" :message="form.errors.email" />
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
                                autocomplete="new-password"
                            />
                            <p class="panfu-form__hint">
                                Stwórz silne hasło składające się z co najmniej 8 znaków i zachowaj je w tajemnicy!
                            </p>
                            <InputError class="panfu-form__error" :message="form.errors.password" />
                        </div>

                        <div class="panfu-form__group">
                            <label class="panfu-form__label" for="gender">
                                Płeć
                            </label>
                            <select
                                id="gender"
                                v-model="form.gender"
                                class="panfu-form__control panfu-form__select"
                                required
                            >
                                <option value="boy">Chłopak</option>
                                <option value="girl">Dziewczyna</option>
                            </select>
                            <p class="panfu-form__hint">
                                Wybierz czy Twoja panda jest chłopcem czy dziewczynką.
                            </p>
                        </div>

                        <div class="panfu-form__group">
                            <label class="panfu-form__label" for="password_confirmation">
                                Potwierdzenie hasła
                            </label>
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                class="panfu-form__control"
                                type="password"
                                required
                                autocomplete="new-password"
                            />
                            <p class="panfu-form__hint">
                                Wpisz hasło jeszcze raz, aby upewnić się, że zostało wpisane poprawnie.
                            </p>
                            <InputError
                                class="panfu-form__error"
                                :message="form.errors.password_confirmation"
                            />
                        </div>

                        <div class="panfu-form__terms">
                            Kontynuując, zgadzasz się na nasz
                            <a href="#">Regulamin</a>
                            i
                            <a href="#">Politykę Prywatności</a>.
                        </div>

                        <div class="panfu-form__actions panfu-form__actions--register">
                            <button
                                class="panfu-form__primary"
                                type="submit"
                                :disabled="form.processing"
                            >
                                Stwórz swoją pandę
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <div class="panfu-auth-side panfu-auth-side--register" aria-hidden="true">
                <img
                    :src="form.gender === 'girl'
                        ? '/vendor/panfu-me/assets/panda-girl-DA6S3_ft.png'
                        : '/vendor/panfu-me/assets/panda-boy-DWR8tkxS.png'"
                    width="135"
                    height="263"
                    alt=""
                />
            </div>
        </div>
    </GuestLayout>
</template>
