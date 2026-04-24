<script setup>
const props = defineProps({
    stage: {
        type: String,
    },
});

const emit = defineEmits(['setStage']);

const setStage = (type) => {
    emit('setStage', type);
};
</script>

<template>
    <div class="text-xs text-black dark:text-white flex gap-2 cursor-pointer">
        <div @click="setStage('select_gateway')" :class="{'text-blue-500' : stage === 'select_gateway'}">select_gateway</div>
        <div @click="setStage('payment')" :class="{'text-blue-500' : stage === 'payment'}">payment</div>
        <div @click="setStage('success')" :class="{'text-blue-500' : stage === 'success'}">success</div>
        <div @click="setStage('fail')" :class="{'text-blue-500' : stage === 'fail'}">fail</div>
        <div @click="setStage('dispute_review')" :class="{'text-blue-500' : stage === 'dispute_review'}">dispute_review</div>
        <div @click="setStage('dispute_canceled')" :class="{'text-blue-500' : stage === 'dispute_canceled'}">dispute_canceled</div>
    </div>
</template>

<style scoped>

</style>
