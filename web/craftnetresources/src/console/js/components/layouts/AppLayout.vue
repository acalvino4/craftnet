<template>
  <div class="app">
    <div class="flex flex-1 app-layout">
      <!--<template v-if="typeof $route.meta.sidebar === 'undefined' || $route.meta.sidebar === true">-->
      <app-sidebar
        class="bg-gray-50 dark:bg-gray-800"
        :showingSidebar="showingSidebar"
        @closeSidebar="closeSidebar()"
        @toggleSidebar="toggleSidebar()"></app-sidebar>
      <!--</template>-->

      <div class="flex flex-col flex-1 bg-primary overflow-auto relative">
        <main-header
          context="app"
          :showingSidebar="showingSidebar"
          class="showingSidebar sticky z-10 top-0 bg-white bg-opacity-90 backdrop-blur ml-[0.02rem]"
          @toggleSidebar="toggleSidebar()"></main-header>

        <div
          id="main"
          class="main flex-1 p-6 md:p-8"
          :class="{'main-full': $route.meta.mainFull}">
          <div class="page-alerts">
            <template v-if="$route.meta.cmsLicensesRenewAlert">
              <license-renew-alert
                type="CMS"
                :expiring-licenses-total="expiringCmsLicensesTotal"></license-renew-alert>
            </template>

            <template v-if="$route.meta.pluginLicensesRenewAlert">
              <license-renew-alert
                type="plugin"
                :expiring-licenses-total="expiringPluginLicensesTotal"></license-renew-alert>
            </template>
          </div>

          <div class="main-content">
            <router-view
              :key="(currentOrganization ? currentOrganization.id : 'personal') + '-' + $route.path"></router-view>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {mapGetters, mapState} from 'vuex'
import LicenseRenewAlert from '../LicenseRenewAlert'
import AppSidebar from '../AppSidebar'
import MainHeader from '@/console/js/components/MainHeader'

export default {
  components: {
    LicenseRenewAlert,
    AppSidebar,
    MainHeader,
  },

  data() {
    return {
      showingSidebar: false,
    }
  },

  computed: {
    ...mapState({
      expiringCmsLicensesTotal: state => state.cmsLicenses.expiringCmsLicensesTotal,
      expiringPluginLicensesTotal: state => state.pluginLicenses.expiringPluginLicensesTotal,
    }),

    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),
  },

  methods: {
    /**
     * Toggles the sidebar.
     */
    toggleSidebar() {
      this.showingSidebar = !this.showingSidebar
    },

    /**
     * Closes the sidebar.
     */
    closeSidebar() {
      this.showingSidebar = false
    },
  }
}
</script>

<style lang="scss">
.app {
  @apply fixed inset-0 flex flex-col;

  .header {
    .header-left {
      #sidebar-toggle {
        @apply mr-6 text-gray-800 text-center;
        width: 14px;

        &:hover {
          @apply text-black;
        }
      }
    }
  }
}

@media (max-width: 767px) {
  .app {
    .app-layout {
      @apply flex-col;
    }

    .sidebar {
      &.showing-sidebar {
        @apply block bg-primary absolute inset-0 z-10;
        top: 57px;
      }
    }
  }
}

@media (min-width: 768px) {
  .app {
    .header {
      .header-left {
        #sidebar-toggle {
          @apply hidden;
        }
      }
    }

    .sidebar {
      @apply block;
    }
  }
}

/* Main */

.main {
  @apply flex-1;

  &:not(.main-full) {
    @apply py-6;

    .main-content {
      @apply mx-auto max-w-screen-xl;
    }
  }

  &.main-full {
    @apply flex;

    .main-content {
      @apply flex flex-1;
    }
  }

  .main-content {
    .top-alert {
      @apply -mx-8 -mt-6 rounded-none px-8 mb-6;
      border: 0;
    }
  }
}
</style>