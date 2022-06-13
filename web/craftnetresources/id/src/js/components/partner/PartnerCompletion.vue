<template>
  <div class="mb-4">
    <h3 v-if="!partner.enabled">Profile Completion:</h3>
    <ul class="list-reset pl-4">
      <li
        v-for="(status, key) in statuses"
        :key="key"
        :class="{'text-orange-dark': !status.valid, 'text-green-dark': status.valid}">
        <span v-if="status.valid">&#10004;</span>
        <span v-else>&#10008;</span>
        {{ status.message }}
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  props: ['partner'],

  computed: {
    statuses() {

      if (this.partner.enabled) {
        return {
          enabled: {
            valid: true,
            message: 'Your profile is live'
          }
        }
      }

      let statuses = {
        basicInfo: {
          valid: true,
          message: 'Basic information provided'
        },
        locations: {
          valid: true,
          message: 'Location provided'
        },
        projects: {
          valid: true,
          message: 'Five projects provided'
        }
      }

      for (let prop in this.partner) {
        let value = this.partner[prop]


        switch (prop) {
          case 'businessName':
          case 'primaryContactName':
          case 'primaryContactEmail':
          case 'primaryContactPhone':
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
              statuses.basicInfo = {
                valid: false,
                message: 'Basic information is incomplete'
              }
            }
            break

          case 'locations':
            if (!Array.isArray(value) || value.length === 0) {
              statuses.locations = {
                valid: false,
                message: 'Please provide a location'
              }
            }
            break

          case 'projects':
            if (!Array.isArray(value) || value.length < 5) {
              statuses.projects = {
                valid: false,
                message: 'Please add five projects'
              }
            } else {
              for (let i in value) {
                let screenshots = value[i]['screenshots'] || []
                if (screenshots.length === 0) {
                  statuses.projects = {
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

      if (this.partner.shortBio && this.partner.shortBio.length > 130 && statuses.basicInfo.valid) {
        statuses.basicInfo = {
          valid: false,
          message: 'Short Bio is more than 130 characters'
        }
      }

      return statuses
    }
  }
}
</script>
