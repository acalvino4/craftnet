/* global Craft */

import axios from 'axios'
import FormDataHelper from '../helpers/form-data';
import qs from 'qs'

export default {
  claimCmsLicense(licenseKey) {
    const data = {
      key: licenseKey
    }

    return axios.post(Craft.actionUrl + '/craftnet/id/cms-licenses/claim', qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  claimCmsLicenseFile(licenseFile) {
    let formData = new FormData();

    FormDataHelper.append(formData, 'licenseFile', licenseFile);

    return axios.post(Craft.actionUrl + '/craftnet/id/cms-licenses/claim', formData, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  getCmsLicense(id) {
    return axios.get(Craft.actionUrl + '/craftnet/id/cms-licenses/get-license-by-id', {params: {id}})
  },

  getExpiringCmsLicensesTotal() {
    return axios.get(Craft.actionUrl + '/craftnet/id/cms-licenses/get-expiring-licenses-total')
  },

  releaseCmsLicense(licenseKey) {
    const data = {
      key: licenseKey
    }

    return axios.post(Craft.actionUrl + '/craftnet/id/cms-licenses/release', qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  saveCmsLicense(license) {
    const data = license

    return axios.post(Craft.actionUrl + '/craftnet/id/cms-licenses/save', qs.stringify(data), {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },
}