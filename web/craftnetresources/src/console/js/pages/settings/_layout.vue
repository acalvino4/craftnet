<template>
  <sidebar-layout>
    <ul>
      <li>
        <router-link
          @click="$emit('closeSidebar')"
          to="/settings/account">Account
        </router-link>
      </li>
      <li>
        <router-link
          @click="$emit('closeSidebar')"
          to="/settings/profile">Profile
        </router-link>
      </li>
      <li>
        <router-link
          @click="$emit('closeSidebar')"
          to="/settings/billing">Billing
        </router-link>
      </li>
      <li>
        <router-link
          @click="$emit('closeSidebar')"
          to="/settings/apps">
          Connected Apps
        </router-link>
      </li>
      <li>
        <router-link
          @click="$emit('closeSidebar')"
          to="/settings/api-access">API Access
        </router-link>
      </li>
      <li>
        <router-link
          @click="$emit('closeSidebar')"
          to="/settings/developer-support">Developer Support
        </router-link>
      </li>

      <template v-if="user && user.enablePartnerFeatures">
        <li>
          <router-link
            @click="$emit('closeSidebar')"
            to="/settings/partner/overview">Partner
            Overview
          </router-link>
        </li>
        <li>
          <router-link
            @click="$emit('closeSidebar')"
            to="/settings/partner/profile">Partner Listing
          </router-link>
        </li>
      </template>
    </ul>
  </sidebar-layout>
</template>


<script>
import {mapState} from 'vuex'
import SidebarLayout from '@/console/js/components/SidebarLayout'

export default {
  components: {
    SidebarLayout
  },

  computed: {
    ...mapState({
      user: state => state.account.user,
    }),
  },
}
</script>
