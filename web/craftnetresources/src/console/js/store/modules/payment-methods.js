import paymentMethodsApi from '../../api/payment-methods'

/**
 * State
 */
const state = {
  card: null,
  cardToken: null,
  paymentMethods: null,
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
  addCard({commit}, source) {
    return new Promise((resolve, reject) => {
      paymentMethodsApi.addCard(source)
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
      paymentMethodsApi.saveCard({paymentSourceId, card})
        .then((response) => {
          resolve(response)
        })
        .catch((error) => {
          reject(error.response)
        })
    })
  },

  removeCard({dispatch}, cardId) {
    console.log('cardId', cardId)
    return new Promise((resolve, reject) => {
      paymentMethodsApi.removeCard(cardId)
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

  getPaymentSources({commit}) {
    return new Promise((resolve, reject) => {
      paymentMethodsApi.getPaymentSources()
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
  updateStripeCard(state, {card}) {
    state.card = card
  },

  updatePaymentMethods(state, {paymentMethods}) {
    state.paymentMethods = paymentMethods
  },

  updatePaymentSources(state, {paymentSources}) {
    state.paymentSources = paymentSources
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
