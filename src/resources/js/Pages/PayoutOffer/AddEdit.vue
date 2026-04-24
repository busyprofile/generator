<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import {Head, router, useForm, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {computed, onMounted, ref} from "vue";
import Select from "@/Components/Select.vue";
import NumberInput from "@/Components/NumberInput.vue";
import SaveButton from "@/Components/Form/SaveButton.vue";
import SecondaryPageSection from "@/Wrappers/SecondaryPageSection.vue";
import InputHelper from "@/Components/InputHelper.vue";

const currencies = usePage().props.currencies;
const detailTypes = usePage().props.detailTypes;
const paymentGateways = usePage().props.paymentGateways;
const payoutOffer = usePage().props.payoutOffer;

const form = useForm({
    max_amount: payoutOffer?.max_amount ?? null,
    min_amount: payoutOffer?.min_amount ?? null,
    detail_types: [],
    active: !!payoutOffer?.active ?? false,
    payment_gateway_id: payoutOffer?.payment_gateway_id ?? 0,
});

const selectedDetailType = ref(0);

const selectedPaymentGateway = computed(() => {
    if (form.payment_gateway_id === 0) {
        return null;
    }

    return paymentGateways.filter(p => {
        return p.id === form.payment_gateway_id;
    })[0];
});

const currentCurrency = computed(() => {
    if (! selectedPaymentGateway.value) {
        return null;
    }

    return selectedPaymentGateway.value.currency.toUpperCase();
});

const addDetailType = (detailType) => {
    if (detailType === 0 || detailType === '0') {
        return;
    }

    form.clearErrors('detail_types');

    form.detail_types = form.detail_types.filter(d => {
        return d !== detailType;
    });
    form.detail_types.push(detailType);
}

const removeDetailType = (detailType) => {
    form.detail_types = form.detail_types.filter(d => {
        return d !== detailType.code;
    });
}

const detailTypesAvailableForGateway = computed(() => {
    if (! selectedPaymentGateway.value) {
        return null;
    }

    return detailTypes.filter(d => {
        return selectedPaymentGateway.value.detail_types.includes(d.code);
    })
});

const selectedDetailTypes = computed(() => {
    return detailTypes.filter(d => {
        return form.detail_types.includes(d.code);
    })
});

onMounted(() => {
    if (payoutOffer) {
        payoutOffer.detail_types.map((detailType) => {
            addDetailType(detailType.code)
        })
    }
})

const submit = () => {
    if (! payoutOffer) {
        form.post(route('trader.payout-offers.store'), {
            preserveScroll: true,
            onSuccess: (result) => {
                if (result.props.flash.message) {
                    return;
                }
                form.reset();
                router.visit(route('trader.payouts.index'), {
                    data: {
                        page: 1,
                        tab: 'payout-offers',
                    }
                });
            },
        });
    } else {
        form.patch(route('trader.payout-offers.update', payoutOffer.id), {
            preserveScroll: true,
            onSuccess: (result) => {
                if (result.props.flash.message) {
                    return;
                }
                router.visit(route('trader.payouts.index'), {
                    data: {
                        page: 1,
                        tab: 'payout-offers',
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
        <Head :title="payoutOffer ? 'Редактирование предложение выплаты' : 'Новое предложение выплаты'" />

        <SecondaryPageSection
            :back-link="route('trader.payouts.index', {
                page: 1,
                tab: 'payout-offers',
            })"
            :title="payoutOffer ? 'Редактирование предложения выплаты' : 'Новое предложение выплаты'"
            :description="payoutOffer ? 'Здесь вы можете отредактировать ваше предложение на выплату средств.' : 'Здесь вы можете создать ваше предложение на выплату средств.'"
        >
            <div v-show="$page.props.flash.message" class="flex items-center p-4 mt-6 mb-6 text-sm text-red-800 border border-red-300 rounded-xl  bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <div>
                    <span class="font-medium">Ошибка!</span> {{ $page.props.flash.message }}
                </div>
            </div>

            <form @submit.prevent="submit" class="mt-6 space-y-6">
                <div v-if="! payoutOffer">
                    <InputLabel
                        for="payment_gateway_id"
                        value="Платежный метод"
                        :error="!!form.errors.payment_gateway_id"
                        class="mb-1"
                    />
                    <Select
                        id="payment_gateway_id"
                        v-model="form.payment_gateway_id"
                        :error="!!form.errors.payment_gateway_id"
                        :items="paymentGateways"
                        value="id"
                        name="name"
                        default_title="Выберите платежный метод"
                        @change="form.clearErrors('payment_gateway_id');"
                    ></Select>

                    <InputError :message="form.errors.payment_gateway_id" class="mt-2" />
                </div>
                <div>
                    <InputLabel
                        for="detail_types"
                        value="Тип реквизитов"
                        :error="!!form.errors.detail_types"
                        class="mb-1"
                    />
                    <div class="flex justify-between gap-2">
                        <Select
                            id="detail_types"
                            v-model="selectedDetailType"
                            :error="!!form.errors.detail_types"
                            :items="detailTypesAvailableForGateway"
                            value="code"
                            name="name"
                            default_title="Выберите тип реквизитов"
                            @change="form.clearErrors('detail_types');"
                        ></Select>
                        <button
                            @click.prevent="addDetailType(selectedDetailType)"
                            type="button"
                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-xl text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
                        >
                            Добавить
                        </button>
                    </div>
                    <InputError :message="form.errors.detail_types" class="mt-2" />
                    <div class="flex gap-3 mt-3">
                        <span
                            v-for="detailType in selectedDetailTypes"
                            class="inline-flex items-center bg-indigo-100 text-indigo-800 text-sm font-medium me-2 px-2.5 py-0.5 rounded-lg dark:bg-indigo-900 dark:text-indigo-300"
                        >
                            {{ detailType.name }}
                            <svg @click="removeDetailType(detailType)" class="w-2.5 h-2.5 ml-1.5 cursor-pointer" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
                            </svg>
                        </span>
                    </div>
                </div>
                <div class="grid md:grid-cols-2 grid-cols-1 gap-6">
                    <div>
                        <InputLabel
                            for="min_amount"
                            :value="`Минимальная сумма ${currentCurrency ?? ''}`"
                            :error="!!form.errors.min_amount"
                        />

                        <NumberInput
                            id="min_amount"
                            v-model="form.min_amount"
                            class="mt-1 block w-full"
                            placeholder="0"
                            :error="!!form.errors.min_amount"
                            @input="form.clearErrors('min_amount')"
                        />

                        <InputError :message="form.errors.min_amount" class="mt-2" />
                        <InputHelper v-if="! form.errors.min_amount" model-value="Минимальная сумма на одну операцию которую вы готовы обработать одним переводом."></InputHelper>
                    </div>
                    <div>
                        <InputLabel
                            for="max_amount"
                            :value="`Максимальная сумма ${currentCurrency ?? ''}`"
                            :error="!!form.errors.max_amount"
                        />

                        <NumberInput
                            id="max_amount"
                            v-model="form.max_amount"
                            class="mt-1 block w-full"
                            placeholder="0"
                            :error="!!form.errors.max_amount"
                            @input="form.clearErrors('max_amount')"
                        />

                        <InputError :message="form.errors.max_amount" class="mt-2" />
                        <InputHelper v-if="! form.errors.max_amount" model-value="Максимальная сумма на одну операцию которую вы готовы обработать одним переводом."></InputHelper>
                    </div>
                </div>
                <div class="">
                    <label class="inline-flex items-center mb-3 mt-3 cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer" v-model="form.active">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:w-5 after:h-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Активен</span>
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
