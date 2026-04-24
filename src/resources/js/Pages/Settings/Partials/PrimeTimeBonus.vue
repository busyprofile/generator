<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {router, useForm, usePage} from '@inertiajs/vue3';
import NumberInput from "@/Components/NumberInput.vue";
import InputHelper from "@/Components/InputHelper.vue";

const primeTimeBonus = usePage().props.primeTimeBonus;

const form = useForm({
    starts: primeTimeBonus.starts,
    ends: primeTimeBonus.ends,
    rate: primeTimeBonus.rate,
});

const submit = () => {
    form.patch(route('admin.settings.update.prime-time-bonus'), {
        preserveScroll: true,
        onError: (result) => form.reset(),
    });
};

//
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Настройка бонуса за работу в прайм-тайм</h2>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div class="max-w-[24rem] grid sm:grid-cols-2 grid-cols-1 gap-4">
                <div>
                    <label for="start-time" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Время начала:</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 end-0 top-0 flex items-center pe-3.5 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <input
                            type="time"
                            id="start-time"
                            class="bg-gray-50 border dark:focus:ring-indigo-600 leading-none border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-900 rounded-xl  focus:ring-indigo-500 focus:border-indigo-500 dark:focus:border-indigo-600block w-full p-2.5 dark:placeholder-gray-400 dark:text-gray-300 dark:focus:border-blue-500 shadow-sm"
                            v-model="form.starts"
                            required
                        />
                    </div>
                </div>
                <div>
                    <label for="end-time" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Время окончания:</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 end-0 top-0 flex items-center pe-3.5 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4a1 1 0 1 0-2 0v4a1 1 0 0 0 .293.707l3 3a1 1 0 0 0 1.414-1.414L13 11.586V8Z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <input
                            type="time"
                            id="end-time"
                            class="bg-gray-50 border dark:focus:ring-indigo-600 leading-none border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-900 rounded-xl  focus:ring-indigo-500 focus:border-indigo-500 dark:focus:border-indigo-600block w-full p-2.5 dark:placeholder-gray-400 dark:text-gray-300 dark:focus:border-blue-500 shadow-sm"
                            v-model="form.ends"
                            required
                        />
                    </div>
                </div>
            </div>
            <InputError class="mt-2" :message="form.errors.starts" />
            <InputError class="mt-2" :message="form.errors.ends" />

            <div class="max-w-[24rem]">
                <div>
                    <InputLabel
                        for="rate"
                        value="Рейт %"
                        :error="!!form.errors.rate"
                    />

                    <NumberInput
                        id="rate"
                        v-model="form.rate"
                        class="mt-1 block w-full"
                        step="0.01"
                        placeholder="0.0"
                        :error="!!form.errors.rate"
                        @input="form.clearErrors('rate')"
                    />

                    <InputError class="mt-2" :message="form.errors.rate" />
                    <InputHelper v-if="! form.errors.rate" model-value="Складывается с % комиссии трейдера, которая в настройках платежного метода"></InputHelper>
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
