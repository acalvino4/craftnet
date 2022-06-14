<template>
  <component
    :is="componentType"
    :href="href"
    :class="[{
      'block w-full rounded my-1 px-3 py-2 leading-5': true,
      'text-left text-sm text-gray-800 dark:text-gray-200 hover:text-gray-800 dark:text-gray-200 dark:hover:text-gray-200 hover:no-underline': true,
      'border border-transparent hover:border-interactive-nav-active-background': true,
      'hover:bg-gray-200 dark:bg-gray-800': !active,
      'bg-gray-200 dark:bg-gray-700': active,
    }]">
    <slot />
  </component>
</template>

<script>
export default {
  props: {
    active: {
      type: Boolean,
      default: false,
    },
    href: {
      type: String,
      default: null,
    },
  },

  computed: {
    componentType() {
      return this.href ? 'a' : 'button'
    },
  },
}
</script>