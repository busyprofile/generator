<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  modelValue: String,
});

const emit = defineEmits(['update:modelValue']);

const detailTypes = ref([]);
const loading = ref(true);

onMounted(async () => {
  try {
    const response = await axios.get('/admin/filters/detail-types');
    detailTypes.value = response.data;
    loading.value = false;
  } catch (error) {
    console.error('Ошибка при загрузке типов реквизитов:', error);
    loading.value = false;
  }
});

const updateValue = (event) => {
  emit('update:modelValue', event.target.value);
};
</script>

<template>
  <div class="w-full md:w-auto">
    <label for="detail-type-filter" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
      Тип реквизита
    </label>
    <select
      id="detail-type-filter"
      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
      :value="modelValue"
      @change="updateValue"
      :disabled="loading"
    >
      <option value="">Все типы</option>
      <option v-for="type in detailTypes" :key="type.value" :value="type.value">
        {{ type.label }}
      </option>
    </select>
  </div>
</template> 