<script setup>
import { computed } from 'vue';
import Tag from 'primevue/tag';

const props = defineProps({
    is_active: {
        type: Boolean, Number,
    },
});

// Compute tag value based on is_active
const tagValue = computed(() => props.is_active ? 'В работе' : 'Не в работе');

// Compute tag severity based on is_active
const tagSeverity = computed(() => props.is_active ? 'success' : 'danger');
</script>

<template>
    <Tag :value="tagValue" :severity="tagSeverity"></Tag>
</template>
