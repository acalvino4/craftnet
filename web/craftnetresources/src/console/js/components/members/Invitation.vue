<template>
  <div class="border rounded-md p-4 flex justify-between">
    <div>
      <h4 class="font-medium">{{invitation.user.email}}</h4>
      <div>{{invitation.role.name}}</div>
      <div class="mt-2 text-gray-500 font-mono text-xs">
        <div>Org ID: {{currentOrganization.id}}</div>
        <div>Date: {{invitation.dateCreated}}</div>
      </div>
    </div>
    <div class="space-x-4">
      <btn
        :loading="loading"
        :disabled="loading"
        @click="cancelInvitation(invitation)"
      >Cancel</btn>
    </div>
  </div>
</template>

<script>
import {mapGetters} from 'vuex';

export default {
  props: {
    invitation: {
      type: Object,
      required: true,
    },
  },

  data() {
    return {
      loading: false,
    }
  },

  computed: {
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),
  },

  methods: {
    cancelInvitation(invitation) {
      this.loading = true
      this.$store.dispatch('organizations/cancelInvitation', {
          organizationId: this.currentOrganization.id,
          userId: invitation.user.id
        })
        .then(() => {
          this.$store.dispatch('organizations/getInvitations', {
              organizationId: this.currentOrganization.id
            })
            .then(() => {
              this.$store.dispatch('app/displayNotice', "Invitation cancelled.")
              this.loading = true
            })
        })
        .catch((error) => {
          this.loading = false
          throw error
        })
    },
  },
}
</script>