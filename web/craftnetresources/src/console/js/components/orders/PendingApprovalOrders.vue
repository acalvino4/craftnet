<template>
  <div class="overflow-x-auto">
    <template v-if="pendingOrders">
      <table>
        <thead>
        <tr>
          <th>Number</th>
          <th>Date Ordered</th>
          <th>Requested by</th>
          <th>Rejected by</th>
          <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="pendingOrder in pendingOrders.data">
          <td>{{ pendingOrder.number }}</td>
          <td>
            <template v-if="pendingOrder.dateOrdered && pendingOrder.dateOrdered.date">
              {{ pendingOrder.dateOrdered.date }}
            </template>
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
            <template v-if="pendingOrder.approvalRejectedBy">
              <div>{{pendingOrder.approvalRejectedBy.name}}</div>
              <div>{{pendingOrder.approvalRejectedBy.email}}</div>
            </template>
            <template v-else>
              —
            </template>
          </td>
          <td class="space-x-2">
            <btn kind="primary" @click="approveRequest(pendingOrder)">Approve</btn>
            <btn kind="danger" @click="rejectRequest(pendingOrder)">Reject</btn>
          </td>
        </tr>
        </tbody>
      </table>
    </template>
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

  methods: {
    approveRequest(pendingOrder) {
      console.log('approveRequest', pendingOrder);
      this.$store.dispatch('cart/saveCart', {
        orgId: 903717,
      })
        .then(() => {
          this.$store.dispatch('app/displayNotice', 'Cart saved.')

          this.$store.dispatch('cart/checkout', {
            orderNumber: this.cart.number,
            // token: this.selectedPaymentSource.token,
            // expectedPrice: this.cart.totalPrice,
            // // makePrimary: this.replaceCard,
          })
            .then(() => {
              this.$store.dispatch('app/displayNotice', 'Checkout success.')
            })
            .catch(() => {
              this.$store.dispatch('app/displayError', 'Couldn’t checkout.')
            })
        })
        .catch(() => {
          this.$store.dispatch('app/displayError', 'Couldn’t save cart.')
        })

    },
    rejectRequest(pendingOrder) {
      this.$store.dispatch('organizations/rejectRequest', {
        organizationId: this.currentOrganization.id,
        orderNumber: pendingOrder.number,
      })
        .then(() => {
          this.$store.dispatch('app/displayNotice', 'Request rejected.')
        })
        .catch(() => {
          this.$store.dispatch('app/displayError', 'Couldn’t reject request.')
        })
    },
  },

  mounted() {
    this.$store.dispatch('organizations/getPendingOrders', {
        organizationId: this.currentOrganization.id
      })
  }
}
</script>