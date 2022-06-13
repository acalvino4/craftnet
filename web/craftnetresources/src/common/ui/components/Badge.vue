<template>
    <span
      :class="[{
                'inline-flex items-center rounded font-medium': true,
                'px-2 py-0.5 leading-4 text-xs': !large,
                'px-2.5 py-0.5 leading-5 text-sm': large,

                [`bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200`]: type === 'default',
                [`bg-green-500 text-green-800 dark:text-green-200`]: type === 'success',
                [`bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200`]: type === 'info',
                [`bg-yellow-200 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200`]: type === 'warning',
                [`bg-red-500 text-red-800 dark:text-red-200`]: type === 'danger',
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