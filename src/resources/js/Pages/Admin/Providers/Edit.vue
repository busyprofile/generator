<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryPageSection from '@/Wrappers/SecondaryPageSection.vue';
import InputError from '@/Components/InputError.vue';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';

const props = defineProps({
    provider: {
        type: Object,
        default: () => ({}),
    },
    integrations: {
        type: Array,
        default: () => [],
    },
});

const integrationOptions = computed(() =>
    (props.integrations ?? []).map((i) => ({ 
        label: i.label ?? i.name, 
        value: i.value ?? i.id 
    }))
);

const form = useForm({
    name: props.provider?.name ?? '',
    integration: props.provider?.integration ?? '',
    trader_id: props.provider?.trader_id ?? '',
    is_active: props.provider?.is_active ?? false,
});

const submit = () => {
    form.patch(route('admin.providers.update', { provider: props.provider?.id ?? 0 }), {
        preserveScroll: true,
    });
};

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <div>
        <Head :title="`Редактирование провайдера - ${props.provider?.name ?? ''}`" />

        <SecondaryPageSection
            :back-link="route('admin.providers.index')"
            :title="`Редактирование провайдера - ${props.provider?.name ?? ''}`"
            description="Обновите параметры провайдера и интеграции."
        >
            <form @submit.prevent="submit" class="mt-2">
                <div class="md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-6">
                    <div>
                        <label for="name" class="block mt-3 text-sm font-medium text-gray-900 dark:text-gray-300">
                            Название
                        </label>
                        <InputText
                            id="name"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="off"
                            :invalid="!!form.errors.name"
                            @input="form.clearErrors('name')"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <label for="integration" class="block mt-3 text-sm font-medium text-gray-900 dark:text-gray-300">
                            Интеграция
                        </label>
                        <Dropdown
                            id="integration"
                            v-model="form.integration"
                            :options="integrationOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Выберите интеграцию"
                            class="w-full mt-1"
                            disabled
                        />
                        <InputError class="mt-2" :message="form.errors.integration" />
                    </div>

                    <div>
                        <label for="trader_id" class="block mt-3 text-sm font-medium text-gray-900 dark:text-gray-300">
                            Trader ID (User ID)
                        </label>
                        <InputNumber
                            id="trader_id"
                            class="w-full mt-1"
                            v-model="form.trader_id"
                            :min="1"
                            :invalid="!!form.errors.trader_id"
                            @input="form.clearErrors('trader_id')"
                            placeholder="Введите ID пользователя-трейдера"
                        />
                        <InputError class="mt-2" :message="form.errors.trader_id" />
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            ID пользователя, который является владельцем провайдера. Сделки открываются только при положительном балансе трейдера.
                        </div>
                    </div>

                    <div>
                        <label class="block mt-3 text-sm font-medium text-gray-900 dark:text-gray-300">
                            Статус провайдера
                        </label>
                        <Button
                            :label="form.is_active ? 'Активен' : 'Неактивен'"
                            :icon="form.is_active ? 'pi pi-check' : 'pi pi-times'"
                            :severity="form.is_active ? 'success' : 'danger'"
                            @click="form.is_active = !form.is_active"
                            class="p-button-sm w-full mt-1"
                            outlined
                            type="button"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-8 md:col-span-2">
                    <Button 
                        label="Сохранить" 
                        :loading="form.processing" 
                        type="submit" 
                        icon="pi pi-save" 
                    />
                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Сохранено.</p>
                    </Transition>
                </div>
            </form>
        </SecondaryPageSection>
    </div>
</template>

