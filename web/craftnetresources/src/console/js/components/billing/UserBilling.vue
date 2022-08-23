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
import BillingCards from './BillingCards';
import BillingAddresses from './BillingAddresses';
import PageHeader from '../PageHeader';
export default {
  components: {PageHeader, BillingAddresses, BillingCards}
}
</script>