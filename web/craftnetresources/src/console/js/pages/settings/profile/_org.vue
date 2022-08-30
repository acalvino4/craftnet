<template>
  <div>
    <form
      v-if="currentOrganizationDraft"
      @submit.prevent="save()">
      <div class="flex items-center justify-between">
        <h1 class="m-0">Profile #{{ currentOrganization.id }}</h1>
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

    <pane class="mt-6 border border-red-500 mb-3">
      <template v-slot:header>
        <h2 class="mb-0 text-red-600">
          Danger Zone</h2>
      </template>

      <remove-organization />


      <hr>

      <div class="lg:flex lg:justify-between lg:items-center">
        <div class="lg:mr-6">
          <h4 class="font-bold">Transfer ownership</h4>
          <p>Transfer organization to another user.</p>
        </div>
        <div class="mt-6 lg:mt-0">
          <btn
            kind="danger">Transfer
          </btn>
        </div>
      </div>
    </pane>
  </div>
</template>

<script>
import {mapGetters, mapState} from 'vuex'
import RemoveOrganization from '../../../components/RemoveOrganization';

export default {
  components: {RemoveOrganization},
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
    ...mapState({
      user: state => state.account.user,
    }),

    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization',
      userIsOwner: 'organizations/userIsOwner',
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
    if (!this.userIsOwner(this.user.id)) {
      this.$router.push('/')
    }

    this.currentOrganizationDraft = JSON.parse(JSON.stringify(this.currentOrganization))
  }
}
</script>
