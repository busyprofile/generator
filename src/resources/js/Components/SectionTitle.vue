<template>
    <div class="flex justify-content-between align-items-start">
        <div>
            <h3 class="text-lg font-medium text-color mt-0 mb-1">
                <slot name="title" />
            </h3>

            <p class="mt-0 mb-0 text-sm text-color-secondary">
                <slot name="description" />
            </p>
        </div>

        <div>
            <slot name="aside" />
        </div>
    </div>
</template> 