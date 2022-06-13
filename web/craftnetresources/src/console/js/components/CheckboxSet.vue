<template>
  <field
    :vertical="true"
    :errors="errors"
    class="c-checkbox-set">
    <ul>
      <li
        v-for="(option, index) in options"
        :key="index">
        <label>
          <input
            type="checkbox"
            v-model="localValue"
            :value="option.value"
            @change="$emit('update:modelValue', localValue)">
          {{ option.label }}
        </label>
      </li>
    </ul>
  </field>
</template>

<script>
export default {
  props: {
    options: {
      type: Array,
      default: null,
    },
    label: {
      type: String,
      default: null,
    },
    instructions: {
      type: String,
      default: null,
    },
    modelValue: {
      type: [String, Array],
      default: null,
    },
    errors: {
      type: [Array, Boolean],
      default: null,
    },
  },

  data() {
    return {
      localValue: []
    }
  },

  mounted() {
    this.localValue = JSON.parse(JSON.stringify(this.modelValue))
  }
}
</script>
