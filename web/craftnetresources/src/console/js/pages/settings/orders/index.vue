<template>
  <component :is="computedComponent"></component>
</template>

<script>
import {mapGetters} from 'vuex';
import UserIndex from './_user';
import OrgIndex from './_org';
import {checkRoute} from '../../../helpers/check-route';

export default {
  computed: {
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),

    computedComponent() {
      if (this.currentOrganization) {
        return OrgIndex
      }

      return UserIndex
    }
  },

  mounted() {
    // checkRoute( {
    //   currentOrganization: this.currentOrganization,
    //   $router: this.$router,
    //   $route: this.$route,
    //   orgRouteName: 'OrgOrders',
    //   userRouteName: 'UserOrders'
    // })
  }
}
</script>