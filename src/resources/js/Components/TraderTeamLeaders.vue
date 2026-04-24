<template>
  <div class="trader-team-leaders">
    <div class="bg-white dark:bg-gray-900 rounded-lg p-3 mb-4 border border-gray-200 dark:border-gray-700 shadow-sm">
      <div class="flex items-center gap-2 mb-4">
       
        <h3 class="text-base font-medium text-gray-800 dark:text-white">Тимлидеры трейдера</h3>
      </div>
      
      <div v-for="(teamLeader, index) in modelValue" :key="index" class="mb-4 relative">
        <div class="grid grid-cols-12 gap-3 items-center sm:grid-cols-12 xs:grid-cols-1">
          <!-- Селектор тимлидера -->
          <div class="col-span-8 xs:col-span-12 sm:mb-0 xs:mb-2">
            <Dropdown 
              :modelValue="teamLeader.team_leader_id"
              @update:modelValue="(value) => updateTeamLeader(index, 'team_leader_id', value)"
              :options="availableTeamLeaders"
              optionLabel="name"
              optionValue="id"
              placeholder="Выберите тимлидера"
              class="w-full"
              :disabled="disabled"
            >
              <template #option="slotProps">
                <div class="text-gray-700 dark:text-gray-200">
                  {{ slotProps.option.name }} ({{ slotProps.option.email }}) - {{ slotProps.option.referral_commission_percentage }}%
                </div>
              </template>
              <template #value="slotProps">
                <div v-if="slotProps.value" class="text-gray-700 dark:text-gray-200 truncate">
                  {{ getTeamLeaderName(slotProps.value) || teamLeader.team_leader_name || `Тимлидер #${teamLeader.team_leader_id}` }}
                </div>
                <div v-else class="text-gray-500 dark:text-gray-400">
                  Выберите тимлидера
                </div>
              </template>
            </Dropdown>
          </div>
          
          <!-- Ввод процента и кнопка удаления в одной строке на мобильных -->
          <div class="col-span-3 xs:col-span-9 flex items-center">
            <InputNumber 
              v-model="teamLeader.commission_percentage"
              @update:modelValue="(value) => updateTeamLeader(index, 'commission_percentage', value)" 
              :min="0" 
              :max="100" 
              :step="0.01"
              mode="decimal" 
              :minFractionDigits="2" 
              :maxFractionDigits="2"
              placeholder="Процент"
              class="w-full"
              :disabled="disabled"
              suffix="%"
            />
          </div>
          
          <!-- Кнопка удаления -->
          <div class="col-span-1 xs:col-span-3 text-right">
            <Button 
              @click="removeTeamLeader(index)"
              icon="pi pi-times"
              class="p-button-rounded p-button-danger p-button-sm"
              :disabled="disabled"
              v-tooltip.top="'Удалить тимлидера'"
            />
          </div>
        </div>
      </div>
      
      <!-- Кнопка добавления -->
      <div class="mt-4">
        <Button 
          @click="addTeamLeader"
          icon="pi pi-plus"
          label="Добавить тимлидера"
          class="p-button-outlined p-button-success w-full"
          :disabled="disabled || isAllTeamLeadersSelected"
        />
      </div>
      
      <small v-if="error" class="p-error block mt-2">{{ error }}</small>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import Tooltip from 'primevue/tooltip';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  availableTeamLeaders: {
    type: Array,
    default: () => [],
  },
  error: {
    type: String,
    default: null,
  },
  disabled: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue']);

// Проверка, все ли доступные тимлидеры уже выбраны
const isAllTeamLeadersSelected = computed(() => {
  return props.availableTeamLeaders.length > 0 && 
         props.modelValue.length >= props.availableTeamLeaders.length;
});

// Получение имени тимлидера по ID с проверками на отсутствие данных
const getTeamLeaderName = (id) => {
  if (!id) return '';
  
  // Пробуем найти в доступных тимлидерах
  const teamLeader = props.availableTeamLeaders.find(tl => tl.id == id);
  if (teamLeader) {
    return `${teamLeader.name}`;
  }
  
  // Проверяем, есть ли имя тимлидера в данных модели
  const teamLeaderInModel = props.modelValue.find(tl => tl.team_leader_id == id);
  if (teamLeaderInModel?.team_leader_name) {
    // Извлекаем только имя до скобки, если оно длинное
    const nameParts = teamLeaderInModel.team_leader_name.split('(');
    if (nameParts.length > 1) {
      return nameParts[0].trim();
    }
    return teamLeaderInModel.team_leader_name;
  }
  
  // Если ничего не нашли, но есть ID, возвращаем его
  if (id) {
    return `Тимлидер #${id}`;
  }
  
  return '';
};

// Метод добавления тимлидера
const addTeamLeader = () => {
  console.log('Добавляем нового тимлидера');
  const newValue = [...props.modelValue];
  
  // Создаем новый объект только с нужными полями без id
  const newTeamLeader = {
    team_leader_id: '', // ID тимлидера
    commission_percentage: 0, // Процент комиссии
    is_primary: true, // Все тимлидеры теперь основные
  };
  
  newValue.push(newTeamLeader);
  console.log('Новый список тимлидеров:', JSON.stringify(newValue));
  
  emit('update:modelValue', newValue);
};

// Метод удаления тимлидера
const removeTeamLeader = (index) => {
  console.log(`Удаление тимлидера, индекс: ${index}`);
  const newValue = [...props.modelValue];
  
  newValue.splice(index, 1);
  
  console.log('Обновленный список тимлидеров после удаления:', JSON.stringify(newValue));
  emit('update:modelValue', newValue);
};

// Метод обновления данных тимлидера
const updateTeamLeader = (index, field, value) => {
  console.log(`Обновляем тимлидера [${index}], поле: ${field}, значение:`, value);
  
  const newValue = [...props.modelValue];
  
  if (field === 'commission_percentage') {
    // PrimeVue InputNumber уже возвращает число, но всё равно проверим
    value = parseFloat(value) || 0;
    // Ограничиваем от 0 до 100
    value = Math.max(0, Math.min(100, value));
  }
  
  // Создаем новый объект с обновленным полем
  newValue[index] = {
    ...newValue[index],
    [field]: value
  };
  
  // Не удаляем team_leader_name, если оно есть, оно может быть нужно для отображения
  if (field === 'team_leader_id' && value) {
    // Если выбран новый тимлидер, обновим его имя из доступных тимлидеров
    const teamLeader = props.availableTeamLeaders.find(tl => tl.id == value);
    if (teamLeader) {
      newValue[index].team_leader_name = `${teamLeader.name}`;
      newValue[index].commission_percentage = teamLeader.referral_commission_percentage;
      console.log(`Установлен процент комиссии: ${newValue[index].commission_percentage}`);
    }
  }
  
  console.log(`Новое значение для тимлидера [${index}]:`, JSON.stringify(newValue[index]));
  
  emit('update:modelValue', newValue);
};

// Проверка, выбран ли уже тимлидер в другой строке
const isTeamLeaderSelected = (id) => {
  return props.modelValue.some(tl => tl.team_leader_id == id);
};

// При монтировании компонента убедимся, что у всех тимлидеров есть имена
onMounted(() => {
  if (props.modelValue.length) {
    const newValue = [...props.modelValue];
    let hasChanges = false;
    
    // Пройдемся по всем тимлидерам и проверим наличие имени
    newValue.forEach((relation, index) => {
      // Если имени нет, но есть ID, и есть доступные тимлидеры, добавим имя
      if (relation.team_leader_id && !relation.team_leader_name && props.availableTeamLeaders.length) {
        const teamLeader = props.availableTeamLeaders.find(tl => tl.id == relation.team_leader_id);
        if (teamLeader) {
          relation.team_leader_name = `${teamLeader.name}`;
          hasChanges = true;
        }
      }
    });
    
    if (hasChanges) {
      emit('update:modelValue', newValue);
    }
  }
});

// Следим за изменениями availableTeamLeaders и обновляем проценты и имена если нужно
watch(
  () => props.availableTeamLeaders,
  (newTeamLeaders) => {
    if (newTeamLeaders.length && props.modelValue.length) {
      const newValue = [...props.modelValue];
      let hasChanges = false;
      
      newValue.forEach((relation, index) => {
        if (relation.team_leader_id) {
          const teamLeader = newTeamLeaders.find(tl => tl.id == relation.team_leader_id);
          if (teamLeader) {
            // Если процент комиссии не был установлен, устанавливаем его
            if (!relation.commission_percentage) {
              relation.commission_percentage = teamLeader.referral_commission_percentage;
              hasChanges = true;
            }
            
            // Если имя тимлидера не было установлено, устанавливаем его
            if (!relation.team_leader_name) {
              relation.team_leader_name = `${teamLeader.name}`;
              hasChanges = true;
            }
          }
        }
      });
      
      if (hasChanges) {
        emit('update:modelValue', newValue);
      }
    }
  },
  { deep: true }
);
</script>

<style scoped>
/* xs — кастомный брейкпоинт, которого нет в Tailwind config */
@media screen and (max-width: 640px) {
  .trader-team-leaders :deep(.p-dropdown) {
    width: 100%;
  }

  .trader-team-leaders :deep(.p-dropdown-panel) {
    width: 100% !important;
    max-width: 90vw !important;
  }

  .xs\:col-span-12 {
    grid-column: span 12 / span 12;
  }

  .xs\:col-span-9 {
    grid-column: span 9 / span 9;
  }

  .xs\:col-span-3 {
    grid-column: span 3 / span 3;
  }

  .xs\:mb-2 {
    margin-bottom: 0.5rem;
  }
}
</style> 