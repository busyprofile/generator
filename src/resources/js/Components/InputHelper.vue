<script setup>
const text = defineModel({
    type: String,
    required: true,
});
</script>

<template>
    <small :id="$.uid + '-help'" class="p-help block mt-1">{{ text }}</small>
</template>
