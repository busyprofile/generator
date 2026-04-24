<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import {Head, router, useForm, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SaveButton from "@/Components/Form/SaveButton.vue";
import SecondaryPageSection from "@/Wrappers/SecondaryPageSection.vue";
import InputHelper from "@/Components/InputHelper.vue";
import TextInput from "@/Components/TextInput.vue";

const payoutGateway = usePage().props.payoutGateway;

const form = useForm({
    name: payoutGateway?.name ?? null,
    domain: payoutGateway?.domain ?? null,
    callback_url: payoutGateway?.callback_url ?? null,
    enabled: !!payoutGateway?.enabled ?? false,
});

const submit = () => {
    if (! payoutGateway) {
        form.post(route('payout-gateways.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                router.visit(route('payouts.index'), {
                    data: {
                        page: 1,
                        tab: 'payout-gateways'
                    }
                });
            },
        });
    } else {
        form.patch(route('payout-gateways.update', payoutGateway.id), {
            preserveScroll: true,
            onSuccess: () => {
                router.visit(route('payouts.index'), {
                    data: {
                        page: 1,
                        tab: 'payout-gateways'
                    }
                });
            },
        });
    }
}

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head :title="payoutGateway ? 'Редактирование направления выплат' : 'Новое направление выплат'" />

        <SecondaryPageSection
            :back-link="route('payouts.index', {
                page: 1,
                tab: 'payout-gateways'
            })"
            :title="payoutGateway ? 'Редактирование направления выплат' : 'Новое направление выплат'"
            :description="payoutGateway ? 'Здесь вы можете отредактировать направление на выплату средств.' : 'Здесь вы можете создать направление на выплату средств.'"
        >
            <form @submit.prevent="submit" class="mt-6 space-y-6">
                <div>
                    <InputLabel
                        for="name"
                        value="Название направления"
                        :error="!!form.errors.name"
                    />

                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        :error="!!form.errors.name"
                        @input="form.clearErrors('name')"
                    />

                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel
                        for="domain"
                        value="Укажите ссылку на проект"
                        :error="!!form.errors.domain"
                    />

                    <TextInput
                        id="domain"
                        v-model="form.domain"
                        type="text"
                        class="mt-1 block w-full"
                        :error="!!form.errors.domain"
                        @input="form.clearErrors('domain')"
                    />

                    <InputError :message="form.errors.domain" class="mt-2" />
                    <InputHelper v-if="! form.errors.domain" model-value="Указывайте ссылку в формате https://example.com/"></InputHelper>
                </div>

                <div>
                    <InputLabel
                        for="callback_url"
                        value="Укажите ссылку на проект"
                        :error="!!form.errors.callback_url"
                    />

                    <TextInput
                        id="callback_url"
                        v-model="form.callback_url"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="https://example.com/callback"
                        :error="!!form.errors.callback_url"
                        @input="form.clearErrors('callback_url')"
                    />

                    <InputError :message="form.errors.callback_url" class="mt-2" />
                    <InputHelper v-if="! form.errors.callback_url" model-value="Установите ссылку на Ваш обработчик для получения уведомлений. По ней мы будем отправлять POST запросы о статусах выплат."></InputHelper>
                </div>

                <div class="">
                    <label class="inline-flex items-center mb-3 mt-3 cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer" v-model="form.enabled">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:w-5 after:h-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Включен</span>
                    </label>
                </div>
                <SaveButton
                    :disabled="form.processing"
                    :saved="form.recentlySuccessful"
                ></SaveButton>
            </form>
        </SecondaryPageSection>
    </div>
</template>
