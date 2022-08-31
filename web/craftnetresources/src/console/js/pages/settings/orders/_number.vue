<template>
  <div>
    <template v-if="!loading">
      <template v-if="order">
        <p>
          <router-link
            class="nav-link"
            :to="getPrefixedTo('/settings/orders')"
            exact>←
            Orders
          </router-link>
        </p>
        <h1>Order {{ order.shortNumber }}</h1>

        <pane>
          <dl>
            <dt>Order Number</dt>
            <dd>{{ order.number }}</dd>

            <dt>Date Paid</dt>
            <dd>
              <template v-if="order.datePaid">
                {{$filters.parseDate(order.datePaid.date).toFormat('ff') }}
              </template>
              <template v-else>
                Not paid
              </template>
            </dd>
          </dl>

          <billing-address
            :address="order.billingAddress"
            class="mb-4"></billing-address>

          <table class="table">
            <thead>
            <tr>
              <th>Item</th>
              <th>Price</th>
              <th>Quantity</th>
              <th></th>
            </tr>
            </thead>
            <tbody>
            <tr
              v-for="(lineItem, lineItemKey) in order.lineItems"
              :key="'line-item-' + lineItemKey">
              <td>{{ lineItem.description }}</td>
              <td>{{ $filters.currency(lineItem.salePrice) }}</td>
              <td>{{ lineItem.qty }}</td>
              <td class="text-right">
                {{ $filters.currency(lineItem.subtotal) }}
              </td>
            </tr>
            <tr
              v-for="(adjustment, adjustmentKey) in order.adjustments"
              :key="'adjustment-' + adjustmentKey">
              <th
                colspan="3"
                class="text-right">
                {{ adjustment.name }}
              </th>
              <td class="text-right">
                {{ $filters.currency(adjustment.amount) }}
              </td>
            </tr>
            <tr>
              <th
                colspan="3"
                class="text-right">Items Price
              </th>
              <td class="text-right">
                {{ $filters.currency(order.itemTotal) }}
              </td>
            </tr>
            <tr>
              <th
                colspan="3"
                class="text-right">Total Price
              </th>
              <td class="text-right">
                {{ $filters.currency(order.totalPrice) }}
              </td>
            </tr>
            </tbody>
          </table>
        </pane>

        <pane>
          <h3 class="mb-2">Transactions</h3>

          <table class="table">
            <thead>
            <tr>
              <th>Type</th>
              <th>Status</th>
              <th>Amount</th>
              <th>Payment Amount</th>
              <th>Method</th>
              <th>Date</th>
            </tr>
            </thead>
            <tbody>
            <tr
              v-for="(transaction, transactionKey) in order.transactions"
              :key="'transaction-' + transactionKey">
              <td>{{ transaction.type }}</td>
              <td>{{ transaction.status }}</td>
              <td>{{ $filters.currency(transaction.amount) }}</td>
              <td>{{
                  $filters.currency(transaction.paymentAmount)
                }}
              </td>
              <td>{{ transaction.gatewayName }}</td>
              <td>{{
                  $filters.parseDate(transaction.dateCreated.date).toFormat('ff')
                }}
              </td>
            </tr>
            </tbody>
          </table>
        </pane>

        <pane v-if="order.cmsLicenses && order.cmsLicenses.length">
          <h3 class="mb-2">CMS Licenses</h3>
          <cms-licenses-table
            :licenses="order.cmsLicenses"></cms-licenses-table>
        </pane>

        <pane v-if="order.pluginLicenses && order.pluginLicenses.length">
          <h3 class="mb-2">Plugin Licenses</h3>
          <plugin-licenses-table
            :licenses="order.pluginLicenses"></plugin-licenses-table>
        </pane>
      </template>
    </template>
    <template v-else>
      <spinner></spinner>
    </template>
  </div>
</template>

<script>
import ordersApi from '../../../api/orders'
import BillingAddress from '../../../components/billing/BillingAddress'
import CmsLicensesTable from '../../../components/licenses/CmsLicensesTable'
import PluginLicensesTable from '../../../components/licenses/PluginLicensesTable'
import helpers from '../../../mixins/helpers';
import {mapGetters} from 'vuex';

export default {
  mixins: [helpers],

  components: {
    BillingAddress,
    CmsLicensesTable,
    PluginLicensesTable,
  },

  data() {
    return {
      loading: false,
      order: null,
      error: false,
    }
  },

  computed: {
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),
  },

  mounted() {
    const orderNumber = this.$route.params.number
    const orgId = this.currentOrganization ? this.currentOrganization.id : null

    this.loading = true
    this.error = false

    ordersApi.getOrder(orderNumber, orgId)
      .then((response) => {
        this.order = response.data.order
        this.loading = false
      })

      .catch(() => {
        this.loading = false
        this.error = true
      })
  }
}
</script>
