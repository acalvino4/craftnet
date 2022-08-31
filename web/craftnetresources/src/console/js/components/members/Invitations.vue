<template>
  <div>
    <h3>Pending Invitations</h3>

    <template v-if="invitations.length === 0">
      <p>No invitations.</p>
    </template>

    <template v-else>
      <div class="mt-4 space-y-4">
        <template v-for="(invitation, invitationKey) in invitations" :key="invitationKey">
          <invitation
            :invitation="invitation"
          />
        </template>
      </div>
    </template>
  </div>
</template>

<script>
import Invitation from './Invitation';

export default {
  components: {Invitation},
  props: {
    invitations: {
      type: Array,
      required: true,
    },
  },
}
</script>