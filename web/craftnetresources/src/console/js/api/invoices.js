/* global Craft */

import axios from 'axios'

export default {
  getInvoices() {
    return axios.get(`${VUE_APP_URL_CONSOLE}/invoices`);
  },

  getInvoiceByNumber(number) {
    return axios.get(`${VUE_APP_URL_CONSOLE}/invoices/${number}`);
  },

  getSubscriptionInvoices() {
    return axios.get(`${VUE_APP_URL_CONSOLE}/invoices/subscriptions`);
  }
}
