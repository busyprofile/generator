<script setup>
import {computed} from "vue";
import ProgressBar from 'primevue/progressbar';

const props = defineProps({
    current: {
        type: [String, Number],
        required: true
    },
    total: {
        type: [String, Number],
        required: true
    },
});

const percent = computed(() => {
    const currentNum = Number(props.current);
    const totalNum = Number(props.total);
    if (isNaN(currentNum) || isNaN(totalNum) || totalNum === 0) {
        return 0;
    }
    return (currentNum / totalNum) * 100;
});

</script>

<template>
    <div>
        <div class="flex justify-content-end mb-1">
            <span class="text-xs font-semibold text-color text-nowrap">
                {{ current }} / {{ total }}
            </span>
        </div>
        <ProgressBar :value="percent" :showValue="false" :pt="{ root: { class: '!h-1.5' } }"></ProgressBar>
    </div>
</template>

<style scoped>

</style>
