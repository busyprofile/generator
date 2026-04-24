<script setup>
import { ref, watch, nextTick, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useForm, usePage, router } from '@inertiajs/vue3';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
import ProgressBar from 'primevue/progressbar';
import Toast from 'primevue/toast';
import Dialog from 'primevue/dialog';
import SelectButton from 'primevue/selectbutton';
import axios from 'axios';
import QrcodeVue from 'qrcode.vue';

const props = defineProps({
  balanceType: { type: String, default: 'trust' }
});

const showModal = ref(true);
const qrCodeUrlSum = ref('');
const qrCodeUrlAddress = ref('');
const isLoadingQR = ref(false);
const depositForm = useForm({ amount: '', balanceType: props.balanceType });
const step = ref('form');
const uniqAmount = ref(null);
const toast = useToast();
const emit = defineEmits(['closeModal']);
const platformWallet = usePage().props.platformWallet || '';
const qrTab = ref('addresssum');
const qrTabOptions = [
  { label: 'Адрес+Сумма', value: 'addresssum' },
  { label: 'Адрес', value: 'address' }
];

const qrValueAddressSum = computed(() => `${platformWallet}?amount=${uniqAmount.value}`);

watch(() => props.balanceType, (newVal) => {
  depositForm.balanceType = newVal;
});

const close = () => {
  showModal.value = false;
  emit('closeModal');
};

const reloadPage = () => {
  window.location.reload();
};

const fetchUniqAmount = async () => {
  try {
    const response = await axios.post(route('wallet.get-uniq-amount'), {
      amount: depositForm.amount
    });
    if (response.data.success) {
      uniqAmount.value = response.data.uniq_amount;
    } else {
      toast.add({ severity: 'error', summary: 'Ошибка', detail: response.data.error || 'Не удалось получить уникальную сумму', life: 3000 });
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Ошибка', detail: 'Ошибка при запросе уникальной суммы', life: 3000 });
  }
};

const submit = async () => {
  try {
    isLoadingQR.value = true;
    await fetchUniqAmount();
    if (!uniqAmount.value) {
      toast.add({ severity: 'error', summary: 'Ошибка', detail: 'Уникальная сумма не была получена.', life: 3000 });
      isLoadingQR.value = false;
      return;
    }
    // Только для суммы — запрос на сервер
    const response_qr_sum = await axios.post(
      route('wallet.qrcode'),
      { amount: uniqAmount.value },
      {
        headers: { Accept: 'image/png' },
        responseType: 'blob'
      }
    );
    const blobSum = new Blob([response_qr_sum.data], { type: 'image/png' });
    qrCodeUrlSum.value = URL.createObjectURL(blobSum);
    // Для адреса — ничего не делаем, QR-код генерируется на фронте
    step.value = 'qr';
    isLoadingQR.value = false;
  } catch (e) {
    isLoadingQR.value = false;
    let detail = 'Ошибка при генерации QR-кода';
    if (e.response && e.response.data) {
      if (e.response.data.error) {
        detail = e.response.data.error;
      } else if (typeof e.response.data === 'string') {
        detail = e.response.data;
      }
    }
    toast.add({ severity: 'error', summary: 'Ошибка', detail, life: 4000 });
  }
};

const confirmDeposit = () => {
  step.value = 'waiting';
};

const copyToClipboard = () => {
  navigator.clipboard.writeText(platformWallet)
    .then(() => {
      toast.add({ severity: 'success', summary: 'Скопировано', detail: 'Адрес скопирован в буфер обмена', life: 2000 });
    })
    .catch(() => {
      toast.add({ severity: 'error', summary: 'Ошибка', detail: 'Не удалось скопировать', life: 2000 });
    });
};

const copySumToClipboard = () => {
  if (uniqAmount.value) {
    navigator.clipboard.writeText(uniqAmount.value + ' USDT')
      .then(() => {
        toast.add({ severity: 'success', summary: 'Скопировано', detail: 'Сумма скопирована', life: 2000 });
      })
      .catch(() => {
        toast.add({ severity: 'error', summary: 'Ошибка', detail: 'Не удалось скопировать', life: 2000 });
      });
  }
};

nextTick(() => {
  if (step.value === 'form') {
    const input = document.querySelector('#amount-input');
    if (input) input.focus();
  }
});
</script>

<template>
  <Toast />  
  <Dialog v-model:visible="showModal" modal :closable="false" :dismissableMask="true" class="p-0" :style="{ maxWidth: '470px', width: '95vw' }" @hide="close">
     <template #header> 
    <div class="flex items-center justify-between w-full">
  <div class="flex items-center gap-2">
    <Tag :value="props.balanceType === 'trust' ? 'TRUST' : 'MERCHANT'" severity="info" class="uppercase" />
    <span class="text-lg font-bold text-gray-900 dark:text-white">
      {{ props.balanceType === 'trust' ? 'Пополнение траст баланса' : 'Пополнение мерчант баланса' }}
    </span>
  </div>
  <Button icon="pi pi-times" text rounded severity="danger" @click="close" />
</div>
</template>
    <Card class="max-w-lg mx-auto p-0 rounded-2xl shadow-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 animate-fadein">
    
     
      <template #content>
        <div v-if="step === 'form'">
          <div class="mb-4 text-center text-gray-900 dark:text-gray-200">
            Введите сумму пополнения в USDT и нажмите <b>«Продолжить»</b>
          </div>
          <div class="flex flex-col gap-3 items-center">
            <InputNumber
              id="amount-input"
              v-model="depositForm.amount"
              mode="decimal"
              :minFractionDigits="2"
              :maxFractionDigits="2"
              :min="1"
              placeholder="Сумма в USDT"
              class="w-full max-w-xs"
              inputClass="text-lg py-2 px-4 rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-gray-300"
              autofocus
              showButtons
              buttonLayout="horizontal"
              incrementButtonIcon="pi pi-plus"
              decrementButtonIcon="pi pi-minus"
            />
            <Button
              label="Продолжить"
              icon="pi pi-arrow-right"
              class="w-full max-w-xs mt-2"
              size="large"
              @click="submit"
              :loading="isLoadingQR"
              severity="success"
            />
          </div>
          <Divider class="my-4" />
          <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
            Если резерв меньше 1000 USDT, то часть депозита зачислится в резерв.
          </div>
        </div>
        <div v-else-if="step === 'qr'">
          <div class="flex justify-center mb-4">
            <SelectButton :options="qrTabOptions" v-model="qrTab" optionLabel="label" optionValue="value" class="bg-transparent border-0 select-button-custom" />
          </div>
         
          <div class="flex flex-col items-center gap-3">
            <div v-if="isLoadingQR" class="p-2 w-[200px] h-[200px] flex items-center justify-center">
              <ProgressBar mode="indeterminate" style="height: 6px; width: 100%;" />
            </div>
            <img v-if="qrTab === 'sum'" :src="qrCodeUrlSum" alt="QR Code" class="p-2 max-w-[200px] rounded-xl border border-gray-300 dark:border-gray-700 shadow-lg animate-fadein" />
            <QrcodeVue v-else-if="qrTab === 'address'" :value="platformWallet" :size="200" :level="'M'" class="p-2 max-w-[200px] rounded-xl border border-gray-300 dark:border-gray-700 shadow-lg animate-fadein" />
            <QrcodeVue v-else :value="qrValueAddressSum" :size="200" :level="'M'" class="p-2 max-w-[200px] rounded-xl border border-gray-300 dark:border-gray-700 shadow-lg animate-fadein" />
              <div class=  "text-center text-gray-900 dark:text-gray-200 text-sm font-semibold">
            Отсканируйте QR-код для пополнения
          </div>
            <div class="border border-gray-300 dark:border-gray-700  flex items-center gap-2 px-3 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 select-all mt-2 mb-1 w-full justify-between">
              <!-- <span class="text-gray-500 dark:text-gray-400">Сумма:</span> -->
              <span class="font-bold text-primary text-lg">{{ uniqAmount }} USDT</span>
              <Button icon="pi pi-copy" text rounded size="small" class="ml-1 opacity-70 hover:opacity-100" @click.stop="copySumToClipboard" />
            </div>
            <div class="border border-gray-300 dark:border-gray-700  flex items-center gap-2 px-3 py-2 rounded-xl bg-gray-100 dark:bg-gray-800   transition    select-all w-full   justify-between"
              @click="copyToClipboard"
              title="Скопировать адрес">
              <span class="font-mono text-sm   truncate ">
                {{ platformWallet }}
              </span>
              <Button
                icon="pi pi-copy"
                text
                rounded
                size="small"
                class="ml-1 opacity-70 group-hover:opacity-100"
                @click.stop="copyToClipboard"
              />
            </div>
          </div>
          <Button label="Я оплатил" icon="pi pi-check" class="w-full   mt-4" size="large" severity="success" @click="confirmDeposit" />
        </div>
        <div v-else-if="step === 'waiting'">
          <div class="flex flex-col items-center gap-4 py-8">
              <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
            <div class="text-center text-gray-900 dark:text-gray-200 text-lg font-semibold">Ожидайте пополнение личного баланса</div>
            <div class="text-gray-400">Ваш запрос на пополнение обрабатывается. Это может занять несколько минут.</div>
            <Button label="Закрыть" icon="pi pi-times" class="w-full max-w-xs mt-4" size="large" severity="danger" @click="reloadPage" />
          </div>
        </div>
      </template>
    </Card>
  </Dialog>
</template>

<style scoped>
.select-button-custom .p-button.p-highlight {
  background: #23272f !important;
  color: #fff !important;
  border-radius: 0.7rem !important;
  font-weight: 600;
}
.select-button-custom .p-button {
  background: transparent !important;
  color: #bdbdbd !important;
  border: none !important;
  border-radius: 0.7rem !important;
  font-weight: 500;
  box-shadow: none !important;
}
.select-button-custom .p-button:focus {
      box-shadow: 0 0 0 2px color-mix(in srgb, var(--primary-color) 20%, transparent) !important;
}
.wallet-address-block {
  user-select: all;
  border: 1px solid transparent;
  transition: background 0.2s, border 0.2s;
}
.wallet-address-block:active {
  border-color: var(--primary-color);
  background: color-mix(in srgb, var(--primary-color) 8%, transparent);
}
.dark .wallet-address-block:active {
  border-color: var(--primary-color);
  background: color-mix(in srgb, var(--primary-color) 8%, transparent);
}
.wallet-sum-block {
  user-select: all;
  border: 1px solid transparent;
  transition: background 0.2s, border 0.2s;
}
.wallet-sum-block:active {
  border-color: var(--primary-color);
  background: color-mix(in srgb, var(--primary-color) 8%, transparent);
}
.dark .wallet-sum-block:active {
  border-color: var(--primary-color);
  background: color-mix(in srgb, var(--primary-color) 8%, transparent);
}
.animate-fadein {
  animation: fadein 0.5s;
}
@keyframes fadein {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: none; }
}
</style>
