<script setup>
import {Head, router, useForm, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryPageSection from "@/Wrappers/SecondaryPageSection.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import Checkbox from "@/Components/Checkbox.vue";
import SaveButton from "@/Components/Form/SaveButton.vue";
import { computed, watch } from 'vue';
import {useViewStore} from "@/store/view.js";

const viewStore = useViewStore();
const promoCode = usePage().props.promoCode;

// Определяем префикс для маршрутов
const routePrefix = viewStore.isAdminViewMode ? 'admin' : 'leader';

const form = useForm({
    max_uses: promoCode.max_uses,
    is_active: promoCode.is_active,
    _method: 'PUT'
});

// Проверка, достигнут ли лимит использований
const isMaxUsesReached = computed(() => {
    return promoCode.max_uses > 0 && promoCode.used_count >= promoCode.max_uses;
});

// Если достигнут лимит использований, отключаем чекбокс активации
watch(isMaxUsesReached, (reached) => {
    if (reached) {
        form.is_active = false;
    }
}, { immediate: true });

const submit = () => {
    form.post(route(routePrefix + '.promo-codes.update', promoCode.id), {
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
        <Head title="Редактирование промокода" />

        <SecondaryPageSection
            :back-link="route(routePrefix + '.promo-codes.index')"
            :title="'Редактирование промокода - ' + promoCode.code"
            description="Здесь вы можете отредактировать настройки промокода."
        >
            <div class="mt-6 space-y-6">
                <div>
                    <InputLabel value="Код" />
                    <div class="mt-1 p-2 bg-gray-100 dark:bg-gray-800 rounded-xl">
                        {{ promoCode.code }}
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Код промокода нельзя изменить</p>
                </div>

                <div v-if="viewStore.isAdminViewMode && promoCode.team_leader">
                    <InputLabel value="Владелец" />
                    <div class="mt-1 p-2 bg-gray-100 dark:bg-gray-800 rounded-xl">
                        {{ promoCode.team_leader?.name || 'Не указан' }}
                    </div>
                </div>

                <div>
                    <InputLabel value="Использовано" />
                    <div class="mt-1 p-2 bg-gray-100 dark:bg-gray-800 rounded-xl">
                        {{ promoCode.used_count }}
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="mt-6 space-y-6">
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
                    <label class="flex items-center" :class="{ 'opacity-50': isMaxUsesReached }">
                        <Checkbox
                            v-model:checked="form.is_active"
                            :disabled="isMaxUsesReached"
                        />
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Активен</span>
                    </label>
                    <InputError :message="form.errors.is_active" class="mt-2" />
                    <p v-if="isMaxUsesReached" class="text-sm text-red-500 mt-1">
                        Промокод нельзя активировать, так как достигнуто максимальное количество использований
                    </p>
                </div>

                <SaveButton
                    :disabled="form.processing"
                    :saved="form.recentlySuccessful"
                ></SaveButton>
            </form>
        </SecondaryPageSection>
    </div>
</template>
