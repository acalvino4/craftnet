<template>
  <form @submit.prevent="save">
    <page-header class="flex items-center justify-between">
      <h1>Billing</h1>

      <div class="flex items-center space-x-4">
        <template v-if="saving">
          <spinner />
        </template>
        <btn kind="primary" type="submit">Save</btn>
      </div>
    </page-header>

    <pane class="mt-6">
      <field
        :first="true"
        label="Credit Card"
      >
        <dropdown
          :options="cardOptions"
          v-model="paymentMethodId"
        />
      </field>

      <field
        label="Require order approval"
        label-for="requireOrderApproval"
      >
        <lightswitch
          id="requireOrderApproval"
          v-model:checked="requireOrderApproval"
        />
      </field>
    </pane>
  </form>
</template>

<script>
import {mapGetters, mapState} from 'vuex';
import Spinner from '../../../../common/ui/components/Spinner';
import PageHeader from '../PageHeader';

export default {
  components: {PageHeader, Spinner},
  data() {
    return {
      loading: false,
      saving: false,
      paymentMethodId: null,
      requireOrderApproval: false,
    }
  },

  computed: {
    ...mapState({
      addresses: state => state.addresses.addresses,
      paymentMethods: state => state.paymentMethods.paymentMethods,
      user: state => state.account.user,
    }),

    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization',
      userIsOwner: 'organizations/userIsOwner',
    }),

    addressOptions() {
      const options = [
        {
          label: 'Select an address',
          value: '',
        },
      ]

      if (this.addresses) {
        this.addresses.forEach(address => {
          options.push({
            label: '#' + address.id,
            value: address.id,
          })
        })
      }

      return options
    },

    cardOptions() {
      const options = [
        {
          label: 'Select a card',
          value: '',
        },
      ]

      for (const paymentMethodKey in this.paymentMethods) {
        const paymentMethod = this.paymentMethods[paymentMethodKey]
        options.push({
          label: `**** **** **** ${paymentMethod.card.last4} - ${paymentMethod.card.brand} - ${paymentMethod.card.exp_month}/${paymentMethod.card.exp_year}`,
          value: paymentMethod.id,
        })
      }

      return options
    }
  },

  methods: {
    save() {
      this.saving = true;

      const organization = {
        id: this.currentOrganization.id,
        fields: {
          requireOrderApproval: this.requireOrderApproval ? 1 : 0,
        },
        paymentMethodId: this.paymentMethodId,
      };

      this.$store.dispatch('organizations/saveOrganization', organization)
        .then(() => {
          this.saving = false;
          this.$store.dispatch('app/displayNotice', 'Billing settings saved.')

          this.$store.dispatch('organizations/getOrganizations')
        })
        .catch(() => {
          this.saving = false;
          this.$store.dispatch('app/displayError', 'Couldn’t save billing settings.')
        })
    },
  },

  mounted() {
    if (!this.userIsOwner(this.user.id)) {
      this.$router.push('/')
    }

    this.requireOrderApproval = this.currentOrganization.requireOrderApproval;
    this.paymentMethodId = this.currentOrganization.paymentMethodId;

    this.loading = true

    this.$store.dispatch('paymentMethods/getPaymentMethods')
      .then(() => {
        this.loading = false
      })
      // .catch(() => {
      //   this.loading = false
      //   this.$store.dispatch('app/displayNotice', 'Couldn’t get payment methods.')
      // })

    this.$store.dispatch('addresses/getAddresses');
  }
}
</script>