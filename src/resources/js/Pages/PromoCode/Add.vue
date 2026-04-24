<script setup>
import {Head, router, useForm, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryPageSection from "@/Wrappers/SecondaryPageSection.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import Checkbox from "@/Components/Checkbox.vue";
import {useViewStore} from "@/store/view.js";
import SaveButton from "@/Components/Form/SaveButton.vue";

const viewStore = useViewStore();

// Определяем префикс для маршрутов
const routePrefix = viewStore.isAdminViewMode ? 'admin' : 'leader';

const form = useForm({
    code: '',
    max_uses: 10,
    is_active: true,
});

const submit = () => {
    form.post(route(routePrefix + '.promo-codes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            router.visit(route(routePrefix + '.promo-codes.index'));
        },
    });
};

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Создание промокода" />

        <SecondaryPageSection
            :back-link="route(routePrefix + '.promo-codes.index')"
            title="Создание промокода"
            description="Здесь вы можете создать новый промокод."
        >
            <form @submit.prevent="submit" class="mt-6 space-y-6">
                <div>
                    <InputLabel
                        for="code"
                        value="Код (оставьте пустым для автогенерации)"
                        :error="!!form.errors.code"
                    />
                    <TextInput
                        id="code"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.code"
                        placeholder="Введите код или оставьте пустым"
                        :error="!!form.errors.code"
                        @input="form.clearErrors('code')"
                    />
                    <InputError :message="form.errors.code" class="mt-2" />
                </div>

                <div>
                    <InputLabel
                        for="max_uses"
                        value="Максимальное количество использований"
                        :error="!!form.errors.max_uses"
                    />
                    <TextInput
                        id="max_uses"
                        type="number"
                        class="mt-1 block w-full"
                        v-model="form.max_uses"
                        min="0"
                        required
                        :error="!!form.errors.max_uses"
                        @input="form.clearErrors('max_uses')"
                    />
                    <InputError :message="form.errors.max_uses" class="mt-2" />
                    <p class="text-sm text-gray-500 mt-1">Установите 0 для неограниченного использования</p>
                </div>

                <div>
                    <label class="flex items-center">
                        <Checkbox v-model:checked="form.is_active" />
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Активен</span>
                    </label>
                    <InputError :message="form.errors.is_active" class="mt-2" />
                </div>

                <SaveButton
                    :disabled="form.processing"
                    :saved="form.recentlySuccessful"
                ></SaveButton>
            </form>
        </SecondaryPageSection>
    </div>
</template>
