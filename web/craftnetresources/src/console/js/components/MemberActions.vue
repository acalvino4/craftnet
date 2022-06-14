<template>
  <Menu
    as="div"
    class="relative inline-block text-left">
    <MenuButton>
      <icon
        icon="dots-horizontal"
        class="w-4 h-4" />
    </MenuButton>

    <MenuItems
      class="absolute z-10 right-0 w-56 mt-2 origin-top-right bg-white dark:bg-gray-800 divide-y divide-gray-100 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
      <div class="px-2 py-1">
        <MenuItem v-slot="{active}">
          <button
            class="block w-full text-left rounded text-sm my-1 px-3 py-2 leading-5 border border-transparent text-gray-800 dark:text-gray-200 hover:text-gray-800 dark:text-gray-200 hover:border-interactive-nav-active-background hover:bg-gray-200 dark:bg-gray-700 hover:no-underline"
            :class="[
                      active ? 'bg-gray-100' : '',
                      'group flex rounded-md items-center w-full px-2 py-2 text-sm',
                    ]"
            @click="$emit('changeRole')"
          >
            Change role
          </button>
        </MenuItem>
        <MenuItem v-slot="{active}">
          <button
            class="block w-full text-left rounded text-sm my-1 px-3 py-2 leading-5 border border-transparent text-red-500 hover:text-red-500 dark:text-gray-200 hover:border-interactive-nav-active-background hover:bg-gray-200 dark:bg-gray-700 hover:no-underline"
            :class="[
                      active ? 'bg-gray-100' : '',
                      'group flex rounded-md items-center w-full px-2 py-2 text-sm',
                    ]"
            @click="removeMember"
          >
            Remove
          </button>
        </MenuItem>
      </div>
    </MenuItems>
  </Menu>
</template>

<script>
import {Menu, MenuButton, MenuItems, MenuItem} from "@headlessui/vue";

export default {
  components: {
    Menu,
    MenuButton,
    MenuItems,
    MenuItem,
  },

  data() {
    return {
      showHud: false,
    }
  },

  methods: {
    removeMember() {
      if (confirm("Are you sure you want to remove this member?")) {
        // TODO: Implement removing a member
        console.log('Remove member')
      }
    }
  },
}
</script>