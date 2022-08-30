/* global VUE_APP_URL_CONSOLE, Craft */
import axios from 'axios';
import qs from 'qs';

export default {
  addMember({organizationId, email}) {
    const data = {
      email,
    }
    return axios.post(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/invitations', qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  leave() {
    console.log('TODO: Implement leaving an organization.')

    return new Promise((resolve) => {
      resolve()
    })
  },

  saveOrganization(organization) {
    const data = organization

    let endpointUrl = VUE_APP_URL_CONSOLE + '/orgs'

    if (organization.id) {
      endpointUrl += '/' + organization.id
    }

    return axios.post(endpointUrl, qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  convertAccountToOrganization() {
    console.log('TODO: Implement converting an account to an organization. The user can’t convert his account until he leaves all the organizations he’s a member of.')
  },

  getOrders(organizationId) {
    const query = qs.stringify({
      orgId: organizationId,
    })

    return axios.get(VUE_APP_URL_CONSOLE + '/orders?' + query, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  getPendingOrders(organizationId) {
    const query = qs.stringify({
      orgId: organizationId,
      approvalPending: 1,
    })

    return axios.get(VUE_APP_URL_CONSOLE + '/orders?' + query, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  requestOrderApproval({organizationId, orderNumber}) {
    return axios.post(VUE_APP_URL_CONSOLE + '/orders/' + orderNumber + '/request-approval', {
      orgId: organizationId
    })
  },

  rejectRequest({organizationId, orderNumber}) {
    return axios.post(VUE_APP_URL_CONSOLE + '/orders/' + orderNumber + '/reject-request', {
      orgId: organizationId
    })
  },

  getOrganizationMembers({organizationId}) {
    return axios.get(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/members', {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  getOrganizations() {
    return axios.get(VUE_APP_URL_CONSOLE + '/orgs')
  },

  removeMember({organizationId, memberId}) {
    return axios.delete(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/members/' + memberId, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  getInvitations({organizationId}) {
    return axios.get(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/invitations')
  },

  cancelInvitation({organizationId, userId}) {
    return axios.delete(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/invitations/' + userId)
  },

  acceptInvitation({organizationId}) {
    return axios.post(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/invitation')
  },

  declineInvitation({organizationId}) {
    return axios.delete(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/invitation')
  },

  setRole({organizationId, userId, role}) {
    return axios.post(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/members/' + userId + '/role', {
      role,
    })
  }
}
