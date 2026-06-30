<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import PanfuLayout from '@/Layouts/PanfuLayout.vue';
import type { MetaContent } from '@/types/panfu';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface AccountSettings {
    name: string;
    email: string;
    gender: 'boy' | 'girl';
    coins: number | null;
    goldPanda: boolean;
    socialLevel: number;
    socialScore: number | null;
    createdAt: string | null;
    lastLogin: string | null;
}

const props = defineProps<{
    account: AccountSettings;
    mustVerifyEmail?: boolean;
    status?: string;
}>();

const meta: MetaContent = {
    title: 'Ustawienia konta - Panfu.me',
    description: 'Zarządzaj swoim lokalnym kontem Panfu.',
};

const form = useForm({
    current_password: '',
    name: props.account.name,
    email: props.account.email,
    gender: props.account.gender,
    new_password: '',
    new_password_confirmation: '',
});

const deleteForm = useForm({
    password: '',
});

const formattedCoins = computed(() =>
    props.account.coins === null
        ? '0'
        : new Intl.NumberFormat('pl-PL').format(props.account.coins),
);

const formattedScore = computed(() =>
    props.account.socialScore === null
        ? '0'
        : new Intl.NumberFormat('pl-PL').format(props.account.socialScore),
);

const submit = () => {
    form.patch('/account/settings', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('current_password', 'new_password', 'new_password_confirmation');
        },
    });
};

const deleteAccount = () => {
    deleteForm.delete('/profile', {
        preserveScroll: true,
        onFinish: () => {
            deleteForm.reset('password');
        },
    });
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
            <article class="panfu-account-card">
                <header class="panfu-account-card__header">
                    Ustawienia konta
                </header>

                <form class="panfu-account-form" @submit.prevent="submit">
                    <div class="panfu-form__group">
                        <label class="panfu-form__label" for="current_password">
                            Aktualne hasło
                        </label>
                        <input
                            id="current_password"
                            v-model="form.current_password"
                            class="panfu-form__control"
                            type="password"
                            required
                            autocomplete="current-password"
                        />
                        <p class="panfu-form__hint">
                            Wpisz aktualne hasło, aby potwierdzić zmiany.
                        </p>
                        <InputError class="panfu-form__error" :message="form.errors.current_password" />
                    </div>

                    <div class="panfu-form__group">
                        <label class="panfu-form__label" for="name">
                            Nazwa pandy
                        </label>
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
                        <label class="panfu-form__label" for="new_password">
                            Nowe hasło
                        </label>
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
                        <InputError class="panfu-form__error" :message="form.errors.email" />
                    </div>

                    <div class="panfu-form__group">
                        <label class="panfu-form__label" for="new_password_confirmation">
                            Potwierdź hasło
                        </label>
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
                        <InputError class="panfu-form__error" :message="form.errors.gender" />
                    </div>

                    <div class="panfu-account-form__actions">
                        <button class="panfu-form__primary" type="submit" :disabled="form.processing">
                            Zapisz zmiany
                        </button>

                        <span v-if="status" class="panfu-account__status">
                            {{ status }}
                        </span>

                        <span v-if="mustVerifyEmail" class="panfu-account__status">
                            Adres e-mail wymaga weryfikacji.
                        </span>
                    </div>
                </form>
            </article>

            <article class="panfu-account-card">
                <header class="panfu-account-card__header">
                    Statystyki
                </header>

                <dl class="panfu-account-stats">
                    <div>
                        <dt>Poziom</dt>
                        <dd>{{ account.socialLevel }}</dd>
                    </div>
                    <div>
                        <dt>Punkty</dt>
                        <dd>{{ formattedScore }}</dd>
                    </div>
                    <div>
                        <dt>Monety</dt>
                        <dd>{{ formattedCoins }}</dd>
                    </div>
                    <div>
                        <dt>Gold Panda</dt>
                        <dd>{{ account.goldPanda ? 'Tak' : 'Nie' }}</dd>
                    </div>
                    <div>
                        <dt>Utworzono</dt>
                        <dd>{{ account.createdAt ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt>Ostatnie logowanie</dt>
                        <dd>{{ account.lastLogin ?? '-' }}</dd>
                    </div>
                </dl>
            </article>

            <article class="panfu-account-card panfu-account-card--discord">
                <header class="panfu-account-card__header">
                    Discord
                </header>

                <div class="panfu-account-card__body">
                    <p>Połącz konto Panfu z Discordem i dołącz do społeczności.</p>
                    <a class="panfu-account-button panfu-account-button--discord" href="#">
                        Połącz z Discordem
                    </a>
                </div>
            </article>

            <article class="panfu-account-card panfu-account-card--danger">
                <header class="panfu-account-card__header">
                    Usuń konto
                </header>

                <form class="panfu-account-card__body" @submit.prevent="deleteAccount">
                    <p>Twoje konto zostanie trwale usunięte po potwierdzeniu hasłem.</p>
                    <div class="panfu-account-delete">
                        <input
                            v-model="deleteForm.password"
                            class="panfu-form__control"
                            type="password"
                            placeholder="Hasło"
                            autocomplete="current-password"
                            required
                        />
                        <button
                            class="panfu-account-button panfu-account-button--danger"
                            type="submit"
                            :disabled="deleteForm.processing"
                        >
                            Usuń konto
                        </button>
                    </div>
                    <InputError class="panfu-form__error" :message="deleteForm.errors.password" />
                </form>
            </article>
        </section>
    </PanfuLayout>
</template>
