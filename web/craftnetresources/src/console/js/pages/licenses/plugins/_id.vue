<template>
    <div>
        <template v-if="!loading">
            <template v-if="error">
                <page-header>
                    <p>
                        <router-link class="nav-link" to="/licenses/plugins"
                                     exact>← Plugins
                        </router-link>
                    </p>
                </page-header>

                <p class="text-red">Couldn’t load license.</p>
            </template>

            <template v-if="license">
                <page-header>
                    <div>
                        <p>
                            <router-link class="nav-link" to="/licenses/plugins"
                                         exact>← Plugins
                            </router-link>
                        </p>
                        <h1><code>{{ license.key.substr(0, 4) }}</code></h1>
                    </div>
                </page-header>

                <div class="space-y-6">
                    <plugin-license-details
                        v-model:license="license"></plugin-license-details>

                    <license-history :history="license.history"/>

                    <pane class="border border-red-500 mb-3">
                        <template v-slot:header>
                            <h2 class="mb-0 text-red-600">
                                Danger Zone</h2>
                        </template>

                        <div class="lg:flex lg:justify-between lg:items-center">
                            <div class="lg:mr-6">
                                <h3 class="font-bold">Release license</h3>
                                <p>Release this license if you no longer wish to use it,
                                    so that it can be claimed by someone else.</p>
                            </div>
                            <div class="mt-6 lg:mt-0">
                                <btn kind="danger" @click="releasePluginLicense()">
                                    Release License
                                </btn>
                            </div>
                        </div>

                        <hr>

                        <div class="lg:flex lg:justify-between lg:items-center">
                            <div class="lg:mr-6">
                                <h4 class="font-bold">Transfer license</h4>
                                <p>Transfer this plugin license to another account.</p>
                            </div>
                            <div class="mt-6 lg:mt-0">
                                <btn kind="danger" @click="showTransferPluginLicenseModal = true">
                                    Transfer license
                                </btn>

                                <transfer-license-modal type="plugin" :license="license" :isOpen="showTransferPluginLicenseModal" @close="showTransferPluginLicenseModal = false"></transfer-license-modal>
                            </div>
                        </div>
                    </pane>
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
import PluginLicenseDetails from '../../../components/licenses/PluginLicenseDetails'
import LicenseHistory from '../../../components/licenses/LicenseHistory'
import PageHeader from '@/console/js/components/PageHeader';
import TransferLicenseModal from '@/console/js/components/licenses/TransferLicenseModal';

export default {
    components: {
        TransferLicenseModal,
        PageHeader,
        PluginLicenseDetails,
        LicenseHistory,
    },

    data() {
        return {
            loading: false,
            license: null,
            error: false,
            showTransferPluginLicenseModal: false,
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
