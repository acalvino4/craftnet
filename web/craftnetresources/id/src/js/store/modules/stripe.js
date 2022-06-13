import Vue from 'vue'
import Vuex from 'vuex'
import stripeApi from '../../api/stripe'

Vue.use(Vuex)

/**
 * State
 */
const state = {
  card: null,
  cardToken: null,
  stripeAccount: null,
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

  removeCard({commit}) {
    return new Promise((resolve, reject) => {
      stripeApi.removeCard()
        .then((response) => {
          if (!response.data.error) {
            commit('removeStripeCard')
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

  saveCard({commit}, source) {
    return new Promise((resolve, reject) => {
      stripeApi.saveCard(source)
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

  updateCardToken(state, {cardToken}) {
    state.cardToken = cardToken
  },

  updateStripeAccount(state, {response}) {
    state.stripeAccount = response.data
  },

  updateStripeCard(state, {card}) {
    state.card = card
  },
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
