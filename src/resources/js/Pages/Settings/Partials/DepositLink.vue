<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {router, useForm, usePage} from '@inertiajs/vue3';
import InputHelper from "@/Components/InputHelper.vue";
import TextInput from "@/Components/TextInput.vue";

const deposit_link = usePage().props.depositLink;

const form = useForm({
    deposit_link: deposit_link,
});

const submit = () => {
    form.patch(route('admin.settings.update.deposit-link'), {
        preserveScroll: true,
        onError: (result) => form.reset(),
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Настройка ссылки пополнения кошелька трейдера</h2>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div class="max-w-[24rem]">
                <div>
                    <InputLabel
                        for="deposit_link"
                        value="Ссылка"
                        :error="!!form.errors.deposit_link"
                    />

                    <TextInput
                        id="deposit_link"
                        v-model="form.deposit_link"
                        class="mt-1 block w-full"
                        placeholder="https://test.te/pay.php"
                        :error="!!form.errors.deposit_link"
                        @input="form.clearErrors('deposit_link')"
                    />

                    <InputError class="mt-2" :message="form.errors.deposit_link" />
                    <InputHelper v-if="! form.errors.deposit_link" model-value="Эта ссылка будет открываться при нажатии на кнопку пополнения кошелька трейдера."></InputHelper>
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