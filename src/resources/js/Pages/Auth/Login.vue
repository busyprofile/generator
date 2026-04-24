<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Card from 'primevue/card';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const loading = ref(false);

const submit = () => {
    loading.value = true;
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
            loading.value = false;
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Вход" />

        <Card class="login-card">
            <template #header>
                <div class="flex justify-center pt-8 pb-2 px-6">
                    <img
                        src="/images/light.png"
                        loading="lazy"
                        alt="Логотип"
                        class="w-full max-w-[180px] h-auto"
                    />
                </div>
            </template>

            <template #content>
                <div class="px-2">
                    <Message v-if="status" severity="success" :closable="false" class="mb-4 w-full">
                        {{ status }}
                    </Message>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium mb-1.5">Почта</label>
                            <div class="input-icon-wrap">
                                <i class="pi pi-envelope input-icon"></i>
                                <InputText
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    class="w-full pl-input"
                                    :class="{'p-invalid': form.errors.email}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="you@example.com"
                                />
                            </div>
                            <small v-if="form.errors.email" class="p-error block mt-1.5">{{ form.errors.email }}</small>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium mb-1.5">Пароль</label>
                            <div class="input-icon-wrap">
                                <i class="pi pi-lock input-icon"></i>
                                <Password
                                    id="password"
                                    v-model="form.password"
                                    toggleMask
                                    class="w-full"
                                    inputClass="w-full pl-input"
                                    :class="{'p-invalid': form.errors.password}"
                                    :feedback="false"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                />
                            </div>
                            <small v-if="form.errors.password" class="p-error block mt-1.5">{{ form.errors.password }}</small>
                        </div>

                        <div class="flex items-center pt-1">
                            <div class="flex items-center gap-2">
                                <Checkbox inputId="remember" v-model="form.remember" :binary="true" />
                                <label for="remember" class="text-sm cursor-pointer select-none">Запомнить меня</label>
                            </div>
                        </div>

                        <Button
                            type="submit"
                            label="Войти"
                            icon="pi pi-sign-in"
                            class="w-full mt-2"
                            :loading="loading"
                            :disabled="form.processing"
                        />
                    </form>
                </div>
            </template>
        </Card>
    </GuestLayout>
</template>

<style scoped>
.login-card {
    width: 100%;
    max-width: 420px;
    margin: 0 auto;
    animation: slideUp 0.4s ease-out;
}

.dark .login-card {
    background-color: var(--card);
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Обёртка поля с иконкой */
.input-icon-wrap {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1;
    color: var(--muted-foreground);
}

/* Отступ для текста под иконкой */
.input-icon-wrap :deep(input.p-inputtext),
.input-icon-wrap :deep(.p-password-input) {
    padding-left: 2.5rem !important;
}

.input-icon-wrap :deep(.p-password) {
    width: 100%;
}

.dark .p-password-panel {
    background-color: var(--popover) !important;
    color: var(--popover-foreground) !important;
    border-color: var(--border) !important;
}

.dark label {
    color: var(--foreground);
}
</style>
