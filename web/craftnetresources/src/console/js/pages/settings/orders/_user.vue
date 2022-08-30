<template>
  <div>
    <h1 class="m-0">Orders</h1>
    <div class="mt-6 space-y-6">
      <pane>
        <h2>Orders</h2>
        <invoices-table class="mt-4"></invoices-table>
      </pane>

      <pane>
        <h2>Subscription Orders</h2>
        <subscription-invoices-table class="mt-4"></subscription-invoices-table>
      </pane>
    </div>
  </div>
</template>

<script>
import {mapGetters} from 'vuex';
import SubscriptionInvoicesTable from '../../../components/billing/SubscriptionInvoicesTable';
import InvoicesTable from '../../../components/billing/InvoicesTable';

export default {
  components: {
    InvoicesTable,
    SubscriptionInvoicesTable
  },
  computed: {
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),
  }
}
</script>