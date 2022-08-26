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
import OrgBilling from '../../../components/billing/OrgBilling';
import UserBilling from '../../../components/billing/UserBilling';
import {checkRoute} from '../../../helpers/check-route';

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

  mounted() {
    checkRoute( {
      currentOrganization: this.currentOrganization,
      $router: this.$router,
      $route: this.$route,
      orgRouteName: 'OrgBilling',
      userRouteName: 'UserBilling'
    })
  }
}
</script>
