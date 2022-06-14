<template>
  <Menu
    as="div"
    class="relative inline-block text-left">
    <MenuButton>
      <icon
        icon="dots-horizontal"
        class="w-4 h-4" />
    </MenuButton>

    <hud-menu-items>
      <MenuItem v-slot="{active}">
        <hud-menu-item
          :active="active"
          @click="$emit('changeRole')"
        >
          Change role
        </hud-menu-item>
      </MenuItem>

      <hr class="my-2 mx-3 border-separator" />

      <MenuItem v-slot="{active}">
        <hud-menu-item
          :active="active"
          @click="removeMember"
        >
          <div class="text-red-500">
            Remove
          </div>
        </hud-menu-item>
      </MenuItem>
    </hud-menu-items>
  </Menu>
</template>

<script>
import {Menu, MenuButton, MenuItems, MenuItem} from "@headlessui/vue";
import HudMenuItems from './HudMenuItems';
import HudMenuItem from './HudMenuItem';

export default {
  components: {
    HudMenuItem,
    HudMenuItems,
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