<template>
    <div>
        <page-header class="flex justify-between items-center mb-6">
            <h1>Claim license</h1>
        </page-header>

        <div class="space-y-6">
            <pane>
                <template v-slot:header>
                    <h2>Claim a Craft CMS license</h2>
                    <p class="text-light mb-0">Attach a Craft CMS license to
                        your Craft ID account.</p>
                </template>


                <form class="mb-6" @submit.prevent="claimCmsLicense()">
                    <field :vertical="true" :first="true"
                           label="Craft CMS License Key">
                        <textbox type="textarea" id="cmsLicenseKey"
                                 class="font-mono" :spellcheck="false"
                                 v-model="cmsLicenseKey"
                                 @input="cmsLicenseKeyChange" rows="5"/>
                    </field>

                    <btn class="mt-5" kind="primary" type="submit">Claim
                        License
                    </btn>
                    <spinner v-if="cmsLicenseLoading"></spinner>
                </form>

                <hr class="-mx-6">

                <form @submit.prevent="claimCmsLicenseFile()">
                    <div class="form-group">
                        <label for="licenseFile" class="block">Or upload your
                            license.key file</label>
                        <input class="form-control" type="file" id="licenseFile"
                               name="licenseFile" ref="licenseFile"
                               @change="cmsLicenseFileChange"/>
                    </div>
                    <btn class="mt-5" kind="primary" type="submit">Claim
                        License
                    </btn>
                    <spinner v-if="cmsLicenseFileLoading"></spinner>
                </form>
            </pane>

            <pane>
                <template v-slot:header>
                    <h2>Claim a plugin license</h2>
                    <p class="text-light mb-1 mb-0">Attach a plugin license to
                        your Craft ID account.</p>
                </template>

                <form @submit.prevent="claimPluginLicense()">
                    <field :vertical="true" :first="true" id="pluginLicenseKey"
                           label="Plugin License Key">
                        <textbox class="mono" :spellcheck="false"
                                 v-model="pluginLicenseKey"
                                 placeholder="XXXX-XXXX-XXXX-XXXX-XXXX-XXXX"
                                 mask="xxxx-xxxx-xxxx-xxxx-xxxx-xxxx"/>
                    </field>

                    <btn class="mt-5" kind="primary" type="submit">Claim
                        License
                    </btn>
                    <spinner v-if="pluginLicenseLoading"></spinner>
                </form>
            </pane>

            <pane>
                <template v-slot:header>
                    <h2>Claim licenses by your email address</h2>
                    <p class="text-light mt-1 mb-0">Use an email address to
                        attach Craft CMS and plugin licenses to your Craft ID
                        account.</p>
                </template>

                <form @submit.prevent="claimLicensesByEmail()">
                    <field :vertical="true" :first="true" id="email"
                           label="Email Address">
                        <textbox v-model="email"
                                 placeholder="user@example.com"/>
                    </field>
                    <btn class="mt-5" kind="primary" type="submit">Claim
                        License
                    </btn>
                    <spinner v-if="emailLoading"></spinner>
                </form>
            </pane>
        </div>
    </div>
</template>

<script>
import cmsLicensesApi from '../../api/cms-licenses'
import pluginLicensesApi from '../../api/plugin-licenses'
import claimLicensesApi from '../../api/claim-licenses'
import useVuelidate from '@vuelidate/core'
import {email, required} from '@vuelidate/validators'
import PageHeader from '@/console/js/components/PageHeader'

export default {
    components: {
        PageHeader,
    },

    setup() {
        return {v$: useVuelidate()}
    },

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

    validations() {
        return {
            email: {required, email},
        }
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
                this.cmsLicenseKey = this.$filters.formatCmsLicense(value)
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
