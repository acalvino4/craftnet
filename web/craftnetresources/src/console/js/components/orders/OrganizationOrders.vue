<template>
  <div>
    <template v-if="loading">
      <spinner />
    </template>
    <template v-else>
      <table>
        <thead>
        <tr>
          <th>Number</th>
          <th>Date Ordered</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="order in orders">
          <td>{{ order.number }}</td>
          <td>
            <template v-if="order.dateOrdered && order.dateOrdered.date">
              {{ order.dateOrdered.date }}
            </template>
          </td>
        </tr>
        </tbody>
      </table>
    </template>
  </div>
</template>

<script>
import {mapGetters, mapState} from 'vuex';
import Spinner from '../../../../common/ui/components/Spinner';

export default {
  components: {Spinner},
  data() {
    return {
      loading: true,
    }
  },
  computed: {
    ...mapState({
      orders: state => state.organizations.orders,
    }),
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization',
    }),
  },

  mounted() {
    this.$store.dispatch('organizations/getOrders', {
        organizationId: this.currentOrganization.id
      })
      .then(() => {
        this.loading = false
      });
  }
}
</script>