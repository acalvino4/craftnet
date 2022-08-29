/* global VUE_APP_URL_CONSOLE, Craft */
import axios from './axios';
import qs from 'qs';

export default {
  addMember({organizationId, email}) {
    const data = {
      email,
    }
    return axios.post(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/invitations', qs.stringify(data))
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

    return axios.post(endpointUrl, qs.stringify(data))
  },

  convertAccountToOrganization() {
    console.log('TODO: Implement converting an account to an organization. The user can’t convert his account until he leaves all the organizations he’s a member of.')
  },

  getOrders(organizationId) {
    const query = qs.stringify({
      approvalRequested: 0,
    })

    return axios.get(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/orders?' + query)
  },

  getPendingOrders(organizationId) {
    return axios.get(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/orders?approvalRequested=1')
  },

  requestOrderApproval({organizationId, orderNumber}) {
    return axios.post(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/orders/' + orderNumber + '/request-approval')
  },

  rejectRequest({organizationId, orderNumber}) {
    return axios.post(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/orders/' + orderNumber + '/reject-request')
  },

  getOrganizationMembers({organizationId}) {
    return axios.get(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/members')
  },

  getOrganizations() {
    return axios.get(VUE_APP_URL_CONSOLE + '/orgs')
  },

  removeMember({organizationId, memberId}) {
    return axios.delete(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/members/' + memberId)
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
