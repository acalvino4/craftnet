import organizationsApi from '@/console/js/api/organizations';

/**
 * State
 */
const state = {
  organizations: [],
  currentOrganizationId: null,
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
  }
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
  }
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
