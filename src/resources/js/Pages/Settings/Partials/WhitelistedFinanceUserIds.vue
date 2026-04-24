<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {useForm, usePage} from '@inertiajs/vue3';
import InputHelper from "@/Components/InputHelper.vue";
import TextArea from "@/Components/TextArea.vue";

const whitelisted_finance_user_ids = usePage().props.whitelistedFinanceUserIds;

const form = useForm({
    whitelisted_finance_user_ids: whitelisted_finance_user_ids,
});

const submit = () => {
    if (form.whitelisted_finance_user_ids) {
        form.whitelisted_finance_user_ids = form.whitelisted_finance_user_ids
            .replace(/[, ]+/g, '\n')
            .replace(/\n+/g, '\n')
            .trim();
    }

    form.patch(route('admin.settings.update.whitelisted-finance-user-ids'), {
        preserveScroll: true,
        onError: (result) => form.reset(),
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Настройка белого списка Finance User IDs</h2>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div class="max-w-[24rem]">
                <div>
                    <InputLabel
                        for="whitelisted_finance_user_ids"
                        value="User IDs"
                        :error="!!form.errors.whitelisted_finance_user_ids"
                    />

                    <TextArea
                        id="whitelisted_finance_user_ids"
                        v-model="form.whitelisted_finance_user_ids"
                        class="mt-1 block w-full"
                        rows="4"
                        :error="!!form.errors.whitelisted_finance_user_ids"
                        @input="form.clearErrors('whitelisted_finance_user_ids')"
                    />

                    <InputError class="mt-2" :message="form.errors.whitelisted_finance_user_ids" />
                    <InputHelper v-if="! form.errors.whitelisted_finance_user_ids" model-value="Укажите ID пользователей с новой строки."></InputHelper>
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
