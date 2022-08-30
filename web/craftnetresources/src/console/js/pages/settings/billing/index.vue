<template>
  <div>
    <template v-if="currentOrganization">
      <org-billing />
    </template>
    <template v-else>
      <user-billing />
    </template>

    <!--
    <pane>
      <billing-address-form></billing-address-form>
    </pane>

    <pane>
      <billing-invoice-details></billing-invoice-details>
    </pane>
    -->
  </div>
</template>

<script>
// import BillingInvoiceDetails from '../../../components/billing/BillingInvoiceDetails'
// import BillingAddressForm from '../../../components/billing/BillingAddressForm'
import {mapGetters} from 'vuex';
import OrgBilling from './_org';
import UserBilling from './_user';

export default {
  components: {
    UserBilling,
    OrgBilling,
    // BillingInvoiceDetails,
    // BillingAddressForm,
  },

  computed: {
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),
  },
}
</script>
