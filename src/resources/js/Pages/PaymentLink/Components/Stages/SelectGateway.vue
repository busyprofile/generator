<script setup>
import GatewayLogo from "@/Components/GatewayLogo.vue";
import MainButton from "@/Pages/PaymentLink/Components/MainButton.vue";
import {useForm} from "@inertiajs/vue3";
import {ref} from "vue";

const props = defineProps({
    data: {
        type: Object,
        default: {}
    },
});

const emit = defineEmits(['selected']);

const formGatewaySelect = useForm({});

const submitGatewaySelect = () => {
    formGatewaySelect.post(route('payment.payment-detail.store', {
        'order': props.data.uuid,
        'paymentGateway': selectedGateway.value,
    }), {
        onSuccess: result => {
            selected();
        },
    })
}

const selected = () => {
    emit('selected');
};

const selectedGateway = ref(null);
</script>

<template>
    <div>
        <div
            v-if="! data.available_gateways.length"
            class="py-5 flex items-center justify-center sm:text-xl text-xl text-gray-900 dark:text-gray-200 sm:mb-0 mb-3"
        >
            Доступные методы оплаты не найдены.
        </div>

        <template v-else>
            <div v-show="$page.props.flash.message && ! formGatewaySelect.processing" class="flex items-center p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-xl  bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                <svg class="flex-shrink-0 inline w-4 h-4 me-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                </svg>
                <div>
                    {{ $page.props.flash.message }}
                </div>
            </div>
            <div class="relative sm:my-5 sm:text-base text-sm grid sm:grid-cols-3 grid-cols-2 gap-4 text-center">
                <div v-show="formGatewaySelect.processing" role="status" class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2 z-20">
                    <svg aria-hidden="true" class="w-10 h-10 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                    <span class="sr-only">Loading...</span>
                </div>
                <div v-show="formGatewaySelect.processing" class="absolute w-full h-full bg-gray-200/90  dark:bg-gray-800/90 rounded-xl z-10"></div>
                <div
                    v-for="gateway in data.available_gateways"
                    class="relative text-gray-900 dark:text-gray-200 border border-gray-200 dark:border-gray-600 rounded-plate cursor-pointer hover:border-blue-500/70 hover:dark:border-blue-400/70"
                    @click="selectedGateway = gateway.id"
                    :class="selectedGateway === gateway.id ? 'border border-blue-500/70 dark:border-blue-400/70' : ''"
                >
                    <div v-if="selectedGateway === gateway.id" class="absolute top-1 right-1">
                        <svg class="w-6 h-6 text-blue-500 dark:text-blue-400/70" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11.917 9.724 16.5 19 7.5"/>
                        </svg>
                    </div>
                    <div class="mx-3 my-4">
                        <div class="flex justify-center">
                            <GatewayLogo
                                :img_path="gateway.logo_path"
                                class="w-14 h-14 text-gray-400 dark:text-gray-500"
                            />
                        </div>
                        <div class="text-sm truncate mt-3">
                            {{gateway.name}}
                        </div>
<!--                        <div class="text-gray-400 dark:text-gray-500 text-xs">
                            Комиссия: {{ gateway.commission }}%
                        </div>-->
                    </div>
                </div>
            </div>

            <div class="mt-5 sm:pb-3">
                <MainButton
                    text="Выбрать"
                    :disabled="! selectedGateway || formGatewaySelect.processing"
                    @click.prevent="submitGatewaySelect"
                />
            </div>
        </template>
    </div>
</template>

<style scoped>

</style>
