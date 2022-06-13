<template>
  <div
    class="c-checkbox"
    :class="{
        'relative flex items-start': true,
        disabled,
    }">
    <div class="flex items-center h-5">
      <input
        type="checkbox"
        class="form-checkbox h-4 w-4 rounded shadow-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-blue-600"
        :id="id"
        :name="name"
        :value="value"
        :checked="modelValue"
        :disabled="disabled"
        @input="toggle"
      />
    </div>

    <div
      :class="{
            'ml-2 font-medium text-sm leading-5': true,
            'text-red-800 dark:text-red-200': invalid
        }">
      <label
        class="mb-0"
        :for="id">
        {{ label }}
      </label>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    modelValue: Boolean,
    disabled: Boolean,
    id: {
      type: String,
      default: function() {
        return 'checkbox-' + Math.random().toString(36).substr(2, 9);
      }
    },
    invalid: Boolean,
    label: String,
    name: String,
    value: String,
  },

  methods: {
    toggle($event) {
      this.$emit('update:modelValue', $event.target.checked)
    }
  },
}
</script>

<style>
.c-checkbox.disabled {
  @apply opacity-50;
}
</style>