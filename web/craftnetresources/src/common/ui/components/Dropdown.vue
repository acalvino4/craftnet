<template>
    <div class="c-dropdown" :class="{
        'is-invalid': invalid,
        'w-full': fullwidth,
        disabled,
    }">
            <select :disabled="disabled" :value="modelValue" :class="{
                'form-select bg-white dark:bg-gray-700 sm:text-sm sm:leading-5 pl-3 pr-10 rounded-md': true,
                'w-full': fullwidth,
                'border-red-400': invalid,
                'border-gray-300 dark:border-gray-600': !invalid,
            }" @input="$emit('update:modelValue', $event.target[$event.target.selectedIndex].value)">
                <option v-for="(option, key) in options" :value="option.value" :key="key">{{ option.label }}</option>
            </select>
    </div>
</template>

<script>
    export default {
        props: {
            disabled: {
                type: Boolean,
                default: false,
            },
            invalid: {
                type: Boolean,
                default: false,
            },
            fullwidth: {
                type: Boolean,
                default: false,
            },
            id: {
                type: String,
                default: function () {
                    return 'c-dropdown-id-' + Math.random().toString(36).substr(2, 9);
                },
            },
            options: {
                type: Array,
                default: null,
            },
            modelValue: {
                type: [String, Number],
                default: null,
            },
        },
    }
</script>

<style lang="scss">
    @import "../sass/mixins";

    .c-dropdown {
        display: inline-block;
        position: relative;

        &.disabled {
            @apply opacity-50;
        }

        select {
            @include ltr() {
                background-position: right 0.5rem center;
            }

            @include rtl() {
                background-position: left 0.5rem center;
            }
        }
    }

</style>