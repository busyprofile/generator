<template>
    <Head title="Категории трейдеров" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Категории трейдеров
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <!-- Заголовок и кнопка создания -->
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Список категорий трейдеров</h3>
                            <PrimaryButton @click="showCreateModal = true">
                                Создать категорию
                            </PrimaryButton>
                        </div>

                        <!-- Таблица категорий -->
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
                                            Slug
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Описание
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Трейдеров
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Активных
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Статус
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Действия
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="category in categories.data" :key="category.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ category.id }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ category.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ category.slug }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                            {{ category.description || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ category.traders_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ category.active_traders_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="category.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'" 
                                                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                                {{ category.is_active ? 'Активна' : 'Неактивна' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <SecondaryButton @click="editCategory(category)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                Редактировать
                                            </SecondaryButton>
                                            <SecondaryButton @click="manageTraders(category)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                Трейдеры
                                            </SecondaryButton>
                                            <DangerButton @click="deleteCategory(category)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                Удалить
                                            </DangerButton>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Пагинация -->
                        <div class="mt-6">
                            <Pagination :links="categories.links" />
                        </div>

                        <!-- Модальное окно создания категории -->
                        <Modal :show="showCreateModal" @close="showCreateModal = false">
                            <div class="p-6 bg-white dark:bg-gray-800">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Создать новую категорию</h3>
                                
                                <form @submit.prevent="createCategory">
                                    <div class="mb-4">
                                        <InputLabel for="name" value="Название" class="text-gray-700 dark:text-gray-300" />
                                        <TextInput
                                            id="name"
                                            v-model="createForm.name"
                                            type="text"
                                            class="mt-1 block w-full bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
                                            required
                                        />
                                        <InputError :message="createForm.errors.name" class="mt-2" />
                                    </div>

                                    <div class="mb-4">
                                        <InputLabel for="description" value="Описание" class="text-gray-700 dark:text-gray-300" />
                                        <textarea
                                            id="description"
                                            v-model="createForm.description"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            rows="3"
                                        ></textarea>
                                        <InputError :message="createForm.errors.description" class="mt-2" />
                                    </div>

                                    <div class="mb-4">
                                        <label class="flex items-center">
                                            <input
                                                type="checkbox"
                                                v-model="createForm.is_active"
                                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 bg-white dark:bg-gray-700"
                                            />
                                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Активна</span>
                                        </label>
                                    </div>

                                    <div class="flex justify-end space-x-3">
                                        <SecondaryButton @click="showCreateModal = false">
                                            Отмена
                                        </SecondaryButton>
                                        <PrimaryButton type="submit" :disabled="createForm.processing">
                                            Создать
                                        </PrimaryButton>
                                    </div>
                                </form>
                            </div>
                        </Modal>

                        <!-- Модальное окно редактирования категории -->
                        <Modal :show="showEditModal" @close="showEditModal = false">
                            <div class="p-6 bg-white dark:bg-gray-800">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Редактировать категорию</h3>
                                
                                <form @submit.prevent="updateCategory">
                                    <div class="mb-4">
                                        <InputLabel for="edit_name" value="Название" class="text-gray-700 dark:text-gray-300" />
                                        <TextInput
                                            id="edit_name"
                                            v-model="editForm.name"
                                            type="text"
                                            class="mt-1 block w-full bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
                                            required
                                        />
                                        <InputError :message="editForm.errors.name" class="mt-2" />
                                    </div>

                                    <div class="mb-4">
                                        <InputLabel for="edit_description" value="Описание" class="text-gray-700 dark:text-gray-300" />
                                        <textarea
                                            id="edit_description"
                                            v-model="editForm.description"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            rows="3"
                                        ></textarea>
                                        <InputError :message="editForm.errors.description" class="mt-2" />
                                    </div>

                                    <div class="mb-4">
                                        <label class="flex items-center">
                                            <input
                                                type="checkbox"
                                                v-model="editForm.is_active"
                                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 bg-white dark:bg-gray-700"
                                            />
                                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Активна</span>
                                        </label>
                                    </div>

                                    <div class="flex justify-end space-x-3">
                                        <SecondaryButton @click="showEditModal = false">
                                            Отмена
                                        </SecondaryButton>
                                        <PrimaryButton type="submit" :disabled="editForm.processing">
                                            Сохранить
                                        </PrimaryButton>
                                    </div>
                                </form>
                            </div>
                        </Modal>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import Modal from '@/Components/Modals/Modal.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import Pagination from '@/Components/Pagination/Pagination.vue'

const props = defineProps({
    categories: Object,
})

const showCreateModal = ref(false)
const showEditModal = ref(false)
const currentCategory = ref(null)

const createForm = useForm({
    name: '',
    description: '',
    is_active: true,
})

const editForm = useForm({
    name: '',
    description: '',
    is_active: true,
})

const createCategory = () => {
    createForm.post(route('admin.trader-categories.store'), {
        onSuccess: () => {
            showCreateModal.value = false
            createForm.reset()
        }
    })
}

const editCategory = (category) => {
    currentCategory.value = category
    editForm.name = category.name
    editForm.description = category.description || ''
    editForm.is_active = category.is_active
    showEditModal.value = true
}

const updateCategory = () => {
    editForm.patch(route('admin.trader-categories.update', currentCategory.value.id), {
        onSuccess: () => {
            showEditModal.value = false
            editForm.reset()
            currentCategory.value = null
        }
    })
}

const deleteCategory = (category) => {
    if (confirm(`Вы уверены, что хотите удалить категорию "${category.name}"?`)) {
        router.delete(route('admin.trader-categories.destroy', category.id))
    }
}

const manageTraders = (category) => {
    router.get(route('admin.trader-categories.manage-traders', category.id))
}
</script> 