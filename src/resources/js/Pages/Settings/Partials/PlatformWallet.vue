<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {router, useForm, usePage} from '@inertiajs/vue3';
import InputHelper from "@/Components/InputHelper.vue";
import TextInput from "@/Components/TextInput.vue";

const platform_wallet = usePage().props.platformWallet || '';

const form = useForm({
    platform_wallet: platform_wallet,
});

const submit = () => {
    console.log(form.data());
    form.patch(route('admin.settings.update.platform-wallet'), {
        preserveScroll: true,
        onError: (result) => form.reset(),
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Настройка адреса кошелька площадки</h2>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div class="max-w-[24rem]">
                <div>
                    <InputLabel
                        for="platform_wallet"
                        value="Кошелёк"
                        :error="!!form.errors.platform_wallet"
                    />

                    <TextInput
                        id="platform_wallet"
                        v-model="form.platform_wallet"
                        class="mt-1 block w-full bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-700 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring focus:ring-indigo-200 dark:focus:ring-indigo-800 rounded-md shadow-sm"
                        step="0.01"
                        placeholder="Введите адрес кошелька"
                        :error="!!form.errors.platform_wallet"
                        @input="form.clearErrors('platform_wallet')"
                        disabled
                    />

                    <InputError class="mt-2" :message="form.errors.platform_wallet" />
                    <InputHelper v-if="! form.errors.platform_wallet" model-value="Адрес кошелька USDT TRC20"></InputHelper>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Сохранить</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Сохранено.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
