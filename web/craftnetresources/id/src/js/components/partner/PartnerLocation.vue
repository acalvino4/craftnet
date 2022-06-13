<template>
  <div>
    <div class="card mb-4">
      <div class="card-body">
        <div
          class="flex"
          v-if="!isEditing">
          <ul class="flex-1 list-reset">
            <li v-if="location.title"><strong>{{ location.title }}</strong></li>
            <li v-if="location.addressLine1">{{ location.addressLine1 }}</li>
            <li v-if="location.addressLine2">{{ location.addressLine2 }}</li>
            <li v-if="cityStateZip">{{ cityStateZip }}</li>
            <li v-if="location.country">{{ location.country }}</li>
            <li v-if="location.phone">{{ location.phone }}</li>
            <li v-if="location.email">{{ location.email }}</li>
          </ul>
          <div>
            <btn
              icon="pencil"
              @click="$emit('edit', index)">Edit
            </btn>
          </div>
        </div>
        <div v-else>
          <textbox
            id="title"
            label="Location Title"
            v-model="location.title"
            :errors="localErrors.title"
            placeholder="e.g. Main Office" />
          <textbox
            id="addressLine1"
            label="Address"
            v-model="location.addressLine1"
            :errors="localErrors.addressLine1" />
          <textbox
            id="addressLine2"
            v-model="location.addressLine2"
            :errors="localErrors.addressLine2" />
          <textbox
            id="city"
            label="City"
            v-model="location.city"
            :errors="localErrors.city" />
          <textbox
            id="state"
            label="State/Region"
            v-model="location.state"
            :errors="localErrors.state" />
          <textbox
            id="zip"
            label="Zip"
            v-model="location.zip"
            :errors="localErrors.zip" />
          <textbox
            id="country"
            label="Country"
            v-model="location.country"
            :errors="localErrors.country" />
          <textbox
            id="phone"
            label="Sales Phone"
            v-model="location.phone"
            :errors="localErrors.phone" />
          <textbox
            id="email"
            label="Sales Email"
            instructions="The “Work With” button will send email here."
            v-model="location.email"
            :errors="localErrors.email" />

          <div class="mt-4 flex">
            <div class="flex-1">
              <btn
                :disabled="requestPending"
                @click="$emit('cancel', index)">Cancel
              </btn>

              <btn
                kind="primary"
                :disabled="requestPending"
                @click="$emit('save')">Save
              </btn>

              <spinner :class="{'invisible': !requestPending}"></spinner>
            </div>
            <div>
              <!-- Multiple locations not currently enabled -->
              <!-- <btn
                  v-if="location.id !== 'new'"
                  kind="danger"
                  :disabled="requestPending"
                  @click="$emit('delete', index)">Delete</button> -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Modal from '../Modal'

export default {
  props: ['index', 'location', 'editIndex', 'requestPending', 'errors'],

  components: {
    Modal,
  },

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
      this.draft = this.simpleClone
      return this.editIndex === this.index
    },
    localErrors() {
      // this.errors could be 'undefined'
      return this.errors || {}
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
