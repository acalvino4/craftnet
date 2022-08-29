import organizationsApi from '@/console/js/api/organizations';

/**
 * State
 */
const state = {
  organizations: [],
  currentOrgSlug: null,
  members: [],
  orders: [],
  pendingOrders: [],
  invitations: [],
}

/**
 * Getters
 */
const getters = {
  currentOrganization(state) {
    if (!state.currentOrgSlug) {
      return null
    }

    return state.organizations.find(o => o.slug === state.currentOrgSlug)
  },

  userIsOwner(state) {
    return (userId) => {
      if (!state.members) {
        return false
      }

      return !!state.members.find(member => {
        return (member.id === userId && member.role === 'owner')
      })
    }
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

  getOrders({commit}, {organizationId}) {
    return organizationsApi.getOrders(organizationId)
      .then((response) => {
        commit('updateOrders', response.data)
      })
  },

  getPendingOrders({commit}, {organizationId}) {
    return organizationsApi.getPendingOrders(organizationId)
      .then((response) => {
        commit('updatePendingOrders', response.data)
      })
  },

  requestOrderApproval(context, {organizationId, orderNumber}) {
    return organizationsApi.requestOrderApproval({organizationId, orderNumber})
  },

  rejectRequest(context, {organizationId, orderNumber}) {
    return organizationsApi.rejectRequest({organizationId, orderNumber})
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

  saveOrganization({dispatch}, organization) {
    return new Promise((resolve, reject) => {
      organizationsApi.saveOrganization(organization)
        .then((response) => {
          dispatch('getOrganizations')
            .then(() => {
              resolve(response)
            })
        })
        .catch((response) => {
          reject(response)
        })
    })
  },

  getInvitations({commit}, {organizationId}) {
    return organizationsApi.getInvitations({organizationId})
      .then((response) => {
        commit('updateInvitations', response.data.invitations)
      })
  },

  cancelInvitation({dispatch}, {organizationId, userId}) {
    return organizationsApi.cancelInvitation({organizationId, userId})
      .then(() => {
        dispatch('getInvitations', {organizationId})
      })
  },

  acceptInvitation({dispatch}, {organizationId}) {
    return new Promise((resolve, reject) => {
      organizationsApi.acceptInvitation({organizationId})
        .then(() => {
          dispatch('getOrganizations')
            .then(() => {
              resolve()
            })
            .catch((orgError) => {
              reject(orgError)
            })
        })
        .catch((error) => {
          reject(error)
        })
    })
  },

  declineInvitation(context, {organizationId}) {
    return organizationsApi.declineInvitation({organizationId})
  },

  setRole(context, {organizationId, userId, role}) {
    return organizationsApi.setRole({organizationId, userId, role})
  }
}

/**
 * Mutations
 */
const mutations = {
  updateCurrentOrgSlug(state, orgSlug) {
    state.currentOrgSlug = orgSlug
  },

  updateOrganizations(state, organizations) {
    state.organizations = organizations
  },

  updateMembers(state, members) {
    state.members = members
  },

  updateOrders(state, orders) {
    state.orders = orders
  },

  updatePendingOrders(state, pendingOrders) {
    state.pendingOrders = pendingOrders
  },

  updateInvitations(state, invitations) {
    state.invitations = invitations
  }
}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
