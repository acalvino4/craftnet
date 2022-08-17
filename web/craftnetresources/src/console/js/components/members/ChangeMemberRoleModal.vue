<template>
  <modal-headless
    :isOpen="showChangeMemberRoleModal"
    @close="$emit('close')">
    <h2>Change member {{member.id}}’s role</h2>

    <div class="mt-4">
      <h3 class="text-base font-bold">Permissions</h3>
      <div class="mt-2 space-y-2">
        <div class="border-t py-2 flex items-start">
          <div class="w-36">
            <radio
              v-model="role"
              value="admin"
              class="mr-4"
              label="Admin"></radio>
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
      <btn kind="primary" @click="setRole({
        organizationId: currentOrganization.id,
        userId: member.id,
        role,
      })">Change</btn>
    </template>
  </modal-headless>
</template>

<script>
import ModalHeadless from '@/console/js/components/ModalHeadless';
import {mapActions, mapGetters} from 'vuex';

export default {
  components: {ModalHeadless},
  props: {
    showChangeMemberRoleModal: {
      type: Boolean,
      default: false,
    },
    member: {
      type: Object,
      required: true,
    },
  },

  computed: {
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),
  },

  methods: {
    ...mapActions({
      setRole: 'organizations/setRole',
    }),
  },

  watch: {
    member() {
      this.role = this.member.role;
    }
  },

  data() {
    return {
      role: null,
    }
  }
}
</script>