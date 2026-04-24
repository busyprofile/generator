<script setup>
import {ref, nextTick, watch} from 'vue';

const model = defineModel({
    required: true,
});

const code = ref(Array(6).fill(""));
const inputs = ref([]);

const setInputRef = (el, index) => {
    if (el) inputs.value[index] = el;
};

watch(
    () => model.value,
    () => {
        if (! model.value) {
            code.value = Array(6).fill("");
        }
    }
);

const handleInput = (index) => {
    if (code.value[index] && index < 5) {
        nextTick(() => inputs.value[index + 1]?.focus());
    }

    model.value = code.value.join('');
};

const handleBackspace = (index) => {
    if (!code.value[index] && index > 0) {
        nextTick(() => inputs.value[index - 1]?.focus());
    }

    model.value = code.value.join('');
};
</script>

<template>
    <div>
        <div class='flex space-x-2 justify-center'>
            <input v-for="(digit, index) in code"
                   :key="index"
                   v-model="code[index]"
                   :ref="(el) => setInputRef(el, index)"
                   type="text"
                   maxlength="1"
                   class='w-12 h-12 text-center text-lg font-bold border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500'
                   @input="handleInput(index)"
                   @keydown.backspace="handleBackspace(index)">
        </div>
    </div>
</template>
