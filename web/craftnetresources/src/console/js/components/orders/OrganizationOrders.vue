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
          <th>Order Placed By</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="order in orders.data">
          <td>
            <router-link
              :to="getPrefixedTo('/settings/orders/' + order.number)"
            >
              {{ order.shortNumber }}
            </router-link>
          </td>
          <td>
            <template v-if="order.dateOrdered && order.dateOrdered.date">
              {{ order.dateOrdered.date }}
            </template>
          </td>
          <td>—</td>
        </tr>
        </tbody>
      </table>
    </template>

    <div class="mt-4 text-gray-500 text-sm">{{orders.total}} rows</div>
  </div>
</template>

<script>
import {mapGetters, mapState} from 'vuex';
import Spinner from '../../../../common/ui/components/Spinner';
import helpers from '../../mixins/helpers';

export default {
  mixins: [helpers],
  components: {Spinner},
  data() {
    return {
      loading: true,
    }
  },
  computed: {
    ...mapState({
      orders: state => state.orders.orders,
    }),
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization',
    }),
  },

  mounted() {
    this.$store.dispatch('orders/getOrders', {
        organizationId: this.currentOrganization.id
      })
      .then(() => {
        this.loading = false
      });
  }
}
</script>