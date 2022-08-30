<template>
  <div v-if="currentOrganization">
    <div class="flex items-center justify-between">
      <h1 class="m-0">Members</h1>

      <template v-if="userIsOwner(user.id)">
        <div>
          <btn
            kind="primary"
            @click="showInviteMembersModal = true">
            Invite members
          </btn>
        </div>
      </template>
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
                  {{ member.name }}
                </div>
                <div>
                  <a :href="'mailto:'+member.email">{{member.email}}</a>
                </div>
              </div>
            </div>
          </td>
          <td class="border-b px-4 py-3 text-sm">
            {{member.role.charAt(0).toUpperCase() + member.role.slice(1)}}
          </td>
          <td class="border-b px-4 py-3 text-sm">
            <template v-if="member.role !== 'owner'">
              <member-actions
                @changeRole="changeMemberRole(member)"
                @removeMember="removeMember(member.id)"
              />
            </template>
          </td>
        </tr>
        </tbody>
      </table>
    </div>

    <template v-if="userIsOwner(user.id)">
      <invitations />
    </template>

    <change-member-role-modal
      :showChangeMemberRoleModal="showChangeMemberRoleModal"
      :member="changeMemberRoleMember"
      @close="showChangeMemberRoleModal = false"
    />

    <invite-members-modal
      :showInviteMembersModal="showInviteMembersModal"
      @close="showInviteMembersModal = false"
    />
  </div>
</template>

<script>
import {mapActions, mapGetters, mapState} from 'vuex';
import helpers from '@/console/js/mixins/helpers';
import MemberActions from '@/console/js/components/MemberActions';
import ChangeMemberRoleModal from '@/console/js/components/members/ChangeMemberRoleModal';
import InviteMembersModal from '@/console/js/components/members/InviteMembersModal';
import ProfilePhoto from '../../components/ProfilePhoto';
import Invitations from '../../components/members/Invitations';

export default {
  components: {Invitations, ProfilePhoto, InviteMembersModal, ChangeMemberRoleModal, MemberActions},
  mixins: [helpers],

  data() {
    return {
      showChangeMemberRoleModal: false,
      showInviteMembersModal: false,
      changeMemberRoleMember: null,
    }
  },

  computed: {
    ...mapState({
      members: state => state.organizations.members,
      user: state => state.account.user,
    }),
    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization',
      userIsOwner: 'organizations/userIsOwner'
    }),
  },

  methods: {
    ...mapActions({
      getOrganizationMembers: 'organizations/getOrganizationMembers',
    }),
    changeMemberRole(member) {
      this.changeMemberRoleMember = member;
      this.showChangeMemberRoleModal = true
    },

    removeMember(memberId) {
      const organizationId = this.$store.getters['organizations/currentOrganization'].id

      this.$store.dispatch('organizations/removeMember', {organizationId, memberId})
    }
  },
}
</script>

