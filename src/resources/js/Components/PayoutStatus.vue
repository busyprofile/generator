<script setup>
import { computed } from 'vue';
import Tag from 'primevue/tag';

const props = defineProps({
    status: {
        type: String,
    },
    status_name: {
        type: String,
    },
});

// Map status to severity for the Tag component
const severity = computed(() => {
    switch (props.status) {
        case 'success':
            return 'success';
        case 'fail':
            return 'danger';
        case 'pending':
            return 'warning';
        default:
            return 'info'; // Default or handle other statuses
    }
});
</script>

<template>
    <Tag :value="status_name" :severity="severity"></Tag>
</template>
