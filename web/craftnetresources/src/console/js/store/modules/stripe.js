import stripeApi from '../../api/stripe'

/**
 * State
 */
const state = {
  card: null,
  paymentMethods: null,
  cardToken: null,
  stripeAccount: null,
  paymentSources: [],
}

/**
 * Getters
 */
const getters = {}

/**
 * Actions
 */
const actions = {
  disconnectStripeAccount({commit}) {
    return new Promise((resolve, reject) => {
      stripeApi.disconnect()
        .then((response) => {
          commit('disconnectStripeAccount')
          resolve(response)
        })
        .catch((response) => {
          reject(response)
        })
    })
  },

  getStripeAccount({commit}) {
    return new Promise((resolve, reject) => {
      stripeApi.getAccount()
        .then((response) => {
          commit('updateStripeAccount', {response})
          resolve(response)
        })
        .catch((response) => {
          reject(response)
        })
    })
  },

  removeCard({dispatch}, cardId) {
    console.log('cardId', cardId)
    return new Promise((resolve, reject) => {
      stripeApi.removeCard(cardId)
        .then((removeCardResponse) => {
          dispatch('getPaymentMethods')
            .then((getPaymentMethodsResponse) => {
              resolve({
                removeCardResponse,
                getPaymentMethodsResponse
              })
            })
        })
        .catch((error) => {
          reject(error.response)
        })
    })
  },

  addCard({commit}, source) {
    return new Promise((resolve, reject) => {
      stripeApi.addCard(source)
        .then((response) => {
          if (!response.data.error) {
            commit('updateStripeCard', {card: response.data.card.card})
            resolve(response)
          } else {
            reject(response)
          }
        })
        .catch((error) => {
          reject(error.response)
        })
    })
  },

  saveCard(context, {paymentSourceId, card}) {
    return new Promise((resolve, reject) => {
      stripeApi.saveCard({paymentSourceId, card})
        .then((response) => {
          resolve(response)
        })
        .catch((error) => {
          reject(error.response)
        })
    })
  },

  getPaymentMethods({commit}) {
    return new Promise((resolve, reject) => {
      stripeApi.getPaymentMethods()
        .then((response) => {
          commit('updatePaymentMethods', {paymentMethods: response.data.paymentMethods})
          resolve(response)
        })
        .catch((error) => {
          reject(error)
        })
    })
  },

  getPaymentSources({commit}) {
    return new Promise((resolve, reject) => {
      stripeApi.getPaymentSources()
        .then((response) => {
          commit('updatePaymentSources', {paymentSources: response.data.paymentSources})
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
  disconnectStripeAccount(state) {
    state.stripeAccount = null
  },

  removeStripeCard(state) {
    state.card = null
  },

  updateCard(state, {card}) {
    state.card = card
  },

  updatePaymentMethods(state, {paymentMethods}) {
    state.paymentMethods = paymentMethods
  },

  updateCardToken(state, {cardToken}) {
    state.cardToken = cardToken
  },

  updateStripeAccount(state, {response}) {
    state.stripeAccount = response.data
  },

  updateStripeCard(state, {card}) {
    state.card = card
  },

  updatePaymentSources(state, {paymentSources}) {
    state.paymentSources = paymentSources
  },
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
