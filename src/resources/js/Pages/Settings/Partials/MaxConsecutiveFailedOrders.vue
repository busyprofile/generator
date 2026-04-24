<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {useForm, usePage} from '@inertiajs/vue3';
import InputHelper from "@/Components/InputHelper.vue";
import NumberInput from "@/Components/NumberInput.vue";

const maxConsecutiveFailedOrders = usePage().props.maxConsecutiveFailedOrders;

const form = useForm({
    count: maxConsecutiveFailedOrders.count,
    period: maxConsecutiveFailedOrders.period,
});

const submit = () => {
    form.patch(route('admin.settings.update.max-consecutive-failed-orders'), {
        preserveScroll: true,
        onError: (result) => form.reset(), // Опционально: можно сбросить только ошибки form.clearErrors()
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Настройка максимума неудачных ордеров подряд</h2>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div class="max-w-[24rem]">
                <div>
                    <InputLabel
                        for="count-failed-orders"
                        value="Максимум неудачных ордеров подряд"
                        :error="!!form.errors.count"
                    />

                    <NumberInput
                        id="count-failed-orders"
                        v-model="form.count"
                        class="mt-1 block w-full"
                        step="1"
                        :error="!!form.errors.count"
                        @input="form.clearErrors('count')"
                    />

                    <InputError class="mt-2" :message="form.errors.count" />
                    <InputHelper v-if="! form.errors.count" model-value="Максимальное количество ордеров в статусе FAIL подряд, которое может быть у пользователя за период времени, прежде чем ему будет остановлен трафик. 0 = бесконечно"></InputHelper>
                </div>

                <div class="mt-4">
                    <InputLabel
                        for="period-failed-orders"
                        value="Период времени (в минутах)"
                        :error="!!form.errors.period"
                    />

                    <NumberInput
                        id="period-failed-orders"
                        v-model="form.period"
                        class="mt-1 block w-full"
                        step="1"
                        :error="!!form.errors.period"
                        @input="form.clearErrors('period')"
                    />

                    <InputError class="mt-2" :message="form.errors.period" />
                    <InputHelper v-if="! form.errors.period" model-value="Период времени, за который ведется подсчет неудачных ордеров подряд. 0 = бесконечно"></InputHelper>
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