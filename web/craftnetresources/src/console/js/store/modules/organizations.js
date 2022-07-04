import organizationsApi from '@/console/js/api/organizations';

/**
 * State
 */
const state = {
  organizations: [],
  currentOrganizationId: null,
  members: [],
  orders: [],
}

/**
 * Getters
 */
const getters = {
  currentOrganization(state) {
    if (!state.currentOrganizationId) {
      return null
    }

    return state.organizations.find(o => o.id === state.currentOrganizationId)
  }
}

/**
 * Actions
 */
const actions = {
  addMember({dispatch}, {organizationId, email, role}) {
    return organizationsApi.addMember({organizationId, email, role})
      .then(() => {
        dispatch('getOrganizationMembers', {organizationId})
      })
  },

  leaveOrganization() {
    return new Promise((resolve, reject) => {
      organizationsApi.leave()
        .then((response) => {
          resolve(response)
        })
        .catch((response) => {
          reject(response)
        })
    })
  },

  saveCurrentOrganization({commit}, organization) {
    return new Promise((resolve, reject) => {
      const organizationId = organization ? organization.id : null

      organizationsApi.saveCurrentOrganizationId(organizationId)
        .then((response) => {
          commit('updateCurrentOrganizationId', organizationId)
          resolve(response)
        })
        .catch((response) => {
          reject(response)
        })
    })
  },

  getCurrentOrganizationId({commit}) {
    return organizationsApi.getCurrentOrganizationId()
      .then((response) => {
        commit('updateCurrentOrganizationId', response.organizationId)
      })
  },

  getOrders({commit}, {organizationId}) {
    return organizationsApi.getOrders(organizationId)
      .then((response) => {
        commit('updateOrders', response.data)
      })
  },

  getOrganizationMembers({commit}, {organizationId}) {
    return organizationsApi.getOrganizationMembers({organizationId})
            .then((response) => {
              commit('updateMembers', response.data)
            })
  },

  getOrganizations({commit}) {
    return new Promise((resolve, reject) => {
      organizationsApi.getOrganizations()
        .then((response) => {
          commit('updateOrganizations', response.data)
          resolve(response)
        })
        .catch((response) => {
          reject(response)
        })
    })
  },

  removeMember({dispatch}, {organizationId, memberId}) {
    return organizationsApi.removeMember({organizationId, memberId})
      .then(() => {
        dispatch('getOrganizationMembers', {organizationId})
      })
  },

  saveOrganization(context, organization) {
    return organizationsApi.saveOrganization(organization)
  },
}

/**
 * Mutations
 */
const mutations = {
  updateCurrentOrganizationId(state, organizationId) {
    state.currentOrganizationId = organizationId
  },

  updateOrganizations(state, organizations) {
    state.organizations = organizations
  },

  updateMembers(state, members) {
    state.members = members
  },

  updateOrders(state, orders) {
    console.log('-------------- orders', orders)
    state.orders = orders
  }
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
