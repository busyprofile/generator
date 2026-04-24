<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  modelValue: [String, Number],
});

const emit = defineEmits(['update:modelValue']);

const searchQuery = ref('');
const paymentGateways = ref([]);
const loading = ref(false);
const showDropdown = ref(false);
const selectedGateway = ref(null);

// Поиск платежных методов
const searchGateways = async () => {
  if (!searchQuery.value.trim() && !props.modelValue) {
    paymentGateways.value = [];
    return;
  }

  loading.value = true;

  try {
    const response = await axios.get('/admin/filters/payment-gateways', {
      params: { query: searchQuery.value }
    });

    paymentGateways.value = response.data;
    showDropdown.value = true;
  } catch (error) {
    console.error('Ошибка при поиске платежных методов:', error);
  } finally {
    loading.value = false;
  }
};

// Дебаунс для поиска
let searchTimeout;
watch(searchQuery, (newVal) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    searchGateways();
  }, 300);
});

// Выбор платежного метода из списка
const selectGateway = (gateway) => {
  selectedGateway.value = gateway;
  searchQuery.value = gateway.label;
  emit('update:modelValue', gateway.value);
  showDropdown.value = false;
};

// Очистка выбора
const clearSelection = () => {
  selectedGateway.value = null;
  searchQuery.value = '';
  emit('update:modelValue', '');
};

// Проверка, если значение приходит из родительского компонента
watch(() => props.modelValue, async (newVal) => {
  if (!newVal) {
    selectedGateway.value = null;
    searchQuery.value = '';
    return;
  }

  if (!selectedGateway.value || selectedGateway.value.value !== newVal) {
    try {
      const response = await axios.get('/admin/filters/payment-gateways', {
        params: { query: newVal }
      });

      if (response.data.length > 0) {
        const gateway = response.data.find(g => g.value == newVal);
        if (gateway) {
          selectedGateway.value = gateway;
          searchQuery.value = gateway.label;
        }
      }
    } catch (error) {
      console.error('Ошибка при загрузке платежного метода:', error);
    }
  }
}, { immediate: true });

// Закрытие выпадающего списка при клике вне компонента
const onClickOutside = (event) => {
  if (!event.target.closest('.payment-gateway-filter')) {
    showDropdown.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', onClickOutside);
});

// Загрузка начального значения, если оно есть
onMounted(async () => {
  if (props.modelValue) {
    try {
      const response = await axios.get('/admin/filters/payment-gateways', {
        params: { query: props.modelValue }
      });

      if (response.data.length > 0) {
        const gateway = response.data.find(g => g.value == props.modelValue);
        if (gateway) {
          selectedGateway.value = gateway;
          searchQuery.value = gateway.label;
        }
      }
    } catch (error) {
      console.error('Ошибка при загрузке платежного метода:', error);
    }
  }
});
</script>

<template>
  <div class="w-full md:w-auto payment-gateway-filter">
    <label for="payment-gateway-filter" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
      Платежный метод
    </label>
    <div class="relative">
      <input
        id="payment-gateway-filter"
        type="text"
        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
        placeholder="Введите название метода..."
        v-model="searchQuery"
        @focus="showDropdown = true"
        @input="showDropdown = true"
      />

      <button
        v-if="selectedGateway"
        type="button"
        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
        @click="clearSelection"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
      </button>

      <!-- Индикатор загрузки -->
      <div v-if="loading" class="absolute inset-y-0 right-0 flex items-center pr-3">
        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>

      <!-- Выпадающий список результатов -->
      <div
        v-if="showDropdown && paymentGateways.length > 0"
        class="absolute z-10 w-full bg-white rounded-lg border border-gray-300 mt-1 dark:bg-gray-700 dark:border-gray-600 shadow-lg"
      >
        <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
          <li
            v-for="gateway in paymentGateways"
            :key="gateway.value"
            class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer"
            @click="selectGateway(gateway)"
          >
            {{ gateway.label }}
          </li>
        </ul>
      </div>

      <!-- Сообщение "Ничего не найдено" -->
      <div
        v-if="showDropdown && searchQuery && !loading && paymentGateways.length === 0"
        class="absolute z-10 w-full bg-white rounded-lg border border-gray-300 mt-1 dark:bg-gray-700 dark:border-gray-600 shadow-lg"
      >
        <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
          Ничего не найдено
        </div>
      </div>
    </div>
  </div>
</template>
