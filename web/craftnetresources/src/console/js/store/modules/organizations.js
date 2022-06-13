import organizationsApi from '@/console/js/api/organizations';

/**
 * State
 */
const state = {
  organizations: [],
  currentOrganization: null,
}

/**
 * Getters
 */
const getters = {}

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
}

/**
 * Mutations
 */
const mutations = {
  updateCurrentOrganization(state, organization) {
    state.currentOrganization = JSON.parse(JSON.stringify(organization))
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
