<template>
  <div>
    <data-table
      :url="apiUrl"
      :columns="vtColumns"
      :options="vtOptions">
      <template v-slot:number="props">
        <router-link
          :to="'/settings/orders/' + props.row.number">
          {{ props.row.shortNumber }}
        </router-link>
      </template>
      <template v-slot:price="props">
        {{ $filters.currency(props.row.totalPrice) }}
      </template>
      <template v-slot:date="props">
        <template v-if="props.row.datePaid">{{
            $filters.parseDate(props.row.datePaid.date).toFormat('yyyy-MM-dd')
          }}
        </template>
      </template>
      <template v-slot:receipt="props">
        <a :href="props.row.pdfUrl">Download Receipt</a>
      </template>
    </data-table>
  </div>
</template>

<script>
/* global Craft */

import DataTable from '@/console/js/components/DataTable';

export default {
  components: {DataTable},
  data() {
    return {
      vtColumns: ['number', 'price', 'date', 'receipt'],
      vtOptions: {
        texts: {
          filterPlaceholder: 'Order number…'
        },
      },
    }
  },

  computed: {
    apiUrl() {
      return Craft.actionUrl + '/craftnet/console/invoices/get-invoices'
    },
  },
}
</script>
