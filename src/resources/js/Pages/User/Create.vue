<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {Head, useForm, usePage} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from "@/Components/TextInput.vue";
import Select from "@/Components/Select.vue";
import SecondaryPageSection from "@/Wrappers/SecondaryPageSection.vue";
import TraderTeamLeaders from '@/Components/TraderTeamLeaders.vue';
import {ref, watch, computed} from "vue";
import axios from 'axios';

const roles = usePage().props.roles;
const traderCategories = usePage().props.traderCategories || [];

// Загружаем список доступных тимлидеров
const teamLeadersList = ref([]);

const loadTeamLeaders = async () => {
    try {
        const response = await axios.get(route('admin.team-leaders.index'));
        teamLeadersList.value = response.data;
    } catch (error) {
        console.error('Ошибка при загрузке тимлидеров:', error);
    }
};

// Загружаем список при первой загрузке компонента
loadTeamLeaders();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: 0,
    promo_code: '',
    trader_category_id: '',
    trader_team_leaders: [],
    target_reserve_amount: null,
});

// Находим объект роли "Трейдер"
const traderRole = computed(() => roles.find(role => role.name === 'Trader'));
// Проверка, выбрана ли роль Трейдер
const isTraderSelected = computed(() => form.role_id === traderRole.value?.id);

// Сброс специфичных для трейдера полей при смене роли
watch(() => form.role_id, (newRoleId) => {
    if (newRoleId !== traderRole.value?.id) {
        form.target_reserve_amount = null;
        form.trader_category_id = '';
        // form.trader_team_leaders = []; // Если это поле тоже нужно сбрасывать
    }
});

const submit = () => {
    form.post(route('admin.users.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Создание пользователя" />

        <SecondaryPageSection
            :back-link="route('admin.users.index')"
            title="Создание пользователя"
            description="Здесь вы можете создать пользователя."
        >
            <form @submit.prevent="submit" class="mt-6">
                <div class="md:grid md:grid-cols-2 md:gap-x-8">
                    <div>
                        <InputLabel
                            for="name"
                            value="Имя"
                            :error="!!form.errors.name"
                        />

                        <TextInput
                            id="name"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                            :error="!!form.errors.name"
                            @input="form.clearErrors('name')"
                        />

                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel
                            for="email"
                            value="Почта"
                            :error="!!form.errors.email"
                        />

                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            :error="!!form.errors.email"
                            @input="form.clearErrors('email')"
                        />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel
                            for="password"
                            value="Пароль"
                            :error="!!form.errors.password"
                        />

                        <TextInput
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            :error="!!form.errors.password"
                            @input="form.clearErrors('password')"
                        />

                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel
                            for="password_confirmation"
                            value="Подтвердите пароль"
                        />

                        <TextInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                        />

                        <InputError :message="form.errors.password_confirmation" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel
                            for="roles"
                            value="Роль"
                            :error="!!form.errors.role_id"
                            class="mb-1"
                        />

                        <Select
                            v-model="form.role_id"
                            :error="!!form.errors.role_id"
                            :items="roles"
                            value="id"
                            name="name"
                            default_title="Выберите роль"
                            @change="form.clearErrors('role_id')"
                        ></Select>

                        <InputError class="mt-2" :message="form.errors.role_id" />
                    </div>

                    <!-- Поле выбора категории трейдера -->
                    <div v-if="isTraderSelected">
                        <InputLabel
                            for="trader_category_id"
                            value="Категория трейдера"
                            :error="!!form.errors.trader_category_id"
                            class="mb-1"
                        />

                        <Select
                            v-model="form.trader_category_id"
                            :error="!!form.errors.trader_category_id"
                            :items="traderCategories"
                            value="id"
                            name="name"
                            default_title="Выберите категорию (опционально)"
                            @change="form.clearErrors('trader_category_id')"
                        ></Select>

                        <InputError class="mt-2" :message="form.errors.trader_category_id" />
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Категория влияет на приоритет назначения трейдера на заказы. Можно оставить пустым.
                        </div>
                    </div>

                    <!-- Новое поле для Целевого резерва трейдера -->
                    <div v-if="isTraderSelected">
                        <InputLabel
                            for="target_reserve_amount"
                            value="Целевой резерв трейдера (USDT)"
                            :error="!!form.errors.target_reserve_amount"
                        />
                        <TextInput
                            id="target_reserve_amount"
                            type="number"
                            class="mt-1 block w-full"
                            v-model.number="form.target_reserve_amount"
                            placeholder="По умолчанию 1000"
                            min="0"
                            :error="!!form.errors.target_reserve_amount"
                            @input="form.clearErrors('target_reserve_amount')"
                        />
                        <InputError class="mt-2" :message="form.errors.target_reserve_amount" />
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Неснижаемый остаток на резервном балансе трейдера. Если не указано, используется системное значение по умолчанию.
                        </div>
                    </div>

                    <!-- Показываем поле промокода только если не выбрана роль Трейдер -->
                    <div v-if="!isTraderSelected">
                        <InputLabel
                            for="promo_code"
                            value="Промокод"
                            :error="!!form.errors.promo_code"
                        />

                        <TextInput
                            id="promo_code"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.promo_code"
                            autocomplete="off"
                            :error="!!form.errors.promo_code"
                            @input="form.clearErrors('promo_code')"
                        />
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Введите промокод, если пользователь был привлечен через него. Нельзя изменить после сохранения.
                        </div>

                        <InputError class="mt-2" :message="form.errors.promo_code" />
                    </div>
                </div>
                
                <!-- Выбор тимлидеров для трейдера -->
                <div v-if="isTraderSelected" class="mt-6 md:col-span-2">
                    <TraderTeamLeaders
                        v-model="form.trader_team_leaders"
                        :availableTeamLeaders="teamLeadersList"
                        :error="form.errors.trader_team_leaders"
                        :disabled="form.processing"
                    />
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
