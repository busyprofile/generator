<script setup>
import {router} from "@inertiajs/vue3";
import GoBackButton from "@/Components/GoBackButton.vue";
import Card from 'primevue/card';

const props = defineProps({
    backLink: {
        type: String,
        default: null,
    },
    title: {
        type: String,
    },
    description: {
        type: String,
        default: null
    }
});
</script>

<template>
    <div class="mx-auto space-y-4">
        <div v-if="backLink" class="mb-3">
            <GoBackButton @click="router.visit(backLink)"/>
        </div>
        <Card>
            <template #title>
                <h2 class="text-xl font-bold tracking-tight">
                    {{ title }}
                </h2>
            </template>
            <template #subtitle>
                <p v-if="description" class="mt-1 text-sm text-color-secondary">
                    {{ description }}
                </p>
            </template>
            <template #content>
                <slot></slot>
            </template>
        </Card>
    </div>
</template>

<style scoped>

</style>
