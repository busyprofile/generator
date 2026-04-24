<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import {Head, useForm} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Select from "@/Components/Select.vue";
import SaveButton from "@/Components/Form/SaveButton.vue";
import SecondaryPageSection from "@/Wrappers/SecondaryPageSection.vue";
import TextInput from "@/Components/TextInput.vue";
import InputHelper from "@/Components/InputHelper.vue";

const form = useForm({
    name: '',
    description: '',
    project_link: '',
});

const submit = () => {
    form
        .post(route('merchants.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
};

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
    <div>
        <Head title="Создание мерчанта" />

        <SecondaryPageSection
            :back-link="route('merchants.index')"
            title="Создание мерчанта"
            description="Здесь вы можете создать мерчант."
        >
            <form @submit.prevent="submit" class="mt-6 space-y-6">
                <div>
                    <InputLabel
                        for="name"
                        value="Название проекта"
                        :error="!!form.errors.name"
                    />

                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        :error="!!form.errors.name"
                        @input="form.clearErrors('name')"
                    />

                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel
                        for="description"
                        value="Опишите деятельность проекта"
                        :error="!!form.errors.description"
                    />

                    <TextInput
                        id="description"
                        v-model="form.description"
                        type="text"
                        class="mt-1 block w-full"
                        :error="!!form.errors.description"
                        @input="form.clearErrors('description')"
                    />

                    <InputError :message="form.errors.description" class="mt-2" />
                </div>

                <div>
                    <InputLabel
                        for="project_link"
                        value="Укажите ссылку на проект"
                        :error="!!form.errors.project_link"
                    />

                    <TextInput
                        id="project_link"
                        v-model="form.project_link"
                        type="text"
                        class="mt-1 block w-full"
                        :error="!!form.errors.project_link"
                        @input="form.clearErrors('project_link')"
                    />

                    <InputError :message="form.errors.project_link" class="mt-2" />
                    <InputHelper v-if="! form.errors.project_link" model-value="Указывайте ссылку в формате https://example.com/"></InputHelper>
                </div>

                <SaveButton
                    :disabled="form.processing"
                    :saved="form.recentlySuccessful"
                ></SaveButton>
            </form>
        </SecondaryPageSection>
    </div>
</template>
