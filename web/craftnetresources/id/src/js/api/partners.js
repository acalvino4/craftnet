/* global Craft */

import axios from 'axios'

export default {
  getPartner() {
    return axios.post(Craft.actionUrl + '/craftnet/partners/fetch-partner', null, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  patchPartner(data, files, partnerId) {
    let formData = new FormData()
    formData.append('scenario', 'scenarioBaseInfo')
    formData.append('id', partnerId)

    for (let prop in data) {
      switch (prop) {
        case 'capabilities':
          for (let i = 0; i < data[prop].length; i++) {
            formData.append(prop + '[]', data[prop][i])
          }
          break

        default:
          formData.append(prop, data[prop])
          break
      }
    }

    formData.append('logoAssetId[]', data.logo.id)

    if (files.length) {
      formData.append('logo', files[0])
    }

    return axios.post(Craft.actionUrl + '/craftnet/partners/patch-partner', formData, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  patchPartnerLocations(locations, partnerId) {
    let formData = new FormData()
    formData.append('id', partnerId)
    formData.append('scenario', 'scenarioLocations')

    locations.forEach(location => {
      for (let prop in location) {
        if (prop !== 'id') {
          formData.append(
            `locations[${location.id}][${prop}]`,
            location[prop]
          )
        }
      }
    })

    return axios.post(Craft.actionUrl + '/craftnet/partners/patch-partner', formData, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  patchPartnerProjects(projects, partnerId) {
    let formData = new FormData()
    formData.append('id', partnerId)
    formData.append('scenario', 'scenarioProjects')

    for (let i in projects) {
      let project = projects[i]

      for (let prop in project) {
        if (prop !== 'id' && prop !== 'screenshots') {
          formData.append(
            `projects[${project.id}][${prop}]`,
            project[prop]
          )
        }
      }

      for (let i in project.screenshots) {
        formData.append(
          `projects[${project.id}][screenshots][]`,
          project.screenshots[i]['id']
        )
      }
    }

    return axios.post(Craft.actionUrl + '/craftnet/partners/patch-partner', formData, {
      headers: {
        'X-CSRF-Token': Craft.csrfTokenValue,
      }
    })
  },

  uploadScreenshots(formData, config) {
    return axios.post(Craft.actionUrl + '/craftnet/partners/upload-screenshots', formData, config)
  }
}
