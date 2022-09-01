<template>
    <span
      :class="[{
                'inline-flex items-center rounded font-medium': true,
                'px-2 py-0.5 leading-4 text-xs': !large,
                'px-2.5 py-0.5 leading-5 text-sm': large,

                'bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-100': type === 'default',
                'bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-100': type === 'success',
                'bg-blue-100 dark:bg-blue-600 text-blue-600 dark:text-blue-100': type === 'info',
                'bg-yellow-100 dark:bg-yellow-600 text-yellow-600 dark:text-yellow-100': type === 'warning',
                'bg-red-100 text-red-600 dark:text-red-100': type === 'danger',
            }]"
    >
        <slot></slot>
    </span>
</template>

<script>
export default {
  props: {
    /**
     * 'default', 'success', 'info', 'warning', or 'danger'
     */
    type: {
      type: String,
      default: 'default',
    },
    /**
     * Larger version of badge if set to `true`.
     */
    large: {
      type: Boolean,
      default: false,
    },
  },
}
</script>