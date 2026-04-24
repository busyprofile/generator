<script setup>
import {computed} from "vue";
import Checkbox from 'primevue/checkbox';
import {useTableFiltersStore} from "@/store/tableFilters.js";

const tableFiltersStore = useTableFiltersStore();

const props = defineProps({
    name: {
        type: String,
    },
    title: {
        type: String,
    },
});

const model = computed({
    get: () => !!tableFiltersStore.filters[props.name],
    set: (val) => {
        tableFiltersStore.filters[props.name] = val
    }
})
</script>

<template>
    <div class="field-checkbox col-12 md:col-4 lg:col-3">
        <Checkbox v-model="model" :inputId="`checkbox-${$.uid}`" :binary="true" />
        <label
            :for="`checkbox-${$.uid}`"
            class="ml-2"
        >
            {{ title }}
        </label>
    </div>
</template>

<style scoped>

</style>
