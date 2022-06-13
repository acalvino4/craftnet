<template>
  <component
    :is="component"
    class="c-btn truncate"
    :target="target"
    :type="computedType"
    :class="[{
                small,
                large,
                block,
                outline,
                loading,
                [kind]: true,
                'group': true,
                'ring-offset-2': true,

                // Base
                'inline-block px-4 py-2 rounded-md': true,
                'text-sm leading-5 no-underline': true,
                'border border-solid': true,
                'disabled:opacity-50 disabled:cursor-default': true,
                'opacity-50 cursor-default': disabled,
                'focus:outline-none focus-visible:ring-2': true,

                // Variants
                'text-white': (kind === 'primary' || kind === 'danger') && !outline,
                'hover:text-white': (kind === 'primary' || kind === 'danger') && !outline,
                'active:text-white': (kind === 'primary' || kind === 'danger') && !outline,

                // Default
                'text-black dark:text-white': kind === 'default',

                // Primary
                'border-blue-500 dark:border-blue-600': kind === 'primary',
                'bg-blue-500 dark:bg-blue-600': kind === 'primary' && !outline,
                'hover:bg-blue-700 dark:hover:bg-blue-500 hover:border-blue-700 dark:hover:border-blue-500': kind === 'primary' && !outline && !disabled,
                'active:bg-blue-800 dark:active:bg-blue-400 active:border-bg-blue-800 dark:active:bg-blue-400': kind === 'primary' && !outline && !disabled,
                'disabled:bg-blue-500 dark:bg-blue-600 disabled:border-blue-500 dark:disabled:border-blue-600': kind === 'primary' && !outline,
                'text-blue-500 dark:text-blue-600': kind === 'primary' && outline,
                'hover:bg-blue-500 dark:hover:bg-blue-600': kind === 'primary' && outline && !disabled,
                'active:bg-blue-800 dark:active:bg-blue-400': kind === 'primary' && outline,

                // Secondary
                'border-gray-200 dark:border-gray-700 text-black dark:text-white': kind === 'secondary',
                'hover:cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-600 hover:border-gray-300 dark:hover:border-gray-600 hover:no-underline': kind === 'secondary' && !disabled,
                'active:cursor-pointer active:bg-gray-400 dark:active:bg-gray-500 active:border-gray-400 dark:active:border-gray-500': kind === 'secondary' && !disabled,
                'bg-gray-200 dark:bg-gray-700': kind === 'secondary' && !outline,
                'text-black dark:text-white': kind === 'secondary' && !outline,

                // Danger
                'border-red-600': kind === 'danger',
                'bg-red-600': kind === 'danger' && !outline,
                'hover:bg-red-700 hover:border-red-700': kind === 'danger' && !outline && !disabled,
                'active:bg-red-800 active:border-red-800': kind === 'danger' && !outline && !disabled,
                'disabled:bg-red-800 disabled:border-red-800': kind === 'danger' && !outline,
                'text-red-600': kind === 'danger' && outline,
                'hover:bg-red-600': kind === 'danger' && outline && !disabled,
                'active:bg-red-800': kind === 'danger' && outline && !disabled
            }]"
    v-bind="additionalAttributes"
  >
    <template v-if="loading">
      <spinner></spinner>
    </template>

    <div class="c-btn-content">
      <slot></slot>
    </div>
  </component>
</template>

<script>
export default {
  name: 'Btn',

  props: {
    /**
     * 'button', 'submit', 'reset', or 'menu'
     */
    type: {
      type: String,
      default: 'button',
    },
    /**
     * 'default', 'primary', or 'danger'
     */
    kind: {
      type: String,
      default: 'secondary',
    },
    /**
     * Smaller version of button if set to `true`.
     */
    small: {
      type: Boolean,
      default: false,
    },
    /**
     * Larger version of button if set to `true`.
     */
    large: {
      type: Boolean,
      default: false,
    },
    /**
     * Block version of button if set to `true`.
     */
    block: {
      type: Boolean,
      default: false,
    },
    /**
     * Disabled version of button if set to `true`.
     */
    disabled: {
      type: Boolean,
      default: false,
    },
    /**
     * Outline version of button if set to `true`.
     */
    outline: {
      type: Boolean,
      default: false,
    },
    loading: {
      type: Boolean,
      default: false,
    },
    to: {
      type: [String, Object],
      default: null,
    },
    href: {
      type: String,
      default: null,
    },
    target: {
      type: String,
      default: null,
    },
  },

  computed: {
    additionalAttributes() {
      const attrs = {}

      if (this.disabled) {
        attrs.disabled = true
      }

      if (this.href) {
        attrs.href = this.href
      }

      if (this.to) {
        attrs.to = this.to
      }

      return attrs
    },

    component() {
      if (this.to !== null && this.to !== '') {
        return 'router-link'
      }

      if (this.href !== null && this.href !== '') {
        return 'a'
      }

      return 'button'
    },

    computedType() {
      if (this.to !== null || this.href !== null) {
        return null
      }

      return this.type
    },
  }
}
</script>

<style lang="scss">
@import "../sass/mixins";

.c-btn,
a.c-btn,
button.c-btn {
  &.block {
    @apply w-full my-2;
  }

  &.small {
    @apply px-3 leading-4;
  }

  &.large {
    @apply text-base leading-6;
  }

  &.loading {
    @apply relative;

    .c-spinner {
      @apply absolute inset-0 flex justify-center items-center;
      color: currentColor;
    }

    .c-btn-content {
      @apply invisible;
    }
  }

  .c-btn-content {
    @apply inline-block;
  }
}
</style>
