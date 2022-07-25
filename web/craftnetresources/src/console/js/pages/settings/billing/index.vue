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
    </div>
  </div>
</template>

<script>
import BillingPayment from '../../../components/billing/BillingPayment'
import BillingInvoiceDetails from '../../../components/billing/BillingInvoiceDetails'
import BillingAddressForm from '../../../components/billing/BillingAddressForm'
import PageHeader from '@/console/js/components/PageHeader'

export default {
  components: {
    BillingPayment,
    BillingInvoiceDetails,
    BillingAddressForm,
    PageHeader,
  },
}
</script>
