<template>
    <Head title="Приоритеты категорий трейдеров" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Приоритеты категорий трейдеров
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <!-- Заголовок -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Мерчанты и их приоритеты категорий</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Управляйте приоритетом категорий трейдеров для каждого мерчанта. 
                                Трейдеры будут назначаться на заказы в соответствии с установленными приоритетами.
                            </p>
                        </div>

                        <!-- Поиск мерчантов -->
                        <div class="mb-6">
                            <div class="flex items-center space-x-4">
                                <div class="flex-1">
                                    <TextInput
                                        v-model="search"
                                        placeholder="Поиск мерчантов..."
                                        class="w-full bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
                                        @input="handleSearch"
                                    />
                                </div>
                                <SecondaryButton @click="clearSearch">
                                    Очистить
                                </SecondaryButton>
                            </div>
                        </div>

                        <!-- Таблица мерчантов -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            ID
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Название
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Домен
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Приоритетов настроено
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Последнее обновление
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Действия
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="merchant in merchants.data" :key="merchant.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ merchant.id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ merchant.user?.name || 'Без названия' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ merchant.domain || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                                  :class="merchant.priorities_count > 0 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'">
                                                {{ merchant.priorities_count }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ merchant.last_priority_update || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <PrimaryButton @click="managePriorities(merchant)">
                                                Настроить приоритеты
                                            </PrimaryButton>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Пагинация -->
                        <div class="mt-6">
                            <Pagination :links="merchants.links" />
                        </div>

                        <!-- Модальное окно управления приоритетами -->
                        <Modal :show="showPrioritiesModal" @close="closePrioritiesModal" max-width="2xl">
                            <div class="p-6 bg-white dark:bg-gray-800">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                    Настройка приоритетов для: {{ currentMerchant?.user?.name }}
                                </h3>

                                <!-- Добавление новой категории -->
                                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Добавить категорию</h4>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <InputLabel for="category" value="Категория" />
                                            <select 
                                                id="category"
                                                v-model="newCategoryId"
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                            >
                                                <option value="">Выберите категорию</option>
                                                <option v-for="category in availableCategories" :key="category.id" :value="category.id">
                                                    {{ category.name }} ({{ category.active_traders_count || 0 }} трейдеров)
                                                </option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <InputLabel for="priority" value="Приоритет" />
                                            <TextInput
                                                id="priority"
                                                v-model="newPriority"
                                                type="number"
                                                min="0"
                                                max="100"
                                                class="mt-1 block w-full"
                                            />
                                        </div>
                                        
                                        <div class="flex items-end">
                                            <PrimaryButton @click="addCategory" :disabled="!newCategoryId">
                                                Добавить
                                            </PrimaryButton>
                                        </div>
                                    </div>
                                </div>

                                <!-- Текущие приоритеты -->
                                <div class="mb-6">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Текущие приоритеты</h4>
                                    
                                    <div v-if="currentPriorities.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                        Приоритеты не настроены. Добавьте категории выше.
                                    </div>

                                    <div v-else class="space-y-3 max-h-96 overflow-y-auto">
                                        <TransitionGroup name="priority-list" tag="div">
                                            <div 
                                                v-for="priority in currentPriorities" 
                                                :key="priority.trader_category_id"
                                                class="flex items-center justify-between p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg shadow-sm"
                                            >
                                                <div class="flex items-center space-x-4">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Приоритет:</span>
                                                        <TextInput
                                                            v-model="priority.priority"
                                                            type="number"
                                                            min="0"
                                                            max="100"
                                                            class="w-20 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
                                                            @change="updatePriority(priority)"
                                                        />
                                                    </div>
                                                    <div>
                                                        <h5 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {{ priority.trader_category?.name || 'Неизвестная категория' }}
                                                        </h5>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ priority.active_traders_count || 0 }} активных трейдеров
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-3">
                                                    <DangerButton @click="removeCategory(priority)" size="sm">
                                                        Удалить
                                                    </DangerButton>
                                                </div>
                                            </div>
                                        </TransitionGroup>
                                    </div>
                                </div>

                                <!-- Действия -->
                                <div class="flex justify-between">
                                    <SecondaryButton @click="closePrioritiesModal">
                                        Закрыть
                                    </SecondaryButton>
                                    <PrimaryButton @click="savePriorities" :disabled="saving">
                                        {{ saving ? 'Сохранение...' : 'Сохранить изменения' }}
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
import { Head, router, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import Modal from '@/Components/Modals/Modal.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import Pagination from '@/Components/Pagination/Pagination.vue'
import { debounce } from 'lodash'

const props = defineProps({
    merchants: Object,
    categories: Array,
})

const search = ref('')
const showPrioritiesModal = ref(false)
const currentMerchant = ref(null)
const currentPriorities = ref([])
const newCategoryId = ref('')
const newPriority = ref(0)
const saving = ref(false)

const availableCategories = computed(() => {
    if (!props.categories) return []
    
    const usedCategoryIds = currentPriorities.value.map(p => p.trader_category_id)
    return props.categories.filter(cat => !usedCategoryIds.includes(cat.id))
})

const handleSearch = debounce(() => {
    router.get(route('admin.trader-category-priorities.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    })
}, 300)

const clearSearch = () => {
    search.value = ''
    router.get(route('admin.trader-category-priorities.index'))
}

const managePriorities = async (merchant) => {
    currentMerchant.value = merchant
    showPrioritiesModal.value = true
    
    // Загружаем текущие приоритеты
    try {
        const response = await fetch(route('admin.trader-category-priorities.show', merchant.id), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        const data = await response.json()
        currentPriorities.value = data.assigned_categories || []
    } catch (error) {
        console.error('Ошибка загрузки приоритетов:', error)
        currentPriorities.value = []
    }
}

const addCategory = () => {
    if (!newCategoryId.value) return
    
    const category = props.categories.find(c => c.id == newCategoryId.value)
    if (!category) return
    
    const newPriorityItem = {
        id: category.id,
        trader_category_id: category.id,
        priority: parseInt(newPriority.value) || 0,
        trader_category: category,
        name: category.name,
        active_traders_count: category.active_traders_count || 0
    }
    
    currentPriorities.value.push(newPriorityItem)
    currentPriorities.value.sort((a, b) => a.priority - b.priority)
    
    newCategoryId.value = ''
    newPriority.value = 0
}

const removeCategory = (priority) => {
    const index = currentPriorities.value.findIndex(p => p.trader_category_id === priority.trader_category_id)
    if (index > -1) {
        currentPriorities.value.splice(index, 1)
    }
}

const updatePriority = (priority) => {
    // Пересортировка после изменения приоритета
    currentPriorities.value.sort((a, b) => a.priority - b.priority)
}

const savePriorities = async () => {
    if (!currentMerchant.value) return
    
    saving.value = true
    
    try {
        const categories = currentPriorities.value.map(p => ({
            id: p.trader_category_id,
            priority: parseInt(p.priority) || 0
        }))

        await router.patch(route('admin.trader-category-priorities.update', currentMerchant.value.id), {
            categories: categories
        }, {
            onSuccess: () => {
                showPrioritiesModal.value = false
                // Обновляем данные в таблице
                router.reload({ only: ['merchants'] })
            },
            onError: (errors) => {
                console.error('Ошибка сохранения:', errors)
            }
        })
    } catch (error) {
        console.error('Ошибка при сохранении приоритетов:', error)
    } finally {
        saving.value = false
    }
}

const closePrioritiesModal = () => {
    showPrioritiesModal.value = false
    currentMerchant.value = null
    currentPriorities.value = []
    newCategoryId.value = ''
    newPriority.value = 0
}
</script>

<style scoped>
.priority-list-move,
.priority-list-enter-active,
.priority-list-leave-active {
    transition: all 0.3s ease;
}

.priority-list-enter-from,
.priority-list-leave-to {
    opacity: 0;
    transform: translateX(30px);
}

.priority-list-leave-active {
    position: absolute;
    right: 0;
    left: 0;
}
</style> 