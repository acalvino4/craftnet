<template>
  <div>
    <template v-if="loading">
      <spinner></spinner>
    </template>
    <template v-else>
      <template v-if="license">
        <page-header>
          <div>
            <p>
              <router-link
                class="nav-link"
                to="/licenses/cms"
                exact>← Craft CMS
              </router-link>
            </p>
            <h1>
              <code>
                <template v-if="license.key">
                  {{ license.key.substr(0, 10) }}
                </template>
                <template v-else-if="license.shortKey">
                  {{ license.shortKey }}
                </template>
              </code>
            </h1>
          </div>
        </page-header>

        <div class="space-y-6">
          <cms-license-details
            v-model:license="license"></cms-license-details>

          <pane class="plugin-licenses">
            <h2>Plugin Licenses</h2>

            <template v-if="license.pluginLicenses.length > 0">
              <p class="text-light mb-4">Plugin licenses attached
                to this Craft CMS license.</p>
              <plugin-licenses-table
                :licenses="license.pluginLicenses"
                :exclude-cms-license-column="true"
                :exclude-notes-column="true"
                :auto-renew-switch="true"></plugin-licenses-table>

              <template
                v-if="license.pluginRenewalOptions && !(license.expirable && license.expiresOn)">
                <div class="mt-6">
                  <btn @click="showRenewLicensesModal('cms')">
                    Renew your plugin licenses
                  </btn>
                </div>
              </template>
            </template>
            <template v-else>
              <p class="text-light mb-4">No plugin licenses are
                attached to this Craft CMS license.</p>
            </template>
          </pane>

          <license-history :history="license.history" />

          <pane class="danger-zone border border-red-500 mb-3">
            <template v-slot:header>
              <h2 class="text-red-600">Danger Zone</h2>
            </template>

            <div class="lg:flex lg:justify-between lg:items-center">
              <div class="lg:mr-6">
                <h3 class="font-bold">Release license</h3>
                <p>Release this license if you no longer wish to use it,
                  so that it can be claimed by someone else.</p>
              </div>
              <div class="mt-6 lg:mt-0">
                <btn
                  kind="danger"
                  @click="releaseCmsLicense()">
                  Release License
                </btn>
              </div>
            </div>

            <hr>

            <div class="lg:flex lg:justify-between lg:items-center">
              <div class="lg:mr-6">
                <h3 class="font-bold">Transfer license</h3>
                <p>Transfer this CMS license to another account.</p>
              </div>
              <div class="mt-6 lg:mt-0">
                <btn
                  kind="danger"
                  @click="showTransferCmsLicenseModal = true">
                  Transfer license
                </btn>

                <transfer-license-modal
                  type="cms"
                  :license="license"
                  :isOpen="showTransferCmsLicenseModal"
                  @close="showTransferCmsLicenseModal = false"></transfer-license-modal>
              </div>
            </div>
          </pane>
        </div>
      </template>
    </template>
  </div>
</template>

<script>
import {mapActions} from 'vuex'
import cmsLicensesApi from '../../../api/cms-licenses'
import CmsLicenseDetails from '../../../components/licenses/CmsLicenseDetails'
import PluginLicensesTable from '../../../components/licenses/PluginLicensesTable'
import LicenseHistory from '../../../components/licenses/LicenseHistory'
import PageHeader from '@/console/js/components/PageHeader';
import TransferLicenseModal from '@/console/js/components/licenses/TransferLicenseModal';

export default {
  components: {
    TransferLicenseModal,
    PageHeader,
    CmsLicenseDetails,
    PluginLicensesTable,
    LicenseHistory,
  },

  data() {
    return {
      loading: false,
      license: null,
      showTransferCmsLicenseModal: false,
    }
  },

  methods: {
    ...mapActions({
      showRenewLicensesModal: 'app/showRenewLicensesModal',
    }),

    releaseCmsLicense() {
      if (!window.confirm("Are you sure you want to release this license?")) {
        return false
      }

      cmsLicensesApi.releaseCmsLicense(this.license.key)
        .then((response) => {
          if (response.data && !response.data.error) {
            this.$store.dispatch('app/displayNotice', 'CMS license released.')
            this.$router.push({path: '/licenses/cms'})
          } else {
            this.$store.dispatch('app/displayError', response.data.error)
          }
        })
        .catch((error) => {
          const errorMessage = error.response.data && error.response.data.error ? error.response.data.error : 'Couldn’t release CMS license.'
          this.$store.dispatch('app/displayError', errorMessage)
        })
    },
  },

  mounted() {
    const licenseId = this.$route.params.id

    this.loading = true

    cmsLicensesApi.getCmsLicense(licenseId)
      .then((response) => {
        this.license = response.data.license
        this.$store.commit('app/updateRenewLicense', this.license)
        this.loading = false
      })
      .catch(() => {
        this.loading = false
      })
  }
}
</script>
