<template>
  <div class="c-textbox">
    <div class="c-textbox-wrapper w-full">
      <component
        :is="computedComponent"
        :autocapitalize="autocapitalize"
        :autocomplete="autocomplete"
        :autofocus="autofocus"
        :class="[{
                        'form-input block rounded': true,
                        'w-full': fullwidth,
                        'w-16': type === 'number',
                        'is-invalid border-red-400': invalid,
                        'border-gray-300 dark:border-gray-600': !invalid && !disabled,
                        'text-red-600': max && max < this.modelValue.length,
                        'bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600': disabled,
                        'bg-white dark:bg-gray-700': !disabled
                    }]"
        :cols="computedCols"
        :disabled="disabled"
        :id="id"
        :mask="mask"
        :max="max"
        :min="min"
        :name="name"
        :pattern="pattern"
        :placeholder="placeholder"
        :readonly="readonly"
        :rows="computedRows"
        :size="size"
        :spellcheck="spellcheck"
        :step="step"
        :type="computedType"
        :value="modelValue"
        ref="input"
        v-maska="computedMask"
        @input="$emit('update:modelValue', $event.target.value)"
        v-bind="$attrs"
      >
        <template v-if="type === 'textarea'">{{ modelValue }}</template>
      </component>

      <p
        v-if="max"
        class="max"
        :class="{
                    'text-light': remainingChars >= 20,
                    'text-yellow-800 dark:text-yellow-200': remainingChars < 20 && remainingChars >= 0,
                    'text-red-800 dark:text-red-200': remainingChars < 0
                }"><small>{{ (max - remainingChars) }}/{{ max }}</small></p>
    </div>
  </div>
</template>

<script>
import {maska} from 'maska'

export default {
  inheritAttrs: false,
  directives: {
    maska,
  },

  props: {
    autocapitalize: {
      type: Boolean,
      default: false
    },
    autocomplete: {
      type: String,
      default: 'on'
    },
    autofocus: {
      type: Boolean,
      default: false
    },
    cols: {
      type: Number,
      default: null,
    },
    disabled: {
      type: Boolean,
      default: false
    },
    fullwidth: {
      type: Boolean,
      default: true,
    },
    invalid: {
      type: Boolean,
      default: false
    },
    id: {
      type: String,
      default: function() {
        return 'c-textbox-id-' + Math.random().toString(36).substr(2, 9);
      },
    },
    mask: {
      type: [String, Object],
      default: ''
    },
    max: {
      type: [Number, String],
      default: null
    },
    min: {
      type: [Number, String],
      default: null
    },
    name: {
      type: String,
      default: null
    },
    pattern: {
      type: String,
      default: null
    },
    placeholder: {
      type: String,
      default: null
    },
    readonly: {
      type: Boolean,
      default: false
    },
    rows: {
      type: [Number, String],
      default: null,
    },
    size: {
      type: [Number, String],
      default: 20
    },
    spellcheck: {
      type: Boolean,
      default: false
    },
    step: {
      type: [Number, String],
      default: null
    },
    type: {
      type: String,
      default: 'text'
    },
    modelValue: {
      type: [String, Number],
      default: ''
    },
  },

  emits: ['update:modelValue'],

  computed: {
    remainingChars() {
      if (this.max) {
        return this.max - this.modelValue.length
      }

      return null
    },

    computedComponent() {
      if (this.type === 'textarea') {
        return 'textarea'
      }

      return 'input'
    },

    computedType() {
      if (this.type === 'textarea') {
        return null
      }

      return this.type
    },

    computedCols() {
      if (this.type !== 'textarea') {
        return null
      }

      return this.cols
    },

    computedRows() {
      if (this.type !== 'textarea') {
        return null
      }

      return this.rows ? this.rows : 4
    },

    computedMask() {
      if (!this.mask) {
        return null
      }

      return {
        mask: this.mask,
        tokens: {
          'x': {pattern: /[0-9a-zA-Z]/, uppercase: true},
        }
      }
    }
  },
}
</script>

