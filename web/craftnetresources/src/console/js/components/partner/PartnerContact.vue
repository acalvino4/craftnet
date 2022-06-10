<template>
    <pane>
        <template v-slot:header>
            <div class="sm:flex sm:justify-between sm:items-center">
                <div class="sm:mr-6">
                    <h2 class="mb-0 text-lg">Contact information</h2>
                    <p class="mb-0 text-light text-sm">For Pixel & Tonic use
                        only. This will not be visible on your profile.</p>
                </div>
                <div>
                    <template v-if="!isEditing">
                        <btn class="mt-3 sm:mt-0" @click="onEditClick">
                            <icon icon="pencil" class="w-3 h-3" />
                            Edit
                        </btn>
                    </template>
                </div>
            </div>
        </template>

        <template v-slot:default v-if="!isEmpty || isEditing">
            <div v-if="!isEditing">
                <ul class="info-list">
                    <li v-if="partner.primaryContactName">
                        {{ partner.primaryContactName }}
                    </li>
                    <li v-if="partner.primaryContactEmail">
                        {{ partner.primaryContactEmail }}
                    </li>
                    <li v-if="partner.primaryContactPhone">
                        {{ partner.primaryContactPhone }}
                    </li>
                </ul>
            </div>

            <form v-else @submit.prevent="onSubmit">
                <field :first="true" label-for="primaryContactName" label="Name"
                       :errors="errors.primaryContactName">
                    <textbox id="primaryContactName"
                             v-model="draft.primaryContactName"
                             :is-invalid="!!errors.primaryContactName"/>
                </field>
                <field label-for="primaryContactEmail" label="Email"
                       :errors="errors.primaryContactEmail">
                    <textbox id="primaryContactEmail"
                             v-model="draft.primaryContactEmail"
                             :is-invalid="!!errors.primaryContactEmail"/>
                </field>
                <field label-for="primaryContactPhone" label="Phone"
                       :errors="errors.primaryContactPhone">
                    <textbox id="primaryContactPhone"
                             v-model="draft.primaryContactPhone"
                             :is-invalid="!!errors.primaryContactPhone"/>
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

<script>
import helpers from '../../mixins/helpers.js'

export default {
    props: ['partner'],

    mixins: [helpers],

    data() {
        return {
            draft: {},
            draftProps: [
                'id',
                'primaryContactName',
                'primaryContactEmail',
                'primaryContactPhone',
            ],
            errors: {},
            logoFiles: [],
            isEditing: false,
            options: {
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
        isEmpty() {
            return !this.draft.primaryContactName && !this.draft.primaryContactEmail && !this.draft.primaryContactPhone
        }
    },

    methods: {
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
            this.isEditing = true
        },

        onSubmit() {
            this.errors = {}
            this.errorMessage = ''
            this.requestPending = true

            let data = {
                draft: this.draft,
                files: this.logoFiles
            }

            this.$store.dispatch('patchPartnerContact', data)
                .then(response => {
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
                    this.$store.dispatch('app/displayError', errorMessage)
                    this.requestPending = false
                })
        }
    },

    mounted() {
        let clone = this.simpleClone(this.partner, this.draftProps)
        // clone.region = this.filterRegionValue(clone.region)
        // clone.agencySize = clone.agencySize || 'XS'

        this.draft = clone
    }
}
</script>
