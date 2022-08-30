/* global VUE_APP_URL_CONSOLE */

import axios from './axios'

export default {
  getInvoiceByNumber(number) {
    return axios.get(`${VUE_APP_URL_CONSOLE}/invoices/${number}`);
  },

  getSubscriptionInvoices() {
    return axios.get(`${VUE_APP_URL_CONSOLE}/invoices/subscriptions`);
  }
}
