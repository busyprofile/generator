<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryPageSection from '@/Wrappers/SecondaryPageSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Select from '@/Components/Select.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    integrations: {
        type: Array,
        default: () => [],
    },
});

const integrationOptions = computed(() =>
    (props.integrations ?? []).map((i) => ({ id: i.value ?? i.id, name: i.label ?? i.name }))
);

const form = useForm({
    name: '',
    integration: '',
    trader_id: '',
});

const submit = () => {
    form.post(route('admin.providers.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

defineOptions({ layout: AuthenticatedLayout });
</script>

<template>
    <div>
        <Head title="Создание провайдера" />

        <SecondaryPageSection
            :back-link="route('admin.providers.index')"
            title="Создание провайдера"
            description="Добавьте нового провайдера и укажите параметры интеграции."
        >
            <form @submit.prevent="submit" class="mt-6">
                <div class="md:grid md:grid-cols-2 md:gap-x-8">
                    <div>
                        <InputLabel
                            for="name"
                            value="Название"
                            :error="!!form.errors.name"
                        />
                        <TextInput
                            id="name"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autocomplete="off"
                            :error="!!form.errors.name"
                            @input="form.clearErrors('name')"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel
                            for="integration"
                            value="Интеграция"
                            :error="!!form.errors.integration"
                            class="mb-1"
                        />
                        <Select
                            id="integration"
                            v-model="form.integration"
                            :items="integrationOptions"
                            value="id"
                            name="name"
                            default_title="Выберите интеграцию"
                            :error="!!form.errors.integration"
                            @change="form.clearErrors('integration')"
                        />
                        <InputError class="mt-2" :message="form.errors.integration" />
                    </div>

                    <div>
                        <InputLabel
                            for="trader_id"
                            value="Trader ID"
                            :error="!!form.errors.trader_id"
                        />
                        <TextInput
                            id="trader_id"
                            type="number"
                            class="mt-1 block w-full"
                            v-model="form.trader_id"
                            min="1"
                            autocomplete="off"
                            :error="!!form.errors.trader_id"
                            @input="form.clearErrors('trader_id')"
                        />
                        <InputError class="mt-2" :message="form.errors.trader_id" />
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            ID пользователя-трейдера. Сделки открываются только при положительном балансе трейдера.
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 mt-8 md:col-span-2">
                    <PrimaryButton :disabled="form.processing">Сохранить</PrimaryButton>

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
