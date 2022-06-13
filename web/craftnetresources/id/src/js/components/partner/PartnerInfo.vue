<template>
  <div>

    <p class="text-grey-darker">Basic business information and adminstrative
      contact information for Pixel &amp; Tonic to reach you.</p>

    <div class="card mb-4">
      <div class="card-body">
        <div
          class="text-right"
          v-if="!isEditing">
          <btn
            icon="pencil"
            @click="onEditClick">Edit
          </btn>
        </div>

        <div v-if="!isEditing">
          <ul class="info-list list-reset">
            <li
              v-if="partner.logo.url"
              class="mb-4">
              <div class="partner-logo">
                <img :src="partner.logo.url">
              </div>
            </li>
            <li v-if="partner.businessName">
              <strong class="text-2xl">{{ partner.businessName }}</strong>
            </li>
            <li v-if="partner.website">
              <a
                :href="partner.website"
                target="_blank">{{ partner.website }}</a>
            </li>
            <li v-if="partner.primaryContactName">
              {{ partner.primaryContactName }}
            </li>
            <li v-if="partner.primaryContactEmail">
              {{ partner.primaryContactEmail }}
            </li>
            <li v-if="partner.primaryContactPhone">
              {{ partner.primaryContactPhone }}
            </li>
            <li v-if="partner.region">
              {{ partner.region }}
            </li>
            <li
              v-if="basicRequirementsList.length"
              class="mt-2">
              Basic Requirements:
              <ul class="text-sm text-grey-darker mt-2">
                <li
                  v-for="(value, index) in basicRequirementsList"
                  :key="index">{{ value }}
                </li>
              </ul>
            </li>
            <li
              v-if="partner.capabilities.length"
              class="mt-2 mb-2">
              Capabilities:
              <ul class="text-sm text-grey-darker mt-2">
                <li
                  v-for="(value, index) in partner.capabilities"
                  :key="index">
                  {{ value }}
                </li>
              </ul>
            </li>
            <li
              v-if="partner.expertise.trim().length"
              class="mt-2 mb-2 pt-2">
              Areas of Expertise:
              <ul class="text-sm text-grey-darker mt-2">
                <li
                  v-for="(value, index) in expertiseList"
                  :key="index">
                  {{ value }}
                </li>
              </ul>
            </li>
            <li
              v-if="partner.agencySize"
              class="mt-2">
              Agency Size: <span class="text-grey-darker">{{
                agencySizeDisplay
              }}</span>
            </li>
            <li
              v-if="partner.fullBio"
              class="mt-4">
              Full Bio:
              <pre class="text-grey-darker text-sm p-2 whitespace-pre-wrap">{{
                  partner.fullBio
                }}</pre>
            </li>
            <li
              v-if="partner.shortBio"
              class="mt-4">
              Short Bio:
              <pre class="text-grey-darker text-sm p-2 whitespace-pre-wrap">{{
                  partner.shortBio
                }}</pre>
            </li>
          </ul>
        </div>

        <div v-else>
          <textbox
            id="businessName"
            label="Business Name"
            v-model="draft.businessName"
            :errors="errors.businessName" />
          <textbox
            id="websiteSlug"
            label="Website Slug"
            instructions="Automatically generated from Business Name if blank. Not editable once your page is live. (e.g. https://craftcms.com/partners/business-name)"
            v-model="draft.websiteSlug"
            :errors="errors.websiteSlug"
            :disabled="partner.enabled" />
          <textbox
            id="website"
            label="Business Website URL"
            v-model="draft.website"
            :errors="errors.website" />

          <div class="mb-4">
            <label>Logo</label>

            <div class="instructions">
              <p>Must be an SVG that fits within a circle. Be sure not to use
                unrasterized fonts or
                base64-encoded JPGs or PNGs inside the SVG or it will cause
                problems with image resizing for
                Twitter share cards and such.</p>
            </div>

            <div>
              <div class="partner-logo">
                <img
                  v-if="draft.logo.url"
                  :src="draft.logo.url"
                  style="width: 250px;"
                  class="block mt-2 mb-2">
              </div>
              <btn
                v-if="draft.logo.url"
                kind="danger"
                icon="times"
                :small="true"
                @click="draft.logo = {id: null, url: null}"
                class="mt-6 mb-4">Remove
              </btn>
              <input
                v-if="!draft.logo.url"
                accept=".svg"
                type="file"
                @change="onLogoChange"
                ref="logoFile"
                class="mt-6 mb-6">
            </div>
            <div
              v-if="errors.logo"
              class="invalid-feedback"
              v-for="(error, index) in errors.logo"
              :key="index">{{ error }}
            </div>
          </div>

          <textbox
            id="primaryContactName"
            label="Primary Contact Name"
            instructions="For Pixel &amp; Tonic use only. This will not be visible on your profile."
            v-model="draft.primaryContactName"
            :errors="errors.primaryContactName" />
          <textbox
            id="primaryContactEmail"
            label="Primary Contact Email"
            instructions="For Pixel &amp; Tonic use only. This will not be visible on your profile."
            v-model="draft.primaryContactEmail"
            :errors="errors.primaryContactEmail" />
          <textbox
            id="primaryContactPhone"
            label="Primary Contact Phone"
            instructions="For Pixel &amp; Tonic use only. This will not be visible on your profile."
            v-model="draft.primaryContactPhone"
            :errors="errors.primaryContactPhone" />
          <dropdown
            id="region"
            label="Region"
            v-model="draft.region"
            :options="options.region"
            :errors="errors.region" />
          <checkbox
            id="isRegisteredBusiness"
            label="This is a registered business"
            instructions="Required for consideration."
            v-model="draft.isRegisteredBusiness"
            :checked-value="1"
            :errors="errors.isRegisteredBusiness" />
          <checkbox
            id="hasFullTimeDev"
            label="Business has at least one full-time Craft developer"
            instructions="Required for consideration."
            v-model="draft.hasFullTimeDev"
            :checked-value="1"
            :errors="errors.hasFullTimeDev" />
          <checkbox-set
            id="capabilities"
            label="Capabilities"
            v-model="draft.capabilities"
            :options="options.capabilities"
            :errors="errors.capabilities" />
          <textbox
            type="textarea"
            id="expertise"
            label="Areas of Expertise"
            instructions="Tags for relevant expertise (e.g. SEO), each on a new line"
            v-model="draft.expertise" />
          <dropdown
            id="agencySize"
            label="Agency Size"
            v-model="draft.agencySize"
            :options="options.agencySize"
            :errors="errors.agencySize" />
          <textbox
            type="textarea"
            id="fullBio"
            label="Full Bio"
            instructions="Markdown OK. Shown on your detail page."
            v-model="draft.fullBio"
            :errors="errors.fullBio" />
          <textbox
            type="textarea"
            id="shortBio"
            label="Short Bio"
            instructions="Max 130 characters. Shown on your listing card."
            v-model="draft.shortBio"
            :max="130"
            :errors="errors.shortBio" />

          <div class="pt-4">
            <btn
              :disabled="requestPending"
              @click="isEditing = false">Cancel
            </btn>

            <btn
              kind="primary"
              :disabled="requestPending"
              @click="onSubmit">Save
            </btn>

            <spinner :class="{'invisible': !requestPending}"></spinner>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.partner-logo {
  height: 120px;
  width: 120px;
  border-radius: 100%;
  border: 1px solid #e5edfd;
  overflow: hidden;
  display: flex;
  align-items: center;
  align-content: center;
}

.partner-logo img {
  display: block;
  max-width: 100%;
  height: auto;
}
</style>

<script>
import helpers from '../../mixins/helpers'
import CheckboxSet from '../CheckboxSet'

export default {
  props: ['partner'],

  mixins: [helpers],

  components: {
    CheckboxSet,
  },

  data() {
    return {
      draft: {},
      draftProps: [
        'id',
        'logo',
        'businessName',
        'websiteSlug',
        'primaryContactName',
        'primaryContactEmail',
        'primaryContactPhone',
        'region',
        'isRegisteredBusiness',
        'hasFullTimeDev',
        'capabilities',
        'expertise',
        'agencySize',
        'fullBio',
        'shortBio',
        'website',
      ],
      errors: {},
      logoFiles: [],
      isEditing: false,
      isUploading: false,
      options: {
        agencySize: [
          {label: "1-2", value: "XS"},
          {label: "3-9", value: "S"},
          {label: "10-29", value: "M"},
          {label: "30+", value: 'L'}
        ],
        capabilities: [
          {label: 'Commerce', value: 'Commerce'},
          {label: 'Full Service', value: 'Full Service'},
          {label: 'Custom Development', value: 'Custom Development'},
          {label: 'Contract Work', value: 'Contract Work'},
          {label: 'Ongoing Maintenance', value: 'Ongoing Maintenance'}
        ],
        region: [
          {label: 'Asia Pacific', value: 'Asia Pacific'},
          {label: 'Europe', value: 'Europe'},
          {label: 'North America', value: 'North America'},
          {label: 'South America', value: 'South America'}
        ]
      },
      requestPending: false
    }
  },

  computed: {

    agencySizeDisplay() {
      for (let i = 0; i < this.options.agencySize.length; i++) {
        const item = this.options.agencySize[i]
        if (item.value === this.partner.agencySize) {
          return item.label
        }
      }

      return this.partner.agencySize
    },
    basicRequirementsList() {
      let list = []

      if (this.partner.isRegisteredBusiness) {
        list.push('Is a registered business')
      }

      if (this.partner.hasFullTimeDev) {
        list.push('Has a full-time Craft developer')
      }

      return list
    },
    expertiseList() {
      if (typeof this.partner.expertise !== 'string') {
        return ''
      }

      return this.partner.expertise.trim().split("\n")
    }
  },

  methods: {
    // default to North America
    filterRegionValue(region) {
      if (region) {
        for (let i in this.options.region) {
          if (region === this.options.region[i].value) {
            return region
          }
        }
      }

      return 'North America'
    },
    onEditClick() {
      let clone = this.simpleClone(this.partner, this.draftProps)
      clone.region = this.filterRegionValue(clone.region)
      clone.agencySize = clone.agencySize || 'XS'

      this.draft = clone
      this.isEditing = true
    },

    onLogoChange(event) {
      let reader = new FileReader();

      reader.onload = e => {
        this.draft.logo.url = e.target.result
        this.draft.logo.id = 'new'
      }

      reader.readAsDataURL(event.target.files[0])

      this.logoFiles = event.target.files
      // eslint-disable-next-line
      console.warn('change', this.logoFiles)
    },

    onSubmit() {
      // eslint-disable-next-line
      console.warn('onSubmit')
      this.errors = {}
      this.errorMessage = ''
      this.requestPending = true
      // eslint-disable-next-line
      console.warn('onSubmit', this.logoFiles)

      let data = {
        draft: this.draft,
        files: this.logoFiles
      }

      this.$store.dispatch('patchPartner', data)
        .then(response => {
          // eslint-disable-next-line
          console.warn('.then()')
          // eslint-disable-next-line
          console.warn(response)
          this.requestPending = false

          if (response.data.success) {
            this.$store.dispatch('app/displayNotice', 'Updated')
            this.isEditing = false
          } else {
            this.errors = response.data.errors
            this.$store.dispatch('app/displayError', 'Validation errors')
          }
        })
        .catch(errorMessage => {
          // eslint-disable-next-line
          console.warn('.catch()')
          // eslint-disable-next-line
          console.warn(errorMessage)
          this.$store.dispatch('app/displayError', errorMessage)
          this.requestPending = false
        })
    }
  },
}
</script>
