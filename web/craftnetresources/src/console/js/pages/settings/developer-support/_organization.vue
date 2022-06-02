<template>
    <div>
        <page-header>
            <div class="flex-1">
                <h1>Developer Support</h1>
            </div>
            <div>
                <btn kind="primary" @click="openInviteMembersModal">Invite
                    member
                </btn>

                <invite-members-modal :isOpen="isInviteMembersModalOpen"
                                      @close="closeInviteMembersModal"></invite-members-modal>
            </div>
        </page-header>

        <div>
            <div class="w-32">
                <div class="h-1 bg-gray-200 dark:bg-gray-700 rounded-full">
                    <div class="h-full bg-green-500 rounded-full" :style="{
                        width: (members.length * 100 / totalSeats) + '%'
                    }"></div>
                </div>
            </div>

            <p class="mt-2">{{ members.length }} of {{ totalSeats }} seats used.
                <a href="#" @click="openManageSeatsModal">Manage seats</a></p>

            <manage-seats-modal :isOpen="isManageSeatsModalOpen"
                                @close="closeManageSeatsModal"></manage-seats-modal>
        </div>

        <table class="w-full">
            <thead>
            <tr>
                <th class="px-4 py-2 uppercase text-xs font-medium text-gray-400 border-b">
                    Name
                </th>
                <th class="px-4 py-2 uppercase text-xs font-medium text-gray-400 border-b"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(member, memberKey) in members"
                :key="'member-' + memberKey">
                <td class="border-b px-4 py-3">
                    <div class="flex items-center">
                        <div
                            class="w-10 h-10 rounded-full overflow-hidden bg-gray-200">
                            <template v-if="member.avatar">
                                <img class="w-10 h-10"
                                     :src="staticImageUrl('avatars/' + member.avatar)"/>
                            </template>
                        </div>
                        <div class="ml-4 text-sm">
                            <div class="font-bold">
                                {{ member.name }}
                            </div>
                            <div>
                                {{ member.email }}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="border-b px-4 py-3 text-sm">
                    <member-actions></member-actions>
                </td>
            </tr>
            </tbody>
        </table>

        <p class="mt-6">
            <router-link to="/settings/developer-support/old">Old version</router-link>
        </p>
    </div>
</template>

<script>
import {mapState} from 'vuex'
import PageHeader from '@/console/js/components/PageHeader';
import helpers from '@/console/js/mixins/helpers';
import MemberActions from '@/console/js/components/developer-support/MemberActions';
import {ref} from 'vue';
import ManageSeatsModal from '@/console/js/components/developer-support/modals/ManageSeatsModal';
import InviteMembersModal from '@/console/js/components/developer-support/modals/InviteMembersModal';

export default {
    setup() {
        const isInviteMembersModalOpen = ref(false)
        const isManageSeatsModalOpen = ref(false)

        return {
            isInviteMembersModalOpen,
            isManageSeatsModalOpen,
            closeInviteMembersModal() {
                isInviteMembersModalOpen.value = false
            },
            openInviteMembersModal() {
                isInviteMembersModalOpen.value = true
            },
            closeManageSeatsModal() {
                isManageSeatsModalOpen.value = false
            },
            openManageSeatsModal() {
                isManageSeatsModalOpen.value = true
            },
        }
    },

    components: {
        InviteMembersModal,
        ManageSeatsModal,
        PageHeader,
        MemberActions,
    },

    mixins: [helpers],

    computed: {
        ...mapState({
            members: state => state.developerSupport.members,
            totalSeats: state => state.developerSupport.totalSeats,
        })
    },
}
</script>

