<template>
  <div
    class="main-header px-4 md:px-8 flex justify-between items-center border-b border-gray-200 dark:border-black">
    <sidebar-toggle
      class="md:hidden"
      context="app"
      :showingSidebar="showingSidebar"
      @toggleSidebar="$emit('toggleSidebar')"></sidebar-toggle>

    <div class="flex md:flex-1 items-center">
      <div class="flex-1 flex">
        <div class="hidden">
          <search-toggle></search-toggle>
        </div>
      </div>

      <ul class="py-2.5 flex items-center space-x-2">
        <li class="block cart-menu">
          <cart-button context="app"></cart-button>
        </li>

        <template v-if="user">
          <li class="block ml-1 user-menu relative">
            <user-hud></user-hud>
          </li>
        </template>
      </ul>
    </div>
  </div>
</template>


<script>
import {mapState, mapGetters} from 'vuex'
import UserHud from './UserHud'
import SidebarToggle from './SidebarToggle'
import SearchToggle from '@/console/js/components/SearchToggle';
import CartButton from '@/console/js/components/CartButton';

export default {
  components: {
    CartButton,
    SearchToggle,
    UserHud,
    SidebarToggle,
  },

  props: ['showingSidebar', 'context'],

  data() {
    return {
      showingGlobalMenu: false,
    }
  },

  computed: {
    ...mapState({
      user: state => state.account.user,
    }),

    ...mapGetters({
      userIsInGroup: 'account/userIsInGroup',

    }),
  },

  methods: {
    /**
     * Click away from the global menu.
     */
    awayGlobalMenu: function() {
      if (this.showingGlobalMenu === true) {
        this.showingGlobalMenu = false
      }
    },

    /**
     * Global menu toggle.
     */
    globalMenuToggle() {
      this.showingGlobalMenu = !this.showingGlobalMenu
    }
  }
}
</script>

<style lang="scss">
.main-header {
  .header-right {
    & > ul > li > a {
      @apply text-gray-800 dark:text-gray-200;

      &:hover {
        @apply text-gray-800 dark:text-gray-200;
      }
    }
  }

  // User menu
  .user-menu {
    @apply relative;

    &.has-photo {
      @apply ml-2;
    }

    .header-toggle {
      &:hover {
        @apply cursor-pointer;
      }

      img {
        @apply rounded-full w-8 h-8;
      }
    }

    .popover {
      top: 38px;
      right: 0;

      .popover-arrow {
        right: 5px;
      }
    }
  }

  // Popover
  .popover {
    @apply bg-primary;
    position: absolute;
    top: 0;
    right: 0;
    z-index: 20;

    .popover-arrow {
      width: 50px;
      height: 16px;
      position: absolute;
      top: -16px;
      right: 5px;
      overflow: hidden;

      &::after {
        @apply bg-primary;

        content: "";
        position: absolute;
        width: 16px;
        height: 16px;
        transform: translateX(-50%) translateY(-50%) rotate(45deg);
        top: 100%;
        left: 50%;
        box-shadow: 1px 1px 5px 0px var(--craftui-shadow-3);
      }
    }
  }
}
</style>