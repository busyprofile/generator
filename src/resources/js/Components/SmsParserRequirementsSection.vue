<script setup>

</script>

<template>
    <h2 class="text-lg font-semibold mb-2">Важные указания:</h2>
    <ul class="text-sm list-disc list-inside mb-2">
        <li class="mb-1">
            Необходимо указывать имя группы <code>amount</code> для суммы перевода. (только цифровую часть)
            <span class="text-primary">*</span>
        </li>
        <li class="mb-1">
            Необходимо указывать имя группы <code>card_type</code> для типа карты если она есть. Например: VISA,MIR...
        </li>
        <li class="mb-1">
            Необходимо указывать имя группы <code>card_last_digits</code> для последних 4х цифр карты если они есть.
        </li>
        <li class="mb-1">
            Имя группы указывается в формате <code>?{{'<amount>'}}</code>
            <span class="text-primary">*</span>
        </li>
    </ul>
    <span class="text-sm text-color-secondary">Обязательные поля помечены <span class="text-primary">*</span></span>

</template>

<style scoped>

</style>
