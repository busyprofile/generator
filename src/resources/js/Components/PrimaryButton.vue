<script setup>
import Button from 'primevue/button';

defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    icon: {
        type: String,
        default: null,
    },
    iconPos: {
        type: String,
        default: 'left',
    },
    loading: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <Button
        :type="type"
        :icon="icon"
        :iconPos="iconPos"
        :loading="loading"
        
    >
        <slot />
    </Button>
</template>
