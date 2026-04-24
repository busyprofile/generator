<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MainTableSection from '@/Wrappers/MainTableSection.vue';
import AddMobileIcon from '@/Components/AddMobileIcon.vue';
import FiltersPanel from '@/Components/Filters/FiltersPanel.vue';
import InputFilter from '@/Components/Filters/Pertials/InputFilter.vue';
import FilterCheckbox from '@/Components/Filters/Pertials/FilterCheckbox.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';

const props = defineProps({
    providers: {
        type: Object,
        default: () => ({ data: [] }),
    },
});

const providers = computed(() => props.providers ?? { data: [] });

const toggleActive = (provider) => {
    router.patch(route('admin.providers.toggle', provider.id), {}, { preserveScroll: true });
};

const openEdit = (provider) => {
    router.visit(route('admin.providers.edit', provider.id));
};

const openCreate = () => router.visit(route('admin.providers.create'));

const applyFilters = (payload) => {
    router.get(route('admin.providers.index'), payload, { preserveState: true, replace: true });
};

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <div>
        <Head title="Провайдеры" />

        <MainTableSection
            title="Провайдеры"
            :data="providers"
        >
            <template #button>
                <AddMobileIcon @click="openCreate" />
            </template>

            <template #table-filters>
                <FiltersPanel name="providers" @apply="applyFilters">
                    <InputFilter name="search" placeholder="Поиск по названию" class="w-64" />
                    <FilterCheckbox name="status" title="Активен" />
                </FiltersPanel>
            </template>

            <template #body>
                <div class="grid grid-cols-1 gap-4 relative">
                    <template v-if="providers && providers.data && providers.data.length > 0">
                        <Card v-for="provider in providers.data" :key="provider.id" class="user-card h-full duration-200 overflow-hidden">
                            <template #content>
                                <div class="flex flex-wrap justify-between items-start sm:items-center gap-x-4 gap-y-3">
                                    <div class="flex items-center gap-3 min-w-[340px] flex-grow sm:flex-grow-0">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                <span class="text-base font-semibold text-gray-800 dark:text-gray-200">{{ provider.name }}</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">(ID: {{ provider.id }})</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="w-[150px] text-left sm:w-[160px]">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">
                                            Баланс трейдера
                                        </div>
                                        <div class="text-gray-900 dark:text-gray-100 font-semibold text-lg whitespace-nowrap">
                                            {{ provider.balance }} $
                                        </div>
                                    </div>

                                    <div class="w-[150px] text-left sm:w-[160px]">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Интеграция</div>
                                        <div class="text-gray-900 dark:text-gray-100 font-semibold text-base whitespace-nowrap uppercase">
                                            {{ provider.integration }}
                                        </div>
                                    </div>

                                    <div class="w-[170px] text-left sm:w-[180px]">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Провайдер терминалов</div>
                                        <div class="text-gray-900 dark:text-gray-100 font-semibold text-base whitespace-nowrap">
                                            {{ provider.terminals_count }}
                                        </div>
                                    </div>

                                    <div class="w-[140px] text-left sm:w-[150px]">
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Онлайн</div>
                                        <Button
                                            :label="provider.is_active ? 'Включен' : 'Выключен'"
                                            :icon="provider.is_active ? 'pi pi-check' : 'pi pi-times'"
                                            :severity="provider.is_active ? 'success' : 'danger'"
                                            class="p-button-sm w-full"
                                            @click="toggleActive(provider)"
                                        />
                                    </div>

                                    <div class="w-auto self-center flex items-center justify-end gap-2 ml-auto">
                                        <Button
                                            icon="pi pi-pencil"
                                            label="Редактировать"
                                            size="small"
                                            @click="openEdit(provider)"
                                            severity="secondary"
                                            outlined
                                        />
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </template>
                    <div v-else class="text-center py-10 col-span-full">
                        <i class="pi pi-briefcase text-5xl text-gray-400 dark:text-gray-500 mb-3"></i>
                        <p class="text-lg text-gray-500 dark:text-gray-400">Провайдеры не найдены.</p>
                        <p class="text-sm text-gray-400 dark:text-gray-500">Попробуйте изменить фильтры.</p>
                    </div>
                </div>
            </template>
        </MainTableSection>
    </div>
</template>

<style scoped>
.user-card .p-card-content {
    padding: 0 !important;
}
:deep(.p-card) {
    background-color: var(--surface-card);
    border-radius: 0.5rem;
    transition: all 0.2s;
    border: 1px solid var(--surface-border);
}
:deep(.user-card .p-card-header) {
    padding: 0;
}
:deep(.user-card .p-card-body) {
    padding: 0;
}
:deep(.user-card .p-card-content) {
    padding: 0;
}
</style>
