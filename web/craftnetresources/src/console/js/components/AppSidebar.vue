<template>
  <div
    class="sidebar pt-2 md:border-r border-gray-200 dark:border-black md:w-64 overflow-y-auto flex-shrink-0"
    :class="{ 'showing-sidebar': showingSidebar }">
    <organization-switcher></organization-switcher>

    <h5>Licenses</h5>
    <ul>
      <li>
        <router-link
          @click="$emit('closeSidebar')"
          to="/licenses/cms">
          <icon
            class="mr-2 text-blue-500 w-5 h-5"
            icon="key" />
          Craft CMS
        </router-link>
      </li>
      <li>
        <router-link
          @click="$emit('closeSidebar')"
          to="/licenses/plugins">
          <icon
            class="mr-2 text-blue-500 w-5 h-5"
            icon="key" />
          Plugins
        </router-link>
      </li>
      <li>
        <router-link
          @click="$emit('closeSidebar')"
          to="/licenses/claim">
          <icon
            class="mr-2 text-blue-500 w-5 h-5"
            icon="key" />
          Claim License
        </router-link>
      </li>
    </ul>

    <template v-if="currentOrganization && user">
      <template v-if="userIsInGroup('developers')">
        <h5>Developer</h5>
        <ul>
          <li>
            <router-link
              @click="$emit('closeSidebar')"
              to="/developer/sales">
              <icon
                class="mr-2 text-blue-500 w-5 h-5"
                icon="chart-square-bar" />
              Plugin Sales
            </router-link>
          </li>
          <li>
            <router-link
              @click="$emit('closeSidebar')"
              to="/developer/plugins">
              <icon
                class="mr-2 text-blue-500 w-5 h-5"
                icon="plug" />
              Plugins
            </router-link>
          </li>
        </ul>
      </template>
    </template>

    <h5>
      <template v-if="currentOrganization">
        Organization Settings
      </template>
      <template v-else>
        User Settings
      </template>
    </h5>
    <ul>
      <template v-if="!currentOrganization && user">
        <li>
          <router-link
            @click="$emit('closeSidebar')"
            to="/settings/account">
            <icon
              class="mr-2 text-blue-500 w-5 h-5"
              icon="cog" />
            Account
          </router-link>
        </li>
      </template>
      <li>
        <router-link
          @click="$emit('closeSidebar')"
          to="/settings/profile">
          <icon
            class="mr-2 text-blue-500 w-5 h-5"
            icon="identification" />
          Profile
        </router-link>
      </li>
      <li v-if="currentOrganization">
        <router-link
          @click="$emit('closeSidebar')"
          to="/settings/members">
          <icon
            class="mr-2 text-blue-500 w-5 h-5"
            icon="user-group" />
          Members
        </router-link>
      </li>
      <li>
        <router-link
          @click="$emit('closeSidebar')"
          to="/settings/billing">
          <icon
            class="mr-2 text-blue-500 w-5 h-5"
            icon="credit-card" />
          Billing
        </router-link>
      </li>
      <li v-if="!currentOrganization">
        <router-link
          @click="$emit('closeSidebar')"
          to="/settings/organizations">
          <icon
            class="mr-2 text-blue-500 w-5 h-5"
            icon="collection" />
          Organizations
        </router-link>
      </li>

      <li>
        <router-link
          @click="$emit('closeSidebar')"
          to="/settings/developer-support">
          <icon
            class="mr-2 text-blue-500 w-5 h-5"
            icon="support" />
          Developer Support
        </router-link>
      </li>

      <template
        v-if="currentOrganization && user && user.enablePartnerFeatures">
        <!--<li><router-link @click="$emit('closeSidebar')" to="/settings/partner/overview">Partner Overview</router-link></li>-->
        <li>
          <router-link
            @click="$emit('closeSidebar')"
            to="/settings/partner/profile">
            <icon
              class="mr-2 text-blue-500 w-5 h-5"
              icon="check-circle" />
            Partner Listing
          </router-link>
        </li>
      </template>

      <li>
        <router-link
          @click="$emit('closeSidebar')"
          to="/settings/plugin-store">
          <icon
            class="mr-2 text-blue-500 w-5 h-5"
            icon="cog" />
          Plugin Store
        </router-link>
      </li>
    </ul>

    <!--
    <h5>Accessibility</h5>
    <ul>
        <li>
            <a @click.prevent="toggleDarkMode()">
                <template v-if="!darkMode">Dark Mode</template>
                <template v-else>Light Mode</template>
            </a>
        </li>
        <li>
            <a @click.prevent="toggleHighContrast()">

                <template v-if="!highContrast">High Contrast</template>
                <template v-else>Low Contrast</template>
            </a>
        </li>
    </ul>
    -->
  </div>
</template>

<script>
import {mapState, mapGetters} from 'vuex'
import OrganizationSwitcher from '@/console/js/components/OrganizationSwitcher';

export default {
  components: {OrganizationSwitcher},
  props: ['showingSidebar'],

  data() {
    return {
      darkMode: false,
      highContrast: false,
      showingOrganizationHud: false,
    }
  },

  computed: {
    ...mapState({
      user: state => state.account.user,
    }),

    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),

    ...mapGetters({
      userIsInGroup: 'account/userIsInGroup',
    }),
  },

  methods: {
    toggleDarkMode() {
      if (!this.darkMode) {
        window.document.body.classList.add('theme-dark')
        this.darkMode = true
      } else {
        window.document.body.classList.remove('theme-dark')
        this.darkMode = false
      }
    },

    toggleHighContrast() {
      if (!this.highContrast) {
        window.document.body.classList.add('high-contrast')
        this.highContrast = true
      } else {
        window.document.body.classList.remove('high-contrast')
        this.highContrast = false
      }
    },

    /**
     * Click away from the organization hud.
     */
    awayOrganizationHud: function() {
      if (this.showingOrganizationHud === true) {
        this.showingOrganizationHud = false
      }
    },

  }
}
</script>

<style lang="scss">
.sidebar {
  @apply hidden;

  h5 {
    @apply relative text-sm mb-1 px-3 mx-2 text-light;

    &:not(:first-child) {
      @apply mt-6;
    }
  }

  & > ul {
    li {
      a {
        @apply block text-sm text-gray-700 dark:text-gray-300 font-medium px-3 py-2 mx-2 mt-1 no-underline rounded-md flex items-center;

        .c-icon {
          @apply mr-2 text-blue-500 w-5 h-5;
        }

        &:hover {
          @apply text-gray-800 dark:text-gray-200 bg-gray-200 dark:bg-gray-700;
        }

        &.active {
          @apply bg-gray-200 dark:bg-gray-700 text-black dark:text-white;
        }

        &.disabled {
          @apply text-gray-300 dark:text-gray-700;
        }
      }
    }
  }
}
</style>