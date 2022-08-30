/* global VUE_APP_CRAFT_API_ENDPOINT */

import axios from './axios'
axios.defaults.baseURL = VUE_APP_CRAFT_API_ENDPOINT + '/'

export default {
  /**
   * Returns the axios instance for calls to the Craft API.
   */
  axios() {
    return axios
  },

  /**
   * Get cart.
   */
  getCart(orderNumber) {
    return this.axios().get('carts/' + orderNumber)
  },

  /**
   * Create cart.
   */
  createCart(data) {
    return this.axios().post('carts', data)
  },

  /**
   * Update cart.
   */
  updateCart(orderNumber, data) {
    return this.axios().post('carts/' + orderNumber, data, {
      withCredentials: true,
    })
  },

  /**
   * Checkout.
   */
  checkout(data) {
    return this.axios().post('payments', data, {
      withCredentials: true,
    })
  },

  /**
   * Reset order number.
   */
  resetOrderNumber() {
    localStorage.removeItem('orderNumber')
  },

  /**
   * Save order number
   */
  saveOrderNumber(orderNumber) {
    localStorage.setItem('orderNumber', orderNumber)
  },

  /**
   * Get order number.
   */
  getOrderNumber(cb) {
    const orderNumber = localStorage.getItem('orderNumber')

    return cb(orderNumber)
  },
}