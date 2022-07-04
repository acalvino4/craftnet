/* global VUE_APP_URL_CONSOLE, Craft */
import axios from 'axios';
import qs from 'qs';

export default {
  addMember({organizationId, email}) {

    const data = {
      email,
    }
    return axios.post(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/members', qs.stringify(data), {
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

  save() {
    console.log('TODO: Implement saving a new organization or updating an exising one.')
  },

  convertAccountToOrganization() {
    console.log('TODO: Implement converting an account to an organization. The user can’t convert his account until he leaves all the organizations he’s a member of.')
  },

  saveCurrentOrganizationId(organizationId) {
    return new Promise((resolve) => {
      localStorage.setItem('currentOrganizationId', organizationId)

      resolve({
        organizationId,
      })
    })
  },

  getCurrentOrganizationId() {
    return new Promise((resolve) => {
      const organizationId = parseInt(localStorage.getItem('currentOrganizationId'))
      resolve({
        organizationId,
      })
    })
  },

  getOrders(organizationId) {
    return axios.get(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/orders', {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
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
    return axios.get(VUE_APP_URL_CONSOLE + '/orgs/all')
  },

  removeMember({organizationId, memberId}) {
    return axios.delete(VUE_APP_URL_CONSOLE + '/orgs/' + organizationId + '/members/' + memberId, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },
}
