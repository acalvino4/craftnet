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
    if (this.currentOrganization && this.$route.params.orgSlug !== this.currentOrganization.slug) {
      // Redirect to the right org profile if the org slug is different than the current org slug.
      this.$router.push({
        name: 'OrgBilling',
        params: {
          orgSlug: this.currentOrganization.slug,
        },
      })
    } else if (!this.currentOrganization && this.$route.params.orgSlug) {
      // Redirect to the user profile if the org slug is provided but there is no current org.
      this.$router.push({
        name: 'UserBilling',
      })
    }
  }
}
</script>
