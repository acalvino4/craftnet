<template>
  <button
    :class="[{
      'w-full cursor-pointer rounded my-1 px-3 py-2 leading-5 flex items-center justify-between': true,
      'text-sm text-gray-800 dark:text-gray-200 hover:text-gray-800 dark:hover:text-gray-200 hover:no-underline': true,
      'hover:bg-gray-200 dark:hover:bg-gray-600': true,
      'border border-transparent hover:border-interactive-nav-active-background': true,
      'bg-gray-200 dark:bg-gray-800': active,
    }]"
  >
    <div class="min-w-0">
      <div class="truncate">
        <slot />
        *
      </div>
    </div>

    <icon
      v-if="checked"
      class="w-4 h-4 text-light"
      icon="check" />
  </button>
</template>

<script>
export default {
  props: {
    active: {
      type: Boolean,
      default: false,
    },
    checked: {
      type: Boolean,
      default: false,
    },
  },
}
</script>