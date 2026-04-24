<script setup>
const props = defineProps({
    form: {
        type: Object,
    },
    field: {
        type: String,
    },
    label: {
        type: String
    },
    helper: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <div class="field col-12">
        <label 
            v-if="label" 
            :for="field" 
            :class="{ 'p-error': form.errors[field] }" 
            class="mb-2 block"
        >
            {{ label }}
        </label>

        <slot/>

        <small 
            :id="field + '-help'" 
            class="p-help block mt-1" 
            v-if="helper && !form.errors[field]"
        >
            {{ helper }}
        </small>
        <small 
            :id="field + '-error'" 
            class="p-error block mt-1" 
            v-if="form.errors[field]"
        >
            {{ form.errors[field] }}
        </small>
    </div>
</template>

<style scoped>

</style>
