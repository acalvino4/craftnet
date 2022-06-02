<template>
    <div>
        <template v-if="!loading">
            <template v-if="invoice">
                <p>
                    <router-link class="nav-link" to="/settings/billing" exact>←
                        Billing
                    </router-link>
                </p>
                <h1>Invoice {{ invoice.shortNumber }}</h1>

                <pane>
                    <dl>
                        <dt>Invoice Number</dt>
                        <dd>{{ invoice.number }}</dd>

                        <dt>Date Paid</dt>
                        <dd>{{
                                $filters.parseDate(invoice.datePaid.date).toFormat('ff')
                            }}
                        </dd>
                    </dl>

                    <billing-address :address="invoice.billingAddress"
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
                        <tr v-for="(lineItem, lineItemKey) in invoice.lineItems"
                            :key="'line-item-' + lineItemKey">
                            <td>{{ lineItem.description }}</td>
                            <td>{{ $filters.currency(lineItem.salePrice) }}</td>
                            <td>{{ lineItem.qty }}</td>
                            <td class="text-right">
                                {{ $filters.currency(lineItem.subtotal) }}
                            </td>
                        </tr>
                        <tr v-for="(adjustment, adjustmentKey) in invoice.adjustments"
                            :key="'adjustment-' + adjustmentKey">
                            <th colspan="3" class="text-right">
                                {{ adjustment.name }}
                            </th>
                            <td class="text-right">
                                {{ $filters.currency(adjustment.amount) }}
                            </td>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-right">Items Price</th>
                            <td class="text-right">
                                {{ $filters.currency(invoice.itemTotal) }}
                            </td>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-right">Total Price</th>
                            <td class="text-right">
                                {{ $filters.currency(invoice.totalPrice) }}
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
                        <tr v-for="(transaction, transactionKey) in invoice.transactions"
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

                <pane v-if="invoice.cmsLicenses.length">
                    <h3 class="mb-2">CMS Licenses</h3>
                    <cms-licenses-table
                        :licenses="invoice.cmsLicenses"></cms-licenses-table>
                </pane>

                <pane v-if="invoice.pluginLicenses.length">
                    <h3 class="mb-2">Plugin Licenses</h3>
                    <plugin-licenses-table
                        :licenses="invoice.pluginLicenses"></plugin-licenses-table>
                </pane>
            </template>
        </template>
        <template v-else>
            <spinner></spinner>
        </template>
    </div>
</template>

<script>
import invoicesApi from '../../../../api/invoices'
import BillingAddress from '../../../../components/billing/BillingAddress'
import CmsLicensesTable from '../../../../components/licenses/CmsLicensesTable'
import PluginLicensesTable from '../../../../components/licenses/PluginLicensesTable'

export default {
    components: {
        BillingAddress,
        CmsLicensesTable,
        PluginLicensesTable,
    },

    data() {
        return {
            loading: false,
            invoice: null,
            error: false,
        }
    },

    mounted() {
        const invoiceNumber = this.$route.params.number

        this.loading = true
        this.error = false

        invoicesApi.getInvoiceByNumber(invoiceNumber)
            .then((response) => {
                this.invoice = response.data
                this.loading = false
            })

            .catch(() => {
                this.loading = false
                this.error = true
            })
    }
}
</script>
