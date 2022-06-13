<template>
  <modal-headless
    :isOpen="showInviteMembersModal"
    @close="$emit('close')">
    <h2>Invite members</h2>
    <p class="text-gray-700 dark:text-gray-300">Enter the email
      addresses of the users you would like to invite, and choose
      the role they should have.</p>

    <field
      label="Email adresses"
      instructions="Comma-separated list of emails."
      :vertical="true">
      <textbox></textbox>
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

    <template v-slot:footer>
      <btn @click="$emit('close')">Cancel</btn>
      <btn kind="primary">Invite</btn>
    </template>
  </modal-headless>
</template>


<script>
import ModalHeadless from '@/console/js/components/ModalHeadless';

export default {
  components: {ModalHeadless},
  props: ['showInviteMembersModal'],
  data() {
    return {
      role: 'owner'
    }
  }
}
</script>