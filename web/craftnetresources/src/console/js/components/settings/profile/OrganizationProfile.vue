<template>
  <form
    v-if="currentOrganizationDraft"
    @submit.prevent="save()">
    <div class="flex items-center justify-between">
      <h1 class="m-0">Profile</h1>
      <div>
        <btn
          kind="primary"
          type="submit"
          :disabled="loading.page"
          :loading="loading.page">Save
        </btn>
      </div>
    </div>

    <pane class="mt-6">
      <field
        label-for="title"
        label="Name"
        :first="true">
        <textbox
          id="title"
          v-model="currentOrganizationDraft.title"
          :errors="errors.title" />
      </field>

      <field
        label-for="developerUrl"
        label="Website URL">
        <textbox
          id="developerUrl"
          v-model="currentOrganizationDraft.developerUrl"
          :errors="errors.developerUrl" />
      </field>

      <field
        label-for="location"
        label="Location">
        <textbox
          id="location"
          v-model="currentOrganizationDraft.location"
          :errors="errors.location" />
      </field>

      <field
        label-for="photo"
        label="Photo">
        <div class="flex items-start">
          [photo]
        </div>
      </field>
    </pane>

    <p class="mt-4 text-sm text-light">Your profile data is being used for
      your developer page on the Plugin Store.</p>
  </form>
</template>

<script>
import {mapGetters} from 'vuex'

export default {
  data() {
    return {
      loading: {
        page: false,
        uploadPhoto: false,
        deletePhoto: false,
      },
      currentOrganizationDraft: {},
      errors: {},
    }
  },

  computed: {
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization',
    }),
  },

  methods: {
    /**
     * Save the profile.
     */
    save() {
      this.loading.page = true
      
      this.$store.dispatch('organizations/saveOrganization', this.currentOrganizationDraft)
        .then(() => {
          this.$store.dispatch('app/displayNotice', 'Profile saved.')
          this.loading.page = false
        })
    }
  },

  mounted() {
    this.currentOrganizationDraft = JSON.parse(JSON.stringify(this.currentOrganization))
  }
}
</script>
