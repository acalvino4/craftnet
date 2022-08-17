<template>
  <div>
    <h2>Accept/Decline Invitations</h2>

    <field
      label="Organization ID"
      :vertical="true"
    >
      <textbox
        v-model="invitationOrgId"
      />
    </field>

    {{invitationOrgId}}

    <div class="mt-6 space-x-2">
      <btn @click="acceptInvitation({organizationId: invitationOrgId})">Accept</btn>
      <btn @click="declineInvitation({organizationId: invitationOrgId})">Decline</btn>
    </div>
  </div>
</template>

<script>
import {mapActions} from 'vuex';

export default {
  data() {
    return {
      invitationOrgId: '',
    };
  },
  methods: {
    ...mapActions({
      acceptInvitation: 'organizations/acceptInvitation',
      declineInvitation: 'organizations/declineInvitation',
    }),
  }
}
</script>