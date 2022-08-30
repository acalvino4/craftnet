import ordersApi from '@/console/js/api/orders';

/**
 * State
 */
const state = {
  orders: [],
  pendingOrders: [],
}

/**
 * Getters
 */
const getters = {}

/**
 * Actions
 */
const actions = {
  getOrders({commit}, {organizationId}) {
    return ordersApi.getOrders(organizationId)
      .then((response) => {
        commit('updateOrders', response.data)
      })
  },

  getPendingOrders({commit}, {organizationId}) {
    return ordersApi.getPendingOrders(organizationId)
      .then((response) => {
        commit('updatePendingOrders', response.data)
      })
  },

  requestOrderApproval(context, {organizationId, orderNumber}) {
    return ordersApi.requestOrderApproval({organizationId, orderNumber})
  },

  rejectRequest(context, {organizationId, orderNumber}) {
    return ordersApi.rejectRequest({organizationId, orderNumber})
  },
}

/**
 * Mutations
 */
const mutations = {
  updateOrders(state, orders) {
    state.orders = orders
  },

  updatePendingOrders(state, pendingOrders) {
    state.pendingOrders = pendingOrders
  },
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
