<template>
  <div class="header">
    <div class="header-left">
      <a
        id="sidebar-toggle"
        href="#"
        @click.prevent="$emit('toggleSidebar')">
        <icon :icon="showingSidebar ? 'times' : 'bars'" />
      </a>

      <div class="header-brand">
        <router-link to="/">Craft ID</router-link>
      </div>
    </div>

    <div class="header-right ml-4">
      <ul class="list-reset flex items-center">
        <li
          class="block ml-4 global-menu"
          v-on-clickaway="awayGlobalMenu">
          <a
            class="block header-toggle"
            @click="globalMenuToggle">
            <icon icon="th" />
          </a>

          <div
            class="popover"
            :class="{hidden: !showingGlobalMenu}">
            <div>
              <p>
                <router-link
                  @click.native="showingGlobalMenu = false"
                  to="/">Craft ID
                </router-link>
              </p>
              <p><a :href="craftPluginsUrl()">Craft Plugin Store</a></p>
            </div>

            <div class="popover-arrow"></div>
          </div>
        </li>

        <li class="block ml-4 cart-menu">
          <router-link
            class="block header-toggle"
            to="/cart">
            <icon icon="shopping-cart" />
            <div
              class="cart-badge"
              :class="{invisible: !cartTotalItems}">{{ cartTotalItems }}
            </div>
          </router-link>
        </li>

        <template v-if="user">
          <li
            class="block ml-4 user-menu"
            :class="{'has-photo': !!user.photoUrl}"
            v-on-clickaway="awayUserMenu">
            <a
              class="block header-toggle"
              @click="userMenuToggle">
              <template v-if="user.photoUrl">
                <img :src="user.photoUrl" />
              </template>
              <template v-else>
                <icon icon="user" />
              </template>
            </a>

            <div
              class="popover"
              :class="{hidden: !showingUserMenu}">
              <div
                :title="user.email"
                class="truncate">
                {{ user.email }}
              </div>

              <hr>

              <ul class="list-reset">
                <li>
                  <router-link
                    class="block py-1"
                    @click.native="showingUserMenu = false"
                    to="/account/billing">Billing
                  </router-link>
                </li>
                <li>
                  <router-link
                    class="block py-1"
                    @click.native="showingUserMenu = false"
                    to="/account/settings">Account Settings
                  </router-link>
                </li>
              </ul>

              <hr>

              <div>
                <a href="/logout">Logout</a>
              </div>

              <div class="popover-arrow"></div>
            </div>
          </li>
        </template>

        <template v-else>
          <li class="block ml-4 user-menu">
            <router-link
              class="block header-toggle"
              to="/login">
              <icon icon="user" />
            </router-link>
          </li>
        </template>
      </ul>
    </div>
  </div>
</template>

<script>
import {mapState, mapGetters} from 'vuex'
import helpers from '../mixins/helpers'
import {directive as onClickaway} from 'vue-clickaway'

export default {
  mixins: [helpers],

  directives: {
    onClickaway: onClickaway,
  },

  props: ['showingSidebar'],

  data() {
    return {
      showingUserMenu: false,
      showingGlobalMenu: false,
    }
  },

  computed: {
    ...mapState({
      user: state => state.account.user,
    }),

    ...mapGetters({
      cartTotalItems: 'cart/cartTotalItems',
    }),
  },

  methods: {
    /**
     * Click away from the user menu.
     */
    awayUserMenu: function() {
      if (this.showingUserMenu === true) {
        this.showingUserMenu = false
      }
    },

    /**
     * Click away from the global menu.
     */
    awayGlobalMenu: function() {
      if (this.showingGlobalMenu === true) {
        this.showingGlobalMenu = false
      }
    },

    /**
     * User menu toggle.
     */
    userMenuToggle() {
      this.showingUserMenu = !this.showingUserMenu
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
.header {
  @apply .flex .justify-between .px-6 .py-3 .items-center .border-b .border-grey-light;

  &-left {
    @apply .flex .items-center;
  }

  &-brand {
    a {
      @apply .text-lg .text-grey-darker .font-bold;

      &:hover {
        @apply .no-underline .text-black;
      }
    }
  }

  &-right {
    & > ul > li > a {
      @apply .text-grey-darker;

      &:hover {
        @apply .text-black;
      }
    }
  }

  .header-toggle {
    @apply .rounded-full .flex .justify-center .items-center;
    width: 36px;
    height: 36px;
  }


  // Cart menu

  .cart-menu {
    a {
      @apply .relative;

      .cart-badge {
        @apply .rounded-full .leading-none .absolute .border .border-grey-lighter .border-2 .text-xs .bg-red .text-white;
        padding: 2px 6px;
        top: -4px;
        right: -10px;
      }
    }
  }


  // Global menu

  .global-menu {
    @apply .relative;

    .popover {
      top: 48px;
      right: -13px;

      .popover-arrow {
        right: 5px;
      }
    }
  }


  // User menu

  .user-menu {
    @apply .relative;

    &.has-photo {
      @apply .ml-6;
    }

    .header-toggle {
      &:hover {
        @apply .cursor-pointer;
      }

      img {
        @apply .rounded-full;
        width: 36px;
      }
    }

    .popover {
      top: 48px;
      right: -12px;

      .popover-arrow {
        right: 5px;
      }
    }
  }


  // Popover

  .popover {
    width: 200px;
    position: absolute;
    top: 0;
    right: 0;
    padding: 20px;
    background: white;
    box-shadow: 0px 5px 15px 0px rgba(0, 0, 0, 0.3);
    z-index: 20;

    .popover-arrow {
      width: 50px;
      height: 16px;
      position: absolute;
      top: -16px;
      right: 5px;
      overflow: hidden;

      &::after {
        content: "";
        position: absolute;
        width: 16px;
        height: 16px;
        background: white;
        transform: translateX(-50%) translateY(-50%) rotate(45deg);
        top: 100%;
        left: 50%;
        box-shadow: 1px 1px 5px 0px rgba(0, 0, 0, 0.3);
      }
    }
  }
}
</style>