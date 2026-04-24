<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';
import UpdateAvatarForm from "@/Pages/Profile/Partials/UpdateAvatarForm.vue";
import Update2faForm from "@/Pages/Profile/Partials/Update2faForm.vue";
import LoginHistoryForm from "@/Pages/Profile/Partials/LoginHistoryForm.vue";

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    auth2fa: {
        type: Object,
        default: () => ({}),
    },
    loginHistory: {
        type: Array,
        default: () => [],
    },
});

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <Head title="Профиль" />

    <div>
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="p-5 sm:p-8 bg-card rounded-plate border border-border">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="w-full"
                />
            </div>

            <div class="p-5 sm:p-8 bg-card rounded-plate border border-border">
                <UpdatePasswordForm class="w-full"/>
            </div>
            <div class="p-5 sm:p-8 bg-card rounded-plate border border-border">
                <Update2faForm class="w-full"/>
            </div>
            <div class="p-5 sm:p-8 bg-card rounded-plate border border-border">
                <LoginHistoryForm :login-history="loginHistory" class="w-full"/>
            </div>
        </div>
    </div>
</template>
