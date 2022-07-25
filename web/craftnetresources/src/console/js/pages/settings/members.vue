<template>
  <div v-if="currentOrganization">
    <div class="flex items-center justify-between">
      <h1 class="m-0">Members</h1>

      <div>
        <btn
          kind="primary"
          @click="showInviteMembersModal = true">
          Invite member
        </btn>
      </div>
    </div>

    <div class="mt-6 pb-24">
      <table class="w-full">
        <thead>
        <tr>
          <th class="px-4 py-2 uppercase text-xs font-medium text-gray-400 border-b">
            Name
          </th>
          <th class="px-4 py-2 uppercase text-xs font-medium text-gray-400 border-b">
            Role
          </th>
          <th class="px-4 py-2 uppercase text-xs font-medium text-gray-400 border-b"></th>
        </tr>
        </thead>
        <tbody>
        <tr
          v-for="(member, memberKey) in members"
          :key="'member-' + memberKey">
          <td class="border-b px-4 py-3">
            <div class="flex items-center">
              <profile-photo
                :photo-url="member.photoUrl"
                size="md"
                shape="circle"
              />

              <div class="ml-4 text-sm">
                <div class="font-bold">
                  [member.name] {{ member.id }}
                </div>
                <div>
                  [member.email]
                </div>
              </div>
            </div>
          </td>
          <td class="border-b px-4 py-3 text-sm">
            {{ member.orgAdmin ? 'Admin' : 'Member' }}
          </td>
          <td class="border-b px-4 py-3 text-sm">
            <member-actions
              @changeRole="changeMemberRole(member.id)"
              @removeMember="removeMember(member.id)"
            />
          </td>
        </tr>
        </tbody>
      </table>
    </div>

    <change-member-role-modal
      :showChangeMemberRoleModal="showChangeMemberRoleModal"
      @close="showChangeMemberRoleModal = false"></change-member-role-modal>

    <invite-members-modal
      :showInviteMembersModal="showInviteMembersModal"
      @close="showInviteMembersModal = false"></invite-members-modal>
  </div>
</template>

<script>
import {mapActions, mapGetters, mapState} from 'vuex';
import helpers from '@/console/js/mixins/helpers';
import MemberActions from '@/console/js/components/MemberActions';
import ChangeMemberRoleModal from '@/console/js/components/members/ChangeMemberRoleModal';
import InviteMembersModal from '@/console/js/components/members/InviteMembersModal';
import ProfilePhoto from '../../components/ProfilePhoto';

export default {
  components: {ProfilePhoto, InviteMembersModal, ChangeMemberRoleModal, MemberActions},
  mixins: [helpers],

  data() {
    return {
      showChangeMemberRoleModal: false,
      showInviteMembersModal: false,
    }
  },

  computed: {
    ...mapState({
      members: state => state.organizations.members,
    }),
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization'
    }),
  },

  methods: {
    ...mapActions({
      getOrganizationMembers: 'organizations/getOrganizationMembers',
    }),
    changeMemberRole(memberKey) {
      // TODO: Implement change member role
      console.log('change member role', memberKey)

      this.showChangeMemberRoleModal = true
    },

    removeMember(memberId) {
      const organizationId = this.$store.getters['organizations/currentOrganization'].id

      this.$store.dispatch('organizations/removeMember', {organizationId, memberId})
    }
  },

  mounted() {
    if (!this.currentOrganization) {
      this.$router.push({path: '/settings/profile'})
    }

    this.getOrganizationMembers({
      organizationId: this.currentOrganization.id
    })
  },
}
</script>

