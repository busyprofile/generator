<script setup>
import { useViewStore } from "@/store/view.js";
import { onMounted, ref } from "vue";
import { router } from "@inertiajs/vue3";
import Select from 'primevue/select';

const viewStore = useViewStore();

// Создаем массив доступных режимов просмотра
const viewModes = ref([
    { name: 'Админ', value: 'admin' },
    { name: 'Трейдер', value: 'trader' },
    { name: 'Мерчант', value: 'merchant' },
    { name: 'Тимлидер', value: 'leader' },
    { name: 'Саппорт', value: 'support' }
]);

// Выбранный режим просмотра
const selectedViewMode = ref(viewStore.viewMode);

// При изменении режима просмотра
const onViewModeChange = () => {
    // Обновляем значение в store
    viewStore.viewMode = selectedViewMode.value;
    
    // Перенаправляем на соответствующую страницу
    visitDefaultPage();
};

// Функция для перехода на главную страницу выбранного режима
const visitDefaultPage = () => {
    if (viewStore.viewMode === 'admin') {
        router.visit(route('admin.main.index'), {
            preserveScroll: true
        });
    }
    if (viewStore.viewMode === 'trader') {
        router.visit(route('trader.main.index'), {
            preserveScroll: true
        });
    }
    if (viewStore.viewMode === 'merchant') {
        router.visit(route('merchant.main.index'), {
            preserveScroll: true
        });
    }
    if (viewStore.viewMode === 'leader') {
        router.visit(route('leader.main.index'), {
            preserveScroll: true
        });
    }
    if (viewStore.viewMode === 'support') {
        router.visit(route('support.users.index'), {
            preserveScroll: true
        });
    }
};

// Инициализация выбранного режима после загрузки компонента
onMounted(() => {
    selectedViewMode.value = viewStore.viewMode;
});
</script>

<template>
    <div class="card">
       
        <Select 
            v-model="selectedViewMode" 
            :options="viewModes" 
            optionLabel="name"
            optionValue="value"
            placeholder="Выберите режим"
            class="w-full"
            @change="onViewModeChange"
        />
    </div>
</template>
