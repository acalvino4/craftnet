<template>
  <div class="mb-8">
    <page-header>
      <div>
        <h1>
          Partner Network

          <template v-if="partner">
            <badge
              v-if="completionProfileIsLive"
              class="ml-2 align-middle"
              type="success">Live
            </badge>
            <badge
              v-else
              class="ml-2 align-middle"
              type="default">
              Pending
            </badge>
          </template>
        </h1>
      </div>

      <div>
        <btn>Public profile →</btn>
      </div>
    </page-header>

    <div
      v-if="loadState == LOADING"
      class="text-center">
      <spinner cssClass="lg mt-8"></spinner>
    </div>

    <p v-if="loadState == LOAD_ERROR">Error: {{ loadError }}</p>

    <div
      v-if="loadState == LOADED"
      class="space-y-6">
      <template v-if="!completionProfileIsLive">
        <partner-completion
          :partner="partner"
          :statuses="completionStatuses"
          :total="completionTotalStatuses"
          :total-valid="completionTotalValidStatuses"></partner-completion>
      </template>

      <partner-contact :partner="partner"></partner-contact>
      <partner-info :partner="partner"></partner-info>
      <partner-locations :partner="partner"></partner-locations>
      <partner-projects :partner="partner"></partner-projects>
    </div>
  </div>
</template>

<script>
import {mapState} from 'vuex'
import PartnerCompletion from '../../../components/partner/PartnerCompletion'
import PartnerContact from '../../../components/partner/PartnerContact'
import PartnerInfo from '../../../components/partner/PartnerInfo'
import PartnerLocations from '../../../components/partner/PartnerLocations'
import PartnerProjects from '../../../components/partner/PartnerProjects'
import PageHeader from '@/console/js/components/PageHeader'

export default {

  data() {
    return {
      LOADED: 'loaded',
      LOADING: 'loading',
      LOAD_ERROR: 'loadError',

      loadState: 'loading',
      loadError: ''
    }
  },

  components: {
    PartnerCompletion,
    PartnerContact,
    PartnerInfo,
    PartnerLocations,
    PartnerProjects,
    PageHeader,
  },

  computed: {
    ...mapState({
      partner: state => state.partner.partner,
    }),

    completionStatuses() {
      if (this.partner.enabled) {
        return {
          enabled: {
            valid: true,
            message: 'Your profile is live'
          }
        }
      }

      let completionStatuses = {
        contactInfo: {
          valid: true,
          message: 'Contact information'
        },
        basicInfo: {
          valid: true,
          message: 'Business information'
        },
        locations: {
          valid: true,
          message: 'Provide a location'
        },
        projects: {
          valid: true,
          message: 'Add five projects'
        }
      }

      for (let prop in this.partner) {
        let value = this.partner[prop]


        switch (prop) {
          case 'primaryContactName':
          case 'primaryContactEmail':
          case 'primaryContactPhone':
            if (
              value === null ||
              typeof value === 'undefined' ||
              (typeof value === 'string' && value.trim() === '') ||
              (Array.isArray(value) && value.length === 0)
            ) {
              completionStatuses.contactInfo = {
                valid: false,
                message: 'Contact information'
              }
            }
            break
          case 'businessName':
          case 'fullBio':
          case 'shortBio':
          case 'agencySize':
          case 'region':
          case 'websiteSlug':
          case 'website':
          case 'hasFullTimeDev':
          case 'isRegisteredBusiness':
          case 'capabilities':
            if (
              value === null ||
              typeof value === 'undefined' ||
              (typeof value === 'string' && value.trim() === '') ||
              (Array.isArray(value) && value.length === 0)
            ) {
              completionStatuses.basicInfo = {
                valid: false,
                message: 'Business information'
              }
            }
            break

          case 'locations':
            if (!Array.isArray(value) || value.length === 0) {
              completionStatuses.locations = {
                valid: false,
                message: 'Provide a location'
              }
            }
            break

          case 'projects':
            if (!Array.isArray(value) || value.length < 5) {
              completionStatuses.projects = {
                valid: false,
                message: 'Add five projects'
              }
            } else {
              for (let i in value) {
                let screenshots = value[i]['screenshots'] || []
                if (screenshots.length === 0) {
                  completionStatuses.projects = {
                    valid: false,
                    message: 'At least one project is missing screenshots'
                  }
                }
              }
            }
            break

          default:
            break
        }
      }

      if (this.partner.shortBio && this.partner.shortBio.length > 130 && completionStatuses.basicInfo.valid) {
        completionStatuses.basicInfo = {
          valid: false,
          message: 'Short Bio is more than 130 characters'
        }
      }

      return completionStatuses
    },

    completionTotalStatuses() {
      return Object.keys(this.completionStatuses).length
    },


    completionTotalValidStatuses() {
      const filteredStatuses = Object.keys(this.completionStatuses).filter(status => {
        if (!this.completionStatuses[status].valid) {
          return false
        }

        return true
      })
      return Object.keys(filteredStatuses).length
    },

    completionProfileIsLive() {
      return this.partner.enabled || (this.completionTotalValidStatuses / this.completionTotalStatuses === 1)
    }
  },

  mounted() {
    this.$store.dispatch('initPartner')
      .then(() => {
        this.loadState = this.LOADED
      })
      .catch((response) => {
        this.loadState = this.LOAD_ERROR
        this.loadError = response.data.error || 'Couldn’t load partner network'
      })
  }
}
</script>
