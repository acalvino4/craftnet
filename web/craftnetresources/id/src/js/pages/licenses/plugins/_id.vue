<template>
  <div>
    <template v-if="!loading">
      <p>
        <router-link
          class="nav-link"
          to="/licenses/plugins"
          exact>← Plugins
        </router-link>
      </p>

      <template v-if="error">
        <p class="text-red">Couldn’t load license.</p>
      </template>

      <template v-if="license">
        <h1><code>{{ license.key.substr(0, 4) }}</code></h1>

        <plugin-license-details :license.sync="license"></plugin-license-details>

        <license-history :history="license.history" />

        <div class="card card-danger mb-3">
          <div class="card-header">Danger Zone</div>
          <div class="card-body">
            <h5>Release license</h5>
            <p>Release this license if you no longer wish to use it, so that it
              can be claimed by someone else.</p>
            <div>
              <btn
                kind="danger"
                @click="releasePluginLicense()">Release License
              </btn>
            </div>
          </div>
        </div>
      </template>
    </template>
    <template v-else>
      <spinner></spinner>
    </template>
  </div>
</template>

<script>
import pluginLicensesApi from '../../../api/plugin-licenses'
import CmsLicensesTable from '../../../components/licenses/CmsLicensesTable'
import PluginLicenseDetails from '../../../components/licenses/PluginLicenseDetails'
import LicenseHistory from '../../../components/licenses/LicenseHistory'

export default {
  components: {
    CmsLicensesTable,
    PluginLicenseDetails,
    LicenseHistory,
  },

  data() {
    return {
      loading: false,
      license: null,
      error: false,
    }
  },

  methods: {
    releasePluginLicense() {
      if (!window.confirm("Are you sure you want to release this license?")) {
        return false
      }

      pluginLicensesApi.releasePluginLicense({
          pluginHandle: this.license.plugin.handle,
          licenseKey: this.license.key,
        })
        .then((response) => {
          if (response.data && !response.data.error) {
            this.$store.dispatch('app/displayNotice', 'Plugin license released.')
            this.$router.push({path: '/licenses/plugins'})
          } else {
            this.$store.dispatch('app/displayError', response.data.error)
          }
        })
        .catch((error) => {
          const errorMessage = error.response.data && error.response.data.error ? error.response.data.error : 'Couldn’t release plugin license.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    },
  },

  mounted() {
    const licenseId = this.$route.params.id

    this.loading = true
    this.error = false

    pluginLicensesApi.getPluginLicense(licenseId)
      .then((response) => {
        this.loading = false

        if (response.data && response.data.error) {
          this.error = true
        } else {
          this.license = response.data
          this.$store.commit('app/updateRenewLicense', this.license)
        }
      })
      .catch(() => {
        this.loading = false
        this.error = true
      })
  }
}
</script>
