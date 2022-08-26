<template>
  <component :is="computedComponent"></component>
</template>

<script>
import {mapGetters} from 'vuex';
import PersonalIndex from './_personal';
import OrganizationIndex from './_organization';
import {checkRoute} from '../../../helpers/check-route';

export default {
  computed: {
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),

    computedComponent() {
      if (this.currentOrganization) {
        return OrganizationIndex
      }

      return PersonalIndex
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