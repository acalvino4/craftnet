<template>
  <div>
    <h3>Invitations</h3>

    <template v-if="invitations.length === 0">
      <p>No invitations.</p>
    </template>

    <template v-for="(invitation, invitationKey) in invitations" :key="invitationKey">
      <div class="border border-red-500 rounded-md p-4 flex justify-between">
        <div>
          <h4 class="font-bold">Invitation #{{invitation.id}}</h4>
          <pre>{{invitation}}</pre>
        </div>
        <div class="space-x-4">
          <btn
            @click="cancel({organizationId: invitation.orgId, userId: invitation.userId})"
          >Cancel</btn>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import {mapActions, mapGetters, mapState} from 'vuex';

export default {
  computed: {
    ...mapState({
      invitations: state => state.organizations.invitations,
    }),
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),
  },
  methods: {
    ...mapActions({
      cancel: 'organizations/cancelInvitation',
    }),
  },
  mounted() {
    this.$store.dispatch('organizations/getInvitations', {organizationId: this.currentOrganization.id})
  }
}
</script>