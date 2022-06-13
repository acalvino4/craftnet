<template>
  <div>
    <page-header>
      <h1>Billing</h1>
    </page-header>

    <div class="space-y-6">
      <pane>
        <billing-payment></billing-payment>
      </pane>

      <pane>
        <billing-address-form></billing-address-form>
      </pane>

      <pane>
        <billing-invoice-details></billing-invoice-details>
      </pane>

      <pane>
        <h2>Invoices</h2>

        <invoices-table class="mt-4"></invoices-table>
      </pane>

      <pane>
        <h2>Subscription Invoices</h2>
        <subscription-invoices-table class="mt-4"></subscription-invoices-table>
      </pane>
    </div>
  </div>
</template>

<script>
import BillingPayment from '../../../components/billing/BillingPayment'
import BillingInvoiceDetails from '../../../components/billing/BillingInvoiceDetails'
import BillingAddressForm from '../../../components/billing/BillingAddressForm'
import InvoicesTable from '../../../components/billing/InvoicesTable'
import SubscriptionInvoicesTable from '../../../components/billing/SubscriptionInvoicesTable'
import PageHeader from '@/console/js/components/PageHeader'

export default {
  components: {
    BillingPayment,
    BillingInvoiceDetails,
    BillingAddressForm,
    InvoicesTable,
    SubscriptionInvoicesTable,
    PageHeader,
  },
}
</script>
