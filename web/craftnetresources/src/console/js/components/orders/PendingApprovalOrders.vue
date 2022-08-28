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
          <td>
            <template v-if="userIsOwner">

            <div class="space-x-2">
              <btn :disabled="!userIsOwner" kind="primary" @click="approveRequest(pendingOrder)">Approve</btn>
              <btn :disabled="!userIsOwner" kind="danger" @click="rejectRequest(pendingOrder)">Reject</btn>
            </div>
            </template>

            <template v-else>
              <div class="mt-2">
                <em>Pending owner approval</em>
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

export default {
  computed: {
    ...mapState({
      pendingOrders: state => state.organizations.pendingOrders,
      user: state => state.account.user,
      members: state => state.organizations.members,
      cart: state => state.cart.cart,
    }),
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization',
    }),

    userIsOwner() {
      if (!this.members) {
        return false
      }

      return !!this.members.find(member => {
        return (member.id === this.user.id && member.role === 'owner')
      })
    }
  },

  methods: {
    approveRequest(pendingOrder) {
      console.log('approveRequest', pendingOrder);
      this.$store.dispatch('cart/saveCart', {
        orgId: this.currentOrganization.id,
      })
        .then(() => {
          this.$store.dispatch('app/displayNotice', 'Cart saved.')

          this.$store.dispatch('cart/checkout', {
            orderNumber: this.cart.number,
            // token: this.selectedPaymentMethod.token,
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

    this.$store.dispatch('organizations/getOrganizationMembers', {
      organizationId: this.currentOrganization.id
    })

    this.$store.dispatch('cart/getCart')
  }
}
</script>