<template>
  <MenuItems
    class="absolute z-10 -right-2 mt-2 w-56 origin-top-right bg-white dark:bg-gray-800 divide-y divide-gray-100 rounded-md shadow-lg ring-1 ring-black dark:ring-white ring-opacity-10 dark:ring-opacity-20 focus:outline-none">
    <div class="px-2 py-1">
      <slot />
    </div>
  </MenuItems>
</template>

<script>
import {MenuItems} from '@headlessui/vue'

export default {
  components: {
    MenuItems
  }
}
</script>