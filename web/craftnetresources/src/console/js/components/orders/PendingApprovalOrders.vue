<template>
  <div class="overflow-x-auto">
    <template v-if="pendingOrders">
      <table>
        <thead>
        <tr>
          <th>Number</th>
          <th>Requested by</th>
          <th class="w-px"></th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="pendingOrder in pendingOrders.data">
          <td>
            <router-link
              :to="getPrefixedTo('/settings/orders/' + pendingOrder.number + '/review')"
            >
              {{ pendingOrder.shortNumber }}
            </router-link>
          </td>
          <td>
            <template v-if="pendingOrder.approvalRequestedBy">
              <div>{{pendingOrder.approvalRequestedBy.name}}</div>
              <div>{{pendingOrder.approvalRequestedBy.email}}</div>
            </template>
            <template v-else>
              —
            </template>
          </td>
          <td>
            <template v-if="currentMemberIsOwner">
              <div class="space-x-2">
                <btn
                  :to="getPrefixedTo('/settings/orders/' + pendingOrder.number + '/review')"
                >Review order request</btn>
              </div>
            </template>

            <template v-else>
              <div>
                <em class="text-gray-500">Pending owner approval</em>
              </div>
            </template>
          </td>
        </tr>
        </tbody>
      </table>

      <div class="mt-4 text-gray-500 text-sm">{{pendingOrders.total}} rows</div>
    </template>
  </div>
</template>

<script>
import {mapGetters, mapState} from 'vuex';
import helpers from '../../mixins/helpers';

export default {
  mixins: [helpers],
  computed: {
    ...mapState({
      pendingOrders: state => state.orders.pendingOrders,
    }),
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization',
      currentMemberIsOwner: 'organizations/currentMemberIsOwner',
    }),
  },

  mounted() {
    this.$store.dispatch('orders/getPendingOrders', {
        organizationId: this.currentOrganization.id
      })

    this.$store.dispatch('organizations/getOrganizationMembers', {
      organizationId: this.currentOrganization.id
    })

    this.$store.dispatch('cart/getCart')
  }
}
</script>