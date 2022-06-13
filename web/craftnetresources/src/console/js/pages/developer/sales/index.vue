<template>
  <div>
    <page-header>
      <h1>Plugin Sales</h1>
    </page-header>

    <data-table
      :url="apiUrl"
      :columns="vtColumns"
      :options="vtOptions">
      <template v-slot:item="props">
        {{ props.row.plugin.name }}

        <template
          v-if="props.row.plugin.hasMultipleEditions && props.row.edition">
          <edition-badge class="ml-2">{{ props.row.edition.name }}
          </edition-badge>
        </template>
      </template>

      <template v-slot:customer="props">
        <a :href="'mailto:'+props.row.customer.email">{{
            props.row.customer.email
          }}</a>
      </template>

      <template v-slot:type="props">
        <template
          v-if="props.row.purchasableType === 'craftnet\\plugins\\PluginRenewal'">
          Renewal
        </template>
        <template v-else>
          License
        </template>

        <div
          class="text-light"
          v-for="(adjustment, adjustmentKey) in props.row.adjustments"
          :key="'adjustment-' + adjustmentKey">
          {{ adjustment.name }}
        </div>
      </template>

      <template v-slot:grossAmount="props">
        {{ $filters.currency(props.row.grossAmount) }}
      </template>

      <template v-slot:netAmount="props">
        {{ $filters.currency(props.row.netAmount) }}
      </template>

      <template v-slot:date="props">
        {{ $filters.parseDate(props.row.saleTime).toFormat('ff') }}
      </template>
    </data-table>
  </div>
</template>

<script>
/* global Craft */

import EditionBadge from '../../../components/EditionBadge'
import PageHeader from '@/console/js/components/PageHeader'
import DataTable from '@/console/js/components/DataTable';

export default {
  components: {
    DataTable,
    EditionBadge,
    PageHeader,
  },

  data() {
    return {
      vtColumns: ['item', 'customer', 'type', 'grossAmount', 'netAmount', 'date'],
      vtOptions: {
        filterable: false,
        headings: {
          'grossAmount': "Gross Amount",
          'netAmount': "Net Amount",
        }
      },
    }
  },

  computed: {
    apiUrl() {
      return Craft.actionUrl + '/craftnet/console/sales/get-sales'
    },
  },
}
</script>
