<template>
    <pane>
        <template v-slot:header>
            <div class="sm:flex sm:justify-between sm:items-center">
                <div class="sm:mr-6">
                    <h2 class="mb-0 text-lg">Business information</h2>
                    <p class="mb-0 text-light text-sm">Basic business
                        information and adminstrative contact information for
                        Pixel &amp; Tonic to reach you.</p>
                </div>
                <div>
                    <template v-if="!isEditing">
                        <btn class="mt-3 sm:mt-0"
                             @click="onEditClick">
                            <icon icon="pencil" size="sm"/>
                            Edit
                        </btn>
                    </template>
                </div>
            </div>
        </template>
        <template v-slot:default v-if="!isEmpty || isEditing">
            <div v-if="!isEditing">
                <ul class="info-list">
                    <li v-if="partner.logo.url" class="mb-4">
                        <div class="partner-logo">
                            <img :src="partner.logo.url">
                        </div>
                    </li>
                    <li v-if="partner.businessName">
                        <strong class="text-2xl">{{
                                partner.businessName
                            }}</strong>
                    </li>
                    <li v-if="partner.website">
                        <a :href="partner.website"
                           target="_blank">{{ partner.website }}</a>
                    </li>
                    <li v-if="partner.region">
                        {{ partner.region }}
                    </li>
                    <li v-if="basicRequirementsList.length" class="mt-2">
                        Basic Requirements:
                        <ul class="text-sm mt-2">
                            <li v-for="(value, index) in basicRequirementsList"
                                :key="index">{{ value }}
                            </li>
                        </ul>
                    </li>
                    <li v-if="partner.capabilities.length" class="mt-2 mb-2">
                        Capabilities:
                        <ul class="text-sm mt-2">
                            <li v-for="(value, index) in partner.capabilities"
                                :key="index">
                                {{ value }}
                            </li>
                        </ul>
                    </li>
                    <li v-if="partner.expertise.trim().length"
                        class="mt-2 mb-2 pt-2">
                        Areas of Expertise:
                        <ul class="text-sm mt-2">
                            <li v-for="(value, index) in expertiseList"
                                :key="index">
                                {{ value }}
                            </li>
                        </ul>
                    </li>
                    <li v-if="partner.agencySize" class="mt-2">
                        Agency Size: <span>{{ agencySizeDisplay }}</span>
                    </li>
                    <li v-if="partner.fullBio" class="mt-4">
                        Full Bio:
                        <pre
                            class="text-sm whitespace-pre-wrap">{{
                                partner.fullBio
                            }}</pre>
                    </li>
                    <li v-if="partner.shortBio" class="mt-4">
                        Short Bio:
                        <pre
                            class="text-sm whitespace-pre-wrap">{{
                                partner.shortBio
                            }}</pre>
                    </li>
                </ul>
            </div>

            <form v-else @submit.prevent="onSubmit">
                <field label-for="businessName" label="Business Name"
                       :first="true"
                       :errors="errors.businessName">
                    <textbox id="businessName" v-model="draft.businessName"
                             :is-invalid="!!errors.businessName"/>
                </field>

                <field label-for="websiteSlug" label="Website Slug"
                       instructions="Automatically generated from Business Name if blank. Not editable once your page is live. (e.g. https://craftcms.com/partners/business-name)"
                       :errors="errors.websiteSlug">
                    <textbox id="websiteSlug" v-model="draft.websiteSlug"
                             :disabled="partner.enabled"
                             :is-invalid="!!errors.websiteSlug"/>
                </field>

                <field label-for="website" label="Business Website URL"
                       :errors="errors.website">
                    <textbox id="website" v-model="draft.website"
                             is-invalid="!!errors.website"/>
                </field>

                <field label="Logo"
                       instructions="Must be an SVG that fits within a circle. Be sure not to use unrasterized fonts or base64-encoded JPGs or PNGs inside the SVG or it will cause problems with image resizing for Twitter share cards and such."
                       :errors="errors.logo">
                    <div>
                        <div class="partner-logo">
                            <img v-if="draft.logo.url" :src="draft.logo.url"
                                 style="width: 250px;" class="block mt-2 mb-2">
                        </div>
                        <btn v-if="draft.logo.url" kind="danger"
                             :small="true"
                             @click="draft.logo = {id: null, url: null}"
                             class="mt-6 mb-4">
                            <icon icon="x"/>
                            Remove
                        </btn>
                        <input v-if="!draft.logo.url" accept=".svg" type="file"
                               @change="onLogoChange" ref="logoFile"
                               class="mt-6 mb-6">
                    </div>
                </field>

                <field label-for="region" label="Region">
                    <dropdown id="region" v-model="draft.region"
                              :options="options.region"
                              :errors="errors.region"/>
                </field>

                <field label="Business details">
                    <field :vertical="true" label-for="isRegisteredBusiness"
                           :first="true"
                           :errors="errors.isRegisteredBusiness">
                        <checkbox id="isRegisteredBusiness"
                                  label="This is a registered business"
                                  instructions="Required for consideration."
                                  v-model="draft.isRegisteredBusiness"
                                  :checked-value="1"
                                  :errors="errors.isRegisteredBusiness"/>
                    </field>
                    <field :vertical="true" label-for="hasFullTimeDev"
                           :first="true"
                           :errors="errors.hasFullTimeDev">
                        <checkbox id="hasFullTimeDev"
                                  label="Business has at least one full-time Craft developer"
                                  instructions="Required for consideration."
                                  v-model="draft.hasFullTimeDev"
                                  :checked-value="1"
                                  :errors="errors.hasFullTimeDev"/>
                    </field>
                </field>

                <field label-for="capabilities" label="Capabilities"
                       :errors="errors.capabilities">
                    <checkbox-set class="sm:-mt-3" id="capabilities"
                                  v-model="draft.capabilities"
                                  :options="options.capabilities"
                                  :is-invalid="!!errors.capabilities"/>
                </field>

                <field label-for="textarea" label="Areas of Expertise"
                       instructions="Tags for relevant expertise (e.g. SEO), each on a new line">
                    <textbox type="textarea" id="expertise"
                             v-model="draft.expertise"/>
                </field>

                <field label-for="agencySize" label="Agency Size"
                       :errors="errors.agencySize">
                    <dropdown id="agencySize" v-model="draft.agencySize"
                              :options="options.agencySize"
                              :is-invalid="!!errors.agencySize"/>
                </field>

                <field label-for="textarea" label="Short Bio"
                       instructions="Max 130 characters. Shown on your listing card."
                       :errors="errors.shortBio">
                    <textbox type="textarea" id="shortBio"
                             v-model="draft.shortBio" :max="130"
                             :is-invalid="!!errors.shortBio"/>
                </field>

                <field label-for="textarea" label="Full Bio"
                       instructions="Markdown OK. Shown on your detail page."
                       :errors="errors.fullBio">
                    <textbox type="textarea" id="fullBio"
                             v-model="draft.fullBio"
                             :is-invalid="!!errors.fullBio"/>
                </field>

                <hr>

                <div>
                    <btn class="mr-3"
                         :disabled="requestPending"
                         @click="isEditing = false">Cancel
                    </btn>

                    <btn
                        type="submit"
                        kind="primary"
                        :disabled="requestPending">Save
                    </btn>

                    <spinner :class="{'invisible': !requestPending}"></spinner>
                </div>
            </form>
        </template>
    </pane>
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
import helpers from '../../mixins/helpers.js'
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
                // 'primaryContactName',
                // 'primaryContactEmail',
                // 'primaryContactPhone',
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
        },

        isEmpty() {
            return (
                !this.partner.agencySize
                && !this.partner.businessName
                && !this.partner.capabilities.length
                && !this.partner.expertise
                && !this.partner.fullBio
                && !this.partner.logo.url
                && !this.partner.region
                && !this.partner.shortBio
                && !this.partner.website
            )
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
