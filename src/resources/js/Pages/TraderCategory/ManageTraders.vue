<template>
    <Head :title="`Трейдеры категории: ${category.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Трейдеры категории: {{ category.name }}
                </h2>
                <SecondaryButton @click="$inertia.visit(route('admin.trader-categories.index'))">
                    Назад к категориям
                </SecondaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <!-- Информация о категории -->
                        <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ category.name }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ category.description || 'Описание не указано' }}
                            </p>
                            <div class="mt-2 flex items-center space-x-4">
                                <span class="text-sm">
                                    <span class="font-medium">Всего трейдеров:</span> {{ tradersInCategory.length }}
                                </span>
                                <span :class="category.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'" 
                                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                    {{ category.is_active ? 'Активна' : 'Неактивна' }}
                                </span>
                            </div>
                        </div>

                        <!-- Два столбца: доступные трейдеры и трейдеры в категории -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Доступные трейдеры -->
                            <div>
                                <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">
                                    Доступные трейдеры ({{ filteredAvailableTraders.length }})
                                </h4>
                                
                                <!-- Поиск среди доступных -->
                                <div class="mb-4">
                                    <TextInput
                                        v-model="availableSearch"
                                        placeholder="Поиск по имени или email..."
                                        class="w-full bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
                                    />
                                </div>

                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg max-h-96 overflow-y-auto">
                                    <div v-if="filteredAvailableTraders.length === 0" class="p-4 text-center text-gray-500 dark:text-gray-400">
                                        Нет доступных трейдеров
                                    </div>
                                    <div v-else>
                                        <div v-for="trader in filteredAvailableTraders" :key="trader.id" 
                                             class="flex items-center justify-between p-3 border-b border-gray-200 dark:border-gray-600 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ trader.name }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ trader.email }}</div>
                                            </div>
                                            <PrimaryButton @click="addTrader(trader)" size="sm" :disabled="loading">
                                                Добавить
                                            </PrimaryButton>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Трейдеры в категории -->
                            <div>
                                <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">
                                    Трейдеры в категории ({{ filteredCategoryTraders.length }})
                                </h4>
                                
                                <!-- Поиск среди трейдеров в категории -->
                                <div class="mb-4">
                                    <TextInput
                                        v-model="categorySearch"
                                        placeholder="Поиск по имени или email..."
                                        class="w-full bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
                                    />
                                </div>

                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg max-h-96 overflow-y-auto">
                                    <div v-if="filteredCategoryTraders.length === 0" class="p-4 text-center text-gray-500 dark:text-gray-400">
                                        В категории нет трейдеров
                                    </div>
                                    <div v-else>
                                        <div v-for="trader in filteredCategoryTraders" :key="trader.id" 
                                             class="flex items-center justify-between p-3 border-b border-gray-200 dark:border-gray-600 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-gray-100">{{ trader.name }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ trader.email }}</div>
                                            </div>
                                            <DangerButton @click="removeTrader(trader)" size="sm" :disabled="loading">
                                                Удалить
                                            </DangerButton>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Массовые действия -->
                        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">Массовые действия</h4>
                            <div class="flex space-x-3">
                                <SecondaryButton @click="showBulkAddModal = true" :disabled="availableTraders.length === 0">
                                    Массово добавить
                                </SecondaryButton>
                                <DangerButton @click="removeAllTraders" :disabled="tradersInCategory.length === 0 || loading">
                                    Очистить категорию
                                </DangerButton>
                            </div>
                        </div>

                        <!-- Модальное окно массового добавления -->
                        <Modal :show="showBulkAddModal" @close="showBulkAddModal = false" max-width="2xl">
                            <div class="p-6 bg-white dark:bg-gray-800">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Массовое добавление трейдеров</h3>
                                
                                <div class="mb-4">
                                    <div class="flex items-center mb-2">
                                        <input
                                            type="checkbox"
                                            v-model="selectAll"
                                            @change="toggleSelectAll"
                                            class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 bg-white dark:bg-gray-700"
                                        />
                                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Выбрать всех</span>
                                    </div>
                                </div>
                                
                                <div class="max-h-64 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
                                    <div v-for="trader in availableTraders" :key="trader.id" 
                                         class="flex items-center p-3 border-b border-gray-200 dark:border-gray-600 last:border-b-0">
                                        <input
                                            type="checkbox"
                                            v-model="selectedTraders"
                                            :value="trader.id"
                                            class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 bg-white dark:bg-gray-700"
                                        />
                                        <div class="ml-3">
                                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ trader.name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ trader.email }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end space-x-3 mt-6">
                                    <SecondaryButton @click="showBulkAddModal = false">
                                        Отмена
                                    </SecondaryButton>
                                    <PrimaryButton @click="bulkAddTraders" :disabled="selectedTraders.length === 0 || loading">
                                        Добавить {{ selectedTraders.length }} трейдеров
                                    </PrimaryButton>
                                </div>
                            </div>
                        </Modal>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import Modal from '@/Components/Modals/Modal.vue'
import TextInput from '@/Components/TextInput.vue'

const props = defineProps({
    category: Object,
    tradersInCategory: Array,
    availableTraders: Array,
})

const loading = ref(false)
const availableSearch = ref('')
const categorySearch = ref('')
const showBulkAddModal = ref(false)
const selectedTraders = ref([])
const selectAll = ref(false)

// Фильтрация доступных трейдеров
const filteredAvailableTraders = computed(() => {
    if (!availableSearch.value) return props.availableTraders
    
    const search = availableSearch.value.toLowerCase()
    return props.availableTraders.filter(trader => 
        trader.name.toLowerCase().includes(search) || 
        trader.email.toLowerCase().includes(search)
    )
})

// Фильтрация трейдеров в категории
const filteredCategoryTraders = computed(() => {
    if (!categorySearch.value) return props.tradersInCategory
    
    const search = categorySearch.value.toLowerCase()
    return props.tradersInCategory.filter(trader => 
        trader.name.toLowerCase().includes(search) || 
        trader.email.toLowerCase().includes(search)
    )
})

// Переключение выбора всех
const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedTraders.value = props.availableTraders.map(trader => trader.id)
    } else {
        selectedTraders.value = []
    }
}

// Отслеживание изменений в выбранных трейдерах
watch(selectedTraders, (newVal) => {
    selectAll.value = newVal.length === props.availableTraders.length
}, { deep: true })

// Добавить одного трейдера
const addTrader = async (trader) => {
    if (loading.value) return // Предотвращаем двойные клики
    
    loading.value = true
    
    try {
        await router.post(route('admin.trader-categories.add-trader', props.category.id), {
            trader_id: trader.id
        }, {
            preserveScroll: true,
            onFinish: () => {
                loading.value = false
            }
        })
    } catch (error) {
        loading.value = false
        console.error('Ошибка при добавлении трейдера:', error)
    }
}

// Удалить одного трейдера
const removeTrader = async (trader) => {
    if (!confirm(`Вы уверены, что хотите удалить трейдера "${trader.name}" из категории?`)) {
        return
    }
    
    if (loading.value) return // Предотвращаем двойные клики
    
    loading.value = true
    
    try {
        await router.delete(route('admin.trader-categories.remove-trader', props.category.id), {
            data: {
                trader_id: trader.id
            },
            preserveScroll: true,
            onFinish: () => {
                loading.value = false
            }
        })
    } catch (error) {
        loading.value = false
        console.error('Ошибка при удалении трейдера:', error)
    }
}

// Массовое добавление
const bulkAddTraders = async () => {
    if (loading.value) return // Предотвращаем двойные клики
    
    loading.value = true
    
    try {
        await router.post(route('admin.trader-categories.bulk-add-traders', props.category.id), {
            trader_ids: selectedTraders.value
        }, {
            preserveScroll: true,
            onSuccess: () => {
                showBulkAddModal.value = false
                selectedTraders.value = []
                selectAll.value = false
            },
            onFinish: () => {
                loading.value = false
            }
        })
    } catch (error) {
        loading.value = false
        console.error('Ошибка при массовом добавлении:', error)
    }
}

// Очистить категорию
const removeAllTraders = async () => {
    if (!confirm(`Вы уверены, что хотите удалить всех трейдеров из категории "${props.category.name}"?`)) {
        return
    }
    
    if (loading.value) return // Предотвращаем двойные клики
    
    loading.value = true
    
    try {
        await router.delete(route('admin.trader-categories.remove-all-traders', props.category.id), {
            preserveScroll: true,
            onFinish: () => {
                loading.value = false
            }
        })
    } catch (error) {
        loading.value = false
        console.error('Ошибка при удалении всех трейдеров:', error)
    }
}
</script> 