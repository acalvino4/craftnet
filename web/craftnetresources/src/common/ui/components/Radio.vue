<template>
    <div class="c-radio" :class="{
        'flex items-center': true,
        disabled,
    }">
        <input type="radio"
               class="form-radio h-4 w-4 shadow-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600"
               :id="id"
               :name="name"
               :value="value"
               :disabled="disabled"
               :checked="this.modelValue === this.value"
               @input="$emit('update:modelValue', $event.target.value)"
        />

        <label :for="id" class="mb-0 pl-2 ml-0 font-medium">
            <span class="block text-sm leading-5 text-black dark:text-white">{{ label }}</span>
        </label>
    </div>
</template>

<script>
    export default {
        props: {
            disabled: Boolean,
            id: {
                type: String,
                default: function() {
                    return 'radio-' + Math.random().toString(36).substr(2, 9);
                }
            },
            label: String,
            modelValue: String,
            value: String,
            name: String,
        },
    }
</script>

<style lang="scss">
    .c-radio {
        &.disabled {
            @apply opacity-50;
        }
    }
</style>