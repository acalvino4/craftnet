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
          v-model="paymentSourceId"
        />
      </field>
      <field
        label="Address"
      >
        <dropdown
          :options="addressOptions"
          v-model="billingAddressId"
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

    <pane class="mt-6">
      <h2><code>organization</code></h2>
      <pre class="mt-4">{{currentOrganization}}</pre>
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
      paymentSourceId: null,
      billingAddressId: null,
      requireOrderApproval: false,
    }
  },

  computed: {
    ...mapState({
      addresses: state => state.addresses.addresses,
      cards: state => state.stripe.cards,
    }),

    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization',
    }),

    addressOptions() {
      const options = [
        {
          label: 'Select an address',
          value: '',
        },
      ]

      for (const addressKey in this.addresses) {
        const address = this.addresses[addressKey]
        options.push({
          label: '#' + address.id,
          value: address.id,
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

      for (const cardKey in this.cards) {
        const card = this.cards[cardKey]
        options.push({
          label: `**** **** **** ${card.card.last4} - ${card.card.brand} - ${card.card.exp_month}/${card.card.exp_year}`,
          value: card.id,
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
        paymentSourceId: this.paymentSourceId,
        billingAddressId: this.billingAddressId,
      };

      this.$store.dispatch('organizations/saveOrganization', organization)
        .then(() => {
          this.saving = false;
          this.$store.dispatch('app/displayNotice', 'Billing settings saved.')
        })
        .catch(() => {
          this.saving = false;
          this.$store.dispatch('app/displayError', 'Couldn’t save billing settings.')
        })
    },
  },

  mounted() {
    this.requireOrderApproval = this.currentOrganization.requireOrderApproval;
    this.paymentSourceId = this.currentOrganization.paymentSourceId;
    this.billingAddressId = this.currentOrganization.billingAddressId;

    this.loading = true

    this.$store.dispatch('stripe/getCards')
      .then(() => {
        this.loading = false
      })
      .catch(() => {
        this.loading = false
        this.$store.dispatch('app/displayNotice', 'Couldn’t get credit cards.')
      })

    this.$store.dispatch('addresses/getAddresses');
  }
}
</script>