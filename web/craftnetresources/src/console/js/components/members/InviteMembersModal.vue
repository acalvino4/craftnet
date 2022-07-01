<template>
  <modal-headless
    :isOpen="showInviteMembersModal"
    @close="$emit('close')">
    <form method="post" @submit.prevent="inviteMembers()">
      <h2>Invite members</h2>
      <p class="text-gray-700 dark:text-gray-300">Enter the email
        addresses of the users you would like to invite, and choose
        the role they should have.</p>

      <field
        label="Email adresses"
        instructions="Comma-separated list of emails."
        :vertical="true">
        <textbox v-model="email"></textbox>
      </field>

      <div class="mt-4">
        <h3 class="text-base font-bold">Permissions</h3>
        <div class="mt-2 space-y-2">
          <div class="border-t py-2 flex items-start">
            <div class="w-36">
              <radio
                v-model="role"
                value="owner"
                class="mr-4"
                label="Owner"></radio>
            </div>
            <ul class="text-sm py-2">
              <li>Edit profile</li>
              <li>Manage members</li>
              <li>Manage connected apps</li>
              <li>Reset the API token</li>
              <li>Manage payment methods</li>
            </ul>
          </div>

          <div class="border-t py-2 flex items-start">
            <div class="w-36">
              <radio
                v-model="role"
                value="member"
                class="mr-4"
                label="Member"></radio>
            </div>
            <ul class="text-sm py-2">
              <li>Manage Craft & plugin licenses</li>
              <li>Purchase Craft & plugin licenses</li>
              <li>View Sales data</li>
              <li>Manage Partner profile</li>
            </ul>
          </div>
        </div>
      </div>
    </form>

    <template v-slot:footer>
      <btn @click="$emit('close')">Cancel</btn>
      <btn kind="primary" @click="inviteMembers()">Invite</btn>
    </template>
  </modal-headless>
</template>


<script>
import ModalHeadless from '@/console/js/components/ModalHeadless';
import {mapActions, mapGetters} from 'vuex';

export default {
  components: {ModalHeadless},
  props: ['showInviteMembersModal'],
  data() {
    return {
      role: 'owner',
      email: '',
    }
  },

  computed: {
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),
  },

  methods: {
    ...mapActions({
      addMember: 'organizations/addMember',
      getOrganizationMembers: 'organizations/getOrganizationMembers',
    }),
    inviteMembers() {
      this.addMember({
        organizationId: this.currentOrganization.id,
        email: this.email,
        role: this.role,
      })
        .then(() => {
          this.$emit('close')
        })
    },
  }
}
</script>