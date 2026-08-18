<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import type { ManagedUser, SelectOption, UserRole } from '@/types/admin';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    user: ManagedUser;
    roles: SelectOption<UserRole>[];
}>();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    role: props.user.role,
    sex: props.user.sex,
    coins: props.user.coins,
    goldpanda: props.user.goldpanda,
    sheriff: props.user.sheriff,
    social_level: props.user.socialLevel,
    social_score: props.user.socialScore,
    tour_finished: props.user.tourFinished,
    birthday: props.user.birthday ?? '',
    email_verified: props.user.emailVerified,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.patch(`/admin/users/${props.user.id}`, {
        preserveScroll: true,
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <form class="space-y-8" @submit.prevent="submit">
        <section>
            <h3 class="text-sm font-semibold text-slate-900">Tożsamość i uprawnienia</h3>
            <p class="mt-1 text-sm text-slate-500">Dane logowania, rola oraz podstawowe informacje o pandzie.</p>
            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">Nazwa pandy</span>
                    <input v-model="form.name" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" required />
                    <InputError class="mt-1" :message="form.errors.name" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">Adres e-mail</span>
                    <input v-model="form.email" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" type="email" required />
                    <InputError class="mt-1" :message="form.errors.email" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">Rola</span>
                    <select v-model="form.role" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.role" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">Płeć</span>
                    <select v-model="form.sex" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option :value="false">Chłopak</option>
                        <option :value="true">Dziewczyna</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">Data urodzenia</span>
                    <input v-model="form.birthday" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" type="date" />
                    <InputError class="mt-1" :message="form.errors.birthday" />
                </label>
                <label class="flex items-center gap-3 self-end rounded-xl border border-slate-200 px-4 py-3">
                    <input v-model="form.email_verified" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" type="checkbox" />
                    <span>
                        <span class="block text-sm font-medium text-slate-700">E-mail zweryfikowany</span>
                        <span class="block text-xs text-slate-500">Pozwala korzystać z chronionych funkcji</span>
                    </span>
                </label>
            </div>
        </section>

        <section class="border-t border-slate-100 pt-7">
            <h3 class="text-sm font-semibold text-slate-900">Parametry gry</h3>
            <p class="mt-1 text-sm text-slate-500">Waluta, członkostwo i postęp widoczny w kliencie Panfu.</p>
            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">Monety</span>
                    <input v-model.number="form.coins" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" type="number" min="0" required />
                    <InputError class="mt-1" :message="form.errors.coins" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">Poziom społeczny</span>
                    <input v-model.number="form.social_level" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" type="number" min="1" required />
                    <InputError class="mt-1" :message="form.errors.social_level" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">Punkty społeczne</span>
                    <input v-model.number="form.social_score" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" type="number" min="0" required />
                    <InputError class="mt-1" :message="form.errors.social_score" />
                </label>
                <div class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">Serwer gry</span>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5">
                        <div class="flex items-center gap-2 text-sm font-medium text-slate-700">
                            <span
                                :class="[
                                    'h-2.5 w-2.5 rounded-full',
                                    user.currentGameServerName ? 'bg-emerald-500' : 'bg-slate-300',
                                ]"
                                aria-hidden="true"
                            />
                            {{ user.currentGameServerName ?? 'Poza grą' }}
                        </div>
                        <span class="mt-0.5 block text-xs text-slate-500">Stan aktualizowany automatycznie przez grę</span>
                    </div>
                </div>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <label class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50/50 px-4 py-3">
                    <input v-model="form.goldpanda" class="rounded border-amber-300 text-amber-600 focus:ring-amber-500" type="checkbox" />
                    <span class="text-sm font-medium text-amber-900">Gold Panda</span>
                </label>
                <label class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/50 px-4 py-3">
                    <input v-model="form.sheriff" class="rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500" type="checkbox" />
                    <span class="text-sm font-medium text-emerald-900">Szeryf</span>
                </label>
                <label class="flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50/50 px-4 py-3">
                    <input v-model="form.tour_finished" class="rounded border-blue-300 text-blue-600 focus:ring-blue-500" type="checkbox" />
                    <span class="text-sm font-medium text-blue-900">Samouczek ukończony</span>
                </label>
            </div>
        </section>

        <section class="border-t border-slate-100 pt-7">
            <h3 class="text-sm font-semibold text-slate-900">Reset hasła</h3>
            <p class="mt-1 text-sm text-slate-500">Pozostaw puste, aby nie zmieniać hasła. Zmiana wyloguje wszystkie sesje tej pandy.</p>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">Nowe hasło</span>
                    <input v-model="form.password" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" type="password" autocomplete="new-password" />
                    <InputError class="mt-1" :message="form.errors.password" />
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-slate-700">Powtórz nowe hasło</span>
                    <input v-model="form.password_confirmation" class="w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" type="password" autocomplete="new-password" />
                </label>
            </div>
        </section>

        <div class="flex justify-end border-t border-slate-100 pt-6">
            <button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60" type="submit" :disabled="form.processing">
                {{ form.processing ? 'Zapisywanie…' : 'Zapisz konto' }}
            </button>
        </div>
    </form>
</template>
