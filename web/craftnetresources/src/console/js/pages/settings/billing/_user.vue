<template>
  <div>
    <page-header>
      <h1>Billing</h1>
    </page-header>

    <div class="space-y-6">
      <pane>
        <payment-methods />
      </pane>
    </div>
  </div>
</template>
<script>
import PaymentMethods from '../../../components/billing/PaymentMethods';
import PageHeader from '../../../components/PageHeader';
export default {
  components: {
    PageHeader,
    PaymentMethods
  }
}
</script>