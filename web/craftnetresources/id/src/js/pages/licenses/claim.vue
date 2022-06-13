<template>
  <div>
    <h1>Claim license</h1>

    <div class="card mb-6">
      <div class="card-body">
        <h3>Claim a Craft CMS license</h3>
        <p class="text-secondary">Attach a Craft CMS license to your Craft ID
          account.</p>

        <form
          class="mb-6"
          @submit.prevent="claimCmsLicense()">
          <textbox
            type="textarea"
            id="cmsLicenseKey"
            class="mono"
            :spellcheck="false"
            v-model="cmsLicenseKey"
            @input="cmsLicenseKeyChange"
            label="Craft CMS License Key"
            rows="5" />
          <btn
            kind="primary"
            type="submit"
            :disabled="!cmsLicenseValidates">Claim License
          </btn>
          <spinner v-if="cmsLicenseLoading"></spinner>
        </form>

        <form @submit.prevent="claimCmsLicenseFile()">
          <div class="form-group mb-4">
            <label
              for="licenseFile"
              class="block">Or upload your license.key file</label>
            <input
              class="form-control"
              type="file"
              id="licenseFile"
              name="licenseFile"
              ref="licenseFile"
              @change="cmsLicenseFileChange" />
          </div>
          <btn
            kind="primary"
            type="submit"
            :disabled="!cmsLicenseFileValidates">Claim License
          </btn>
          <spinner v-if="cmsLicenseFileLoading"></spinner>
        </form>
      </div>
    </div>

    <div class="card mb-6">
      <div class="card-body">
        <h3>Claim a plugin license</h3>
        <p class="text-secondary">Attach a plugin license to your Craft ID
          account.</p>

        <form @submit.prevent="claimPluginLicense()">
          <textbox
            id="pluginLicenseKey"
            class="mono"
            :spellcheck="false"
            v-model="pluginLicenseKey"
            label="Plugin License Key"
            placeholder="XXXX-XXXX-XXXX-XXXX-XXXX-XXXX"
            mask="XXXX-XXXX-XXXX-XXXX-XXXX-XXXX" />
          <btn
            kind="primary"
            type="submit"
            :disabled="!pluginLicenseValidates">Claim License
          </btn>
          <spinner v-if="pluginLicenseLoading"></spinner>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h3>Claim licenses by your email address</h3>
        <p class="text-secondary">Use an email address to attach Craft CMS and
          plugin licenses to your Craft ID account.</p>

        <form @submit.prevent="claimLicensesByEmail()">
          <textbox
            id="email"
            label="Email Address"
            v-model="email"
            placeholder="user@example.com" />
          <btn
            kind="primary"
            type="submit"
            :disabled="$v.email.$invalid">Claim License
          </btn>
          <spinner v-if="emailLoading"></spinner>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import cmsLicensesApi from '../../api/cms-licenses'
import pluginLicensesApi from '../../api/plugin-licenses'
import claimLicensesApi from '../../api/claim-licenses'
import {required, email} from 'vuelidate/lib/validators'

export default {
  data() {
    return {
      cmsLicenseKey: '',
      cmsLicenseLoading: false,
      cmsLicenseValidates: false,
      cmsLicenseFile: '',
      cmsLicenseFileLoading: false,
      cmsLicenseFileValidates: false,
      pluginLicenseKey: '',
      pluginLicenseLoading: false,
      pluginLicenseValidates: false,
      email: '',
      emailLoading: false,
    }
  },

  validations: {
    email: {
      required,
      email,
    },
  },

  methods: {
    checkCmsLicense() {
      if (this.cmsLicenseKey.length === 258) {
        return true
      }

      return false
    },

    checkPluginLicense() {
      const normalizedValue = this.pluginLicenseKey.replace(/(- )/gm, "").trim()

      if (normalizedValue.length === 29) {
        return true
      }

      return false
    },

    claimCmsLicense() {
      this.cmsLicenseLoading = true

      cmsLicensesApi.claimCmsLicense(this.cmsLicenseKey)
        .then((response) => {
          this.cmsLicenseLoading = false

          if (response.data && !response.data.error) {
            this.$store.dispatch('app/displayNotice', 'CMS license claimed.')
            this.$router.push({path: '/licenses/cms'})
          } else {
            this.$store.dispatch('app/displayError', response.data.error)
          }
        })
        .catch((error) => {
          this.cmsLicenseLoading = false
          const errorMessage = error.response.data && error.response.data.error ? error.response.data.error : 'Couldn’t claim CMS license.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    },

    claimCmsLicenseFile() {
      cmsLicensesApi.claimCmsLicenseFile(this.$refs.licenseFile.files[0])
        .then((response) => {
          this.cmsLicenseFileLoading = false

          if (response.data && !response.data.error) {
            this.$store.dispatch('app/displayNotice', 'CMS license claimed.')
            this.$router.push({path: '/licenses/cms'})
          } else {
            this.$store.dispatch('app/displayError', response.data.error)
          }
        })
        .catch((error) => {
          this.cmsLicenseFileLoading = false
          const errorMessage = error.response.data && error.response.data.error ? error.response.data.error : 'Couldn’t claim CMS license.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    },

    claimLicensesByEmail() {
      this.emailLoading = true

      claimLicensesApi.claimLicensesByEmail(this.email)
        .then((response) => {
          this.emailLoading = false

          if (response.data && !response.data.error) {
            this.$store.dispatch('app/displayNotice', 'Verification email sent to ' + this.email + '.')
          } else {
            this.$store.dispatch('app/displayError', response.data.error)
          }
        })
        .catch((error) => {
          this.emailLoading = false
          const errorMessage = error.response.data && error.response.data.error ? error.response.data.error : 'Couldn’t claim licenses.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    },

    claimPluginLicense() {
      this.pluginLicenseLoading = true

      pluginLicensesApi.claimPluginLicense(this.pluginLicenseKey)
        .then((response) => {
          this.pluginLicenseLoading = false

          if (response.data && !response.data.error) {
            this.$store.dispatch('app/displayNotice', 'Plugin license claimed.')
            this.$router.push({path: '/licenses/plugins'})
          } else {
            this.$store.dispatch('app/displayError', response.data.error)
          }
        })
        .catch((error) => {
          this.pluginLicenseLoading = false
          const errorMessage = error.response.data && error.response.data.error ? error.response.data.error : 'Couldn’t claim plugin license.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    },

    cmsLicenseKeyChange(value) {
      this.$nextTick(() => {
        this.cmsLicenseKey = this.$options.filters.formatCmsLicense(value)
      })
    },

    cmsLicenseFileChange() {
      this.cmsLicenseFileValidates = this.$refs.licenseFile.files.length > 0
    }
  },

  watch: {
    cmsLicenseKey() {
      this.cmsLicenseValidates = this.checkCmsLicense()
    },

    pluginLicenseKey() {
      this.pluginLicenseValidates = this.checkPluginLicense()
    }
  },
}
</script>
