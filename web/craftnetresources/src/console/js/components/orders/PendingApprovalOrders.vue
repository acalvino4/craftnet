<template>
  <div>
    [Paginated list of pending orders]

    <hr>

    <h3>Debug</h3>
    <pre>{{pendingOrders}}</pre>
  </div>
</template>

<script>
import {mapGetters, mapState} from 'vuex';

export default {
  computed: {
    ...mapState({
      pendingOrders: state => state.organizations.pendingOrders,
    }),
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization',
    }),
  },

  mounted() {
    this.$store.dispatch('organizations/getPendingOrders', {
        organizationId: this.currentOrganization.id
      })
  }
}
</script>