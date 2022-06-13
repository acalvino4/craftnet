/* global Craft */

import axios from 'axios'

export default {
  getInvoiceByNumber(number) {
    return axios.get(Craft.actionUrl + '/craftnet/console/invoices/get-invoice-by-number', {params: {number}})
  },

  getSubscriptionInvoices() {
    return axios.get(Craft.actionUrl + '/craftnet/console/invoices/get-subscription-invoices')
  }
}
