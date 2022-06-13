<template>
  <div>
    <template v-if="loading">
      <spinner></spinner>
    </template>

    <template v-else>
      <data-table
        :data="subscriptionInvoices"
        :columns="vtColumns"
        :options="vtOptions">
        <template v-slot:date="props">
          {{ props.row.date }}
        </template>
        <template v-slot:amount="props">
          {{ $filters.currency(props.row.amount) }}
        </template>
        <template v-slot:url="props">
          <a
            :href="props.row.url"
            title="Download receipt">Download
            Receipt</a>
        </template>
      </data-table>
    </template>
  </div>
</template>

<script>
import {mapState, mapActions} from 'vuex'
import DataTable from '@/console/js/components/DataTable';

export default {
  components: {DataTable},
  data() {
    return {
      loading: false,
      vtColumns: ['date', 'amount', 'url'],
      vtOptions: {
        texts: {
          filterPlaceholder: 'Invoice number…'
        },
        headings: {
          'url': "Download",
        }
      },
    }
  },

  computed: {
    ...mapState({
      subscriptionInvoices: state => state.invoices.subscriptionInvoices,
    }),
  },

  methods: {
    ...mapActions({
      getSubscriptionInvoices: 'invoices/getSubscriptionInvoices',
    }),
  },

  mounted() {
    this.loading = true

    this.getSubscriptionInvoices()
      .then(() => {
        this.loading = false
      })
      .catch(() => {
        this.loading = false
      })
  }
}
</script>