<template>
    <div>
        <div class="sm:flex sm:justify-between" v-if="!isEditing">
            <div class="sm:mr-6">
                <ul class="flex-1">
                    <li v-if="computedLocation.title">
                        <strong>{{ computedLocation.title }}</strong></li>
                    <li v-if="computedLocation.addressLine1">
                        {{ computedLocation.addressLine1 }}
                    </li>
                    <li v-if="computedLocation.addressLine2">
                        {{ computedLocation.addressLine2 }}
                    </li>
                    <li v-if="cityStateZip">{{ cityStateZip }}</li>
                    <li v-if="computedLocation.country">
                        {{ computedLocation.country }}
                    </li>
                    <li v-if="computedLocation.phone">{{
                            computedLocation.phone
                        }}
                    </li>
                    <li v-if="computedLocation.email">{{
                            computedLocation.email
                        }}
                    </li>
                </ul>
            </div>
            <div>
                <btn class="mt-3 sm:mt-0" @click="$emit('edit', index)">
                    <icon icon="pencil" class="w-3 h-3" />
                    Edit
                </btn>
            </div>
        </div>
        <form v-else @submit.prevent="$emit('save')">

            <field label-for="title" label="Location Title"
                   :errors="localErrors.title" :first="true">
                <textbox id="title" v-model="computedLocation.title"
                         placeholder="e.g. Main Office"
                         :is-invalid="!!localErrors.title"/>
            </field>

            <field label-for="addressLine1" label="Address Line 1"
                   :errors="localErrors.addressLine1">
                <textbox id="addressLine1"
                         v-model="computedLocation.addressLine1"
                         :is-invalid="!!localErrors.addressLine1"/>
            </field>

            <field label-for="addressLine2" label="Address Line 2"
                   :errors="localErrors.addressLine2">
                <textbox id="addressLine2"
                         v-model="computedLocation.addressLine2"
                         :is-invalid="!!localErrors.addressLine2"/>
            </field>

            <field label-for="city" label="City" :errors="localErrors.city">
                <textbox id="city" v-model="computedLocation.city"
                         :is-invalid="!!localErrors.city"/>
            </field>

            <field label-for="state" label="State/Region"
                   :errors="localErrors.state">
                <textbox id="state" v-model="computedLocation.state"
                         :is-invalid="!!localErrors.state"/>
            </field>

            <field label-for="zip" label="Zip" :errors="localErrors.zip">
                <textbox id="zip" v-model="computedLocation.zip"
                         :is-invalid="!!localErrors.zip"/>
            </field>

            <field label-for="country" label="Country"
                   :errors="localErrors.country">
                <textbox id="country" v-model="computedLocation.country"
                         :is-invalid="!!localErrors.country"/>
            </field>

            <field label-for="phone" label="Sales Phone"
                   :errors="localErrors.phone">
                <textbox id="phone" v-model="computedLocation.phone"
                         :is-invalid="!!localErrors.phone"/>
            </field>

            <field label-for="email" label="Sales Email"
                   instructions="The “Work With” button will send email here."
                   :errors="localErrors.email">
                <textbox id="email" v-model="computedLocation.email"
                         :is-invalid="!!localErrors.email"/>
            </field>

            <hr>

            <div class="flex">
                <div class="flex-1">
                    <btn
                        class="mr-3"
                        :disabled="requestPending"
                        @click="$emit('cancel', index)">Cancel
                    </btn>

                    <btn
                        type="submit"
                        kind="primary"
                        :disabled="requestPending">Save
                    </btn>

                    <spinner :class="{'invisible': !requestPending}"></spinner>
                </div>
                <div>
                    <!-- Multiple locations not currently enabled -->
                    <!-- <btn
                        v-if="computedLocation.id !== 'new'"
                        kind="danger"
                        :disabled="requestPending"
                        @click="$emit('delete', index)">Delete</button> -->
                </div>
            </div>
        </form>
    </div>
</template>

<script>
import helpers from '@/console/js/mixins/helpers.js';

export default {
    mixins: [helpers],

    props: ['index', 'location', 'editIndex', 'requestPending', 'errors'],

    data() {
        return {
            draft: {},
        }
    },

    computed: {
        cityStateZip() {
            let city = this.location.city
            let state = this.location.state
            let zip = this.location.zip
            let comma = city.length && state.length ? ',' : ''

            return `${city}${comma} ${state} ${zip}`.trim()
        },
        isEditing() {
            // eslint-disable-next-line
            this.draft = this.simpleClone(this.location)
            return this.editIndex === this.index
        },
        localErrors() {
            // this.errors could be 'undefined'
            return this.errors || {}
        },

        computedLocation: {
            get() {
                return this.location
            },

            set(location) {
                this.$emit('update:location', location)
            }
        }
    },

    mounted() {
        // go straight to the modal form after clicking
        // "Add New Location" button
        if (this.location.id === 'new') {
            this.$emit('edit', this.index)
        }
    },
}
</script>
