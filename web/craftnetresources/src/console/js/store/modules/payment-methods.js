import paymentMethodsApi from '../../api/payment-methods'

/**
 * State
 */
const state = {
  card: null,
  cardToken: null,
  paymentMethods: [],
  paymentMethodsCheckout: [],
}

/**
 * Getters
 */
const getters = {}

/**
 * Actions
 */
const actions = {
  savePaymentMethod(context, data) {
    return new Promise((resolve, reject) => {
      paymentMethodsApi.savePaymentMethod(data)
        .then((response) => {
          resolve(response)
        })
        .catch((error) => {
          reject(error.response)
        })
    })
  },

  removePaymentMethod({dispatch}, paymentMethodId) {
    return new Promise((resolve, reject) => {
      paymentMethodsApi.removePaymentMethod(paymentMethodId)
        .then((removePaymentMethodResponse) => {
          dispatch('getPaymentMethods')
            .then((getPaymentMethodsResponse) => {
              resolve({
                removePaymentMethodResponse,
                getPaymentMethodsResponse
              })
            })
        })
        .catch((error) => {
          reject(error.response)
        })
    })
  },

  getPaymentMethods({commit}) {
    return new Promise((resolve, reject) => {
      paymentMethodsApi.getPaymentMethods()
        .then((response) => {
          commit('updatePaymentMethods', {paymentMethods: response.data.paymentMethods})
          resolve(response)
        })
        .catch((error) => {
          reject(error)
        })
    })
  },

  getPaymentMethodsCheckout({commit}) {
    return new Promise((resolve, reject) => {
      paymentMethodsApi.getPaymentMethodsCheckout()
        .then((response) => {
          commit('updatePaymentMethodsCheckout', {paymentMethodsCheckout: response.data.paymentMethods})
          resolve(response)
        })
        .catch((error) => {
          reject(error)
        })
    })
  },
}

/**
 * Mutations
 */
const mutations = {
  updateStripeCard(state, {card}) {
    state.card = card
  },

  updatePaymentMethods(state, {paymentMethods}) {
    state.paymentMethods = paymentMethods
  },

  updatePaymentMethodsCheckout(state, {paymentMethodsCheckout}) {
    state.paymentMethodsCheckout = paymentMethodsCheckout
  },

  updateCard(state, {card}) {
    state.card = card
  },

  updateCardToken(state, {cardToken}) {
    state.cardToken = cardToken
  },
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
