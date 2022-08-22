import stripeApi from '../../api/stripe'

/**
 * State
 */
const state = {
  card: null,
  cards: null,
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

  removeCard({dispatch}, cardId) {
    console.log('cardId', cardId)
    return new Promise((resolve, reject) => {
      stripeApi.removeCard(cardId)
        .then((removeCardResponse) => {
          dispatch('getCards')
            .then((getCardsResponse) => {
              resolve({
                removeCardResponse,
                getCardsResponse
              })
            })
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

  getCards({commit}) {
    return new Promise((resolve, reject) => {
      stripeApi.getCards()
        .then((response) => {
          commit('updateCards', {cards: response.data.cards})
          resolve(response)
        })
        .catch((error) => {
          reject(error)
        })
    })
  }
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

  updateCards(state, {cards}) {
    state.cards = cards
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
