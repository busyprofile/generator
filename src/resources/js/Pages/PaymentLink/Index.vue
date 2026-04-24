<script setup>
import {Head, router, usePage} from '@inertiajs/vue3';
import PaymentLayout from "@/Layouts/PaymentLayout.vue";
import {nextTick, onMounted, ref, computed, watch, watchEffect} from "vue";
import {initFlowbite} from "flowbite";
import SupportButton from "@/Pages/PaymentLink/Components/SupportButton.vue";
import Clock from "@/Components/Clock.vue";
import ColorThemeSwitcher from "@/Pages/PaymentLink/Components/ColorThemeSwitcher.vue";
import StageSwitcher from "@/Pages/PaymentLink/Components/StageSwitcher.vue";
import MerchantName from "@/Pages/PaymentLink/Components/MerchantName.vue";
import PaymentHeader from "@/Pages/PaymentLink/Components/PaymentHeader.vue";
import HelperModal from "@/Pages/PaymentLink/Components/HelperModal.vue";
import SelectGateway from "@/Pages/PaymentLink/Components/Stages/SelectGateway.vue";
import Payment from "@/Pages/PaymentLink/Components/Stages/Payment.vue";
import SuccessOrFail from "@/Pages/PaymentLink/Components/Stages/SuccessOrFail.vue";
import DisputeReview from "@/Pages/PaymentLink/Components/Stages/DisputeReview.vue";
import DisputeCanceled from "@/Pages/PaymentLink/Components/Stages/DisputeCanceled.vue";
import { useBankDeeplinks } from '@/Composables/useBankDeeplinks';
import QrcodeVue from 'qrcode.vue';
import Card from 'primevue/card';
import Divider from "primevue/divider";
defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const stage = ref('payment');
const clockRef = ref(null);
const data = ref({});

const pageData = computed(() => usePage().props.data);

const { availableBanks, generateDeeplinkForBank } = useBankDeeplinks(pageData);

watchEffect(() => {
  if (pageData.value && pageData.value.uuid) {
    console.log('[watchEffect] Текущий stage:', stage.value);
    console.log('[watchEffect] Данные страницы (pageData):', JSON.parse(JSON.stringify(pageData.value)));
    console.log('[watchEffect] Платежный шлюз из pageData:', pageData.value?.payment_gateway);
    console.log('[watchEffect] Доступные банки (из composable):', availableBanks.value);
    availableBanks.value.forEach(bank => {
      console.log(`[watchEffect] Диплинк для ${bank.name}:`, generateDeeplinkForBank(bank.key));
    });
  } else {
    console.log('[watchEffect] pageData еще не загружены или не содержат uuid');
  }
});

const initializeClock = () => {
    nextTick(() => {
        if (clockRef.value && typeof clockRef.value.initializeClock === 'function') {
             clockRef.value.initializeClock();
        }
    });
}

const setData = () => {
    const sourceData = usePage().props.data;
    if (sourceData) {
        data.value = {
            order_status: sourceData.order_status,
            uuid: sourceData.uuid,
            name: sourceData.name,
            amount: sourceData.amount,
            amount_formated: sourceData.amount_formated,
            currency_symbol: sourceData.currency_symbol,
            support_link: sourceData.support_link,
            detail: sourceData.detail,
            detail_type: sourceData.detail_type,
            initials: sourceData.initials,
            payment_gateway: sourceData.payment_gateway,
            payment_gateway_logo_path: sourceData.payment_gateway_logo_path,
            success_url: sourceData.success_url,
            fail_url: sourceData.fail_url,
            created_at: sourceData.created_at,
            expires_at: sourceData.expires_at,
            now: sourceData.now,
            has_dispute: sourceData.has_dispute,
            dispute_status: sourceData.dispute_status,
            dispute_cancel_reason: sourceData.dispute_cancel_reason,
            manually: sourceData.manually,
            gateway_selected: sourceData.gateway_selected,
            available_gateways: sourceData.available_gateways,
        };
    }
}

const checkPaid = () => {
    setInterval(async () => {
        router.reload({ only: ['data'] })
    }, 5000);
}

const setStage = () => {
    const currentOrderData = data.value;

    if (!currentOrderData.gateway_selected) {
        stage.value = 'select_gateway';
    } else  if (currentOrderData.order_status === 'pending' && !currentOrderData.has_dispute) {
        stage.value = 'payment';
    } else if (currentOrderData.order_status === 'success') {
        stage.value = 'success';
    } else if (currentOrderData.order_status === 'fail' && !currentOrderData.has_dispute) {
        stage.value = 'fail';
    } else if (currentOrderData.has_dispute && currentOrderData.dispute_status === 'pending') {
        stage.value = 'dispute_review';
    } else if (currentOrderData.has_dispute  && currentOrderData.dispute_status === 'canceled') {
        stage.value = 'dispute_canceled';
    }
}

const showQrCode = ref(false);
const qrCodeUrl = ref(null);
const isDesktop = computed(() => !/Mobi|Android/i.test(navigator.userAgent));

const attemptOpenSpecificDeeplink = (bankKey) => {
  const url = generateDeeplinkForBank(bankKey);
  if (!url) {
    console.error(`Не удалось сгенерировать диплинк для банка: ${bankKey}`);
    return;
  }

  console.log(`Генерируемый URL для ${bankKey}: ${url}`);

  if (isDesktop.value) {
    qrCodeUrl.value = url;
    showQrCode.value = true; 
    console.log(`Показ QR-кода для ${bankKey} с URL: ${qrCodeUrl.value}`);
  } else {
    window.location.href = url;
  }
};

watch(stage, (newStage) => {
    if (newStage !== 'payment') {
        showQrCode.value = false;
        qrCodeUrl.value = null;
    }
});

setData();
setStage();

router.on('success', (event) => {
    setData();
    setStage();
})

onMounted(() => {
    initFlowbite();

    setTimeout(() => {
        checkPaid();
    }, 5000)

    if (data.value.gateway_selected) {
        initializeClock();
    }
})

defineOptions({ layout: PaymentLayout });
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
        <Head title="Платеж" />

        <div
            class="w-full m-8"
            :class="stage === 'select_gateway' ? 'sm:max-w-lg' : 'sm:max-w-md'"
        >
            <div class="flex justify-between items-center px-2 sm:px-0">
                <div class="flex items-left flex-col ">
                    <img src="/images/dark.png" alt="Logo" class="h-8 w-auto block dark:hidden">
                    <img src="/images/light.png" alt="Logo" class="h-8 w-auto hidden dark:block">
                    
              
                </div>
               
                <div class="flex   items-center"> 

 
                <ColorThemeSwitcher/>
         

                    <SupportButton :support_link="data.support_link"/>
                     
                    
                </div>
            </div>

            <PaymentHeader :stage="stage" :data="data">
                <template v-slot:clock>
                    <Clock :expires_at="data.expires_at" :now="data.now" ref="clockRef"/>
                </template>
            </PaymentHeader>

            <div class="sm:mx-0 mx-2 mt-4 sm:px-6 px-3 py-4 bg-white dark:bg-gray-800 overflow-hidden rounded-plate border border-gray-200 dark:border-gray-700 rounded-lg">
                <div>
                    <SelectGateway
                        v-if="stage === 'select_gateway'"
                        :data="data"
                        @selected="initializeClock"
                    />

                    <Payment
                        v-show="stage === 'payment'"
                        :data="data"
                    />

                    <SuccessOrFail
                        v-if="stage === 'success' || stage === 'fail'"
                        :stage="stage"
                        :data="data"
                    />

                    <DisputeReview
                        v-if="stage === 'dispute_review'"
                    />

                    <DisputeCanceled
                        v-if="stage === 'dispute_canceled'"
                        :data="data"
                    />

                    <HelperModal :data="data"/>
                </div>
            </div>
           <div class="flex items-center flex-col gap-1 mt-2"> 
 
  <MerchantName :name="data.name"/>
    <span class="text-sm text-gray-500 dark:text-gray-500">{{ data.uuid }}</span>
</div>

 
         
        </div>
    </div>
</template>


<style scoped>
 .rounded-lg {
    border-radius: 12px !important;
}
</style>
