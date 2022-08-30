<template>
  <div>
    <page-header>
      <h1>Billing</h1>
    </page-header>

    <div class="space-y-6">
      <pane>
        <billing-cards />
      </pane>

      <pane>
        <billing-addresses />
      </pane>
    </div>
  </div>
</template>
<script>
import BillingCards from '../../../components/billing/BillingCards';
import BillingAddresses from '../../../components/billing/BillingAddresses';
import PageHeader from '../../../components/PageHeader';
export default {
  components: {PageHeader, BillingAddresses, BillingCards}
}
</script>