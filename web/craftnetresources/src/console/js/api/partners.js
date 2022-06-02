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
        const formData = new FormData()
        formData.append('scenario', 'scenarioBaseInfo')
        formData.append('id', partnerId)

        for (const prop in data) {
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

    patchPartnerContact(data, files, partnerId) {
        const formData = new FormData()
        formData.append('scenario', 'scenarioContactInfo')
        formData.append('id', partnerId)

        console.log('data', data)

        for (const prop in data) {
            formData.append(prop, data[prop])
        }

        return axios.post(Craft.actionUrl + '/craftnet/partners/patch-partner', formData, {
            headers: {
                'X-CSRF-Token': Craft.csrfTokenValue,
            }
        })
    },

    patchPartnerLocations(locations, partnerId) {
        const formData = new FormData()
        formData.append('id', partnerId)
        formData.append('scenario', 'scenarioLocations')

        locations.forEach((location) => {
            for (const prop in location) {
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
        const formData = new FormData()
        formData.append('id', partnerId)
        formData.append('scenario', 'scenarioProjects')

        for (const i in projects) {
            const project = projects[i]

            for (const prop in project) {
                if (prop !== 'id' && prop !== 'screenshots') {
                    formData.append(
                        `projects[${project.id}][${prop}]`,
                        project[prop]
                    )
                }
            }

            for (const i in project.screenshots) {
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
