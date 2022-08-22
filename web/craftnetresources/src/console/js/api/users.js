/* global Craft */

import axios from 'axios'
import FormDataHelper from '../helpers/form-data.js';

export default {
  getRemainingSessionTime(config) {
    return axios.get(Craft.VUE_APP_URL_CONSOLE + '/session', config)
  },

  login(formData) {
    return axios.post(VUE_APP_URL_CONSOLE + '/session', formData, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  logout() {
    return axios.delete(VUE_APP_URL_CONSOLE + '/session')
  },

  registerUser(formData) {
    return axios.post(VUE_APP_URL_CONSOLE + '/users', formData, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  saveUser(user) {
    const formData = new FormData();

    for (const attribute in user) {
      switch (attribute) {
        case 'id':
          FormDataHelper.append(formData, 'userId', user[attribute]);
          break;
        case 'email':
        case 'username':
        case 'firstName':
        case 'lastName':
        case 'password':
        case 'newPassword':
        case 'photo':
        case 'payPalEmail':
          FormDataHelper.append(formData, attribute, user[attribute]);
          break;
        default:
          FormDataHelper.append(formData, 'fields[' + attribute + ']', user[attribute]);
      }
    }

    return axios.post(VUE_APP_URL_CONSOLE + '/users/me', formData, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  sendPasswordResetEmail(formData) {
    return axios.post(VUE_APP_URL_CONSOLE + '/users/me/send-password-reset-email', formData, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },
}
