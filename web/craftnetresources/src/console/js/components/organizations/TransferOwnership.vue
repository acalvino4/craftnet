<template>
  <div class="lg:flex lg:justify-between lg:items-center">
    <div class="lg:mr-6">
      <h4 class="font-bold">Transfer ownership</h4>
      <p>Transfer this organization to another member.</p>
    </div>

    <div class="mt-6 lg:mt-0">
      <btn
        kind="danger"
        @click="openModal">Transfer
      </btn>

      <TransitionRoot
        appear
        :show="isOpen"
        as="template">
        <Dialog
          :open="isOpen"
          as="div"
          @close="closeModal">
          <div
            class="fixed bg-black bg-opacity-50 inset-0 z-10 overflow-y-auto">
            <div class="min-h-screen px-4 text-center">
              <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
              >
                <DialogOverlay class="fixed inset-0" />
              </TransitionChild>

              <span
                class="inline-block h-screen align-middle"
                aria-hidden="true">&#8203;</span>

              <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0 scale-95"
                enter-to="opacity-100 scale-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100 scale-100"
                leave-to="opacity-0 scale-95"
              >
                <div
                  class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-primary shadow-xl rounded-xl"
                >
                  <DialogTitle
                    as="h3"
                    class="text-lg font-medium leading-6"
                  >
                    Transfer ownership
                  </DialogTitle>
                  <div class="mt-2">
                    <p class="text-sm text-light">
                      Transfer this organization to another member.
                    </p>
                  </div>

                  <field
                    :vertical="true"
                    label-for="member"
                    label="Member">
                    <dropdown
                      v-model="transferMemberId"
                      :options="memberOptions"
                    />
                  </field>

                  <field
                    :vertical="true"
                    label-for="slug"
                    :label="`Type “${currentOrganization.slug}” to confirm`">
                    <textbox
                      id="slug"
                      v-model="slug"></textbox>
                  </field>

                  <div
                    class="mt-4 space-x-reverse space-x-2 flex flex-row-reverse justify-start">
                    <btn
                      kind="danger"
                      :disabled="currentOrganization.slug !== slug || !transferMemberId"
                      type="button"
                      @click="transferOrg"
                    >
                      Transfert this organization
                    </btn>

                    <btn
                      @click="closeModal">Cancel
                    </btn>

                  </div>
                </div>
              </TransitionChild>
            </div>
          </div>
        </Dialog>
      </TransitionRoot>
    </div>
  </div>
</template>

<script>
import {mapGetters, mapState} from 'vuex';
import {ref} from 'vue'
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogOverlay,
  DialogTitle,
} from '@headlessui/vue'

export default {
  components: {
    TransitionRoot,
    TransitionChild,
    Dialog,
    DialogOverlay,
    DialogTitle,
  },

  setup() {
    const isOpen = ref(false)
    const slug = ref(null)
    const transferMemberId = ref(null)

    return {
      isOpen,
      slug,
      transferMemberId,
      closeModal() {
        isOpen.value = false
      },
      openModal() {
        isOpen.value = true
      },
    }
  },

  computed: {
    ...mapState({
      user: state => state.account.user,
      members: state => state.organizations.members,
    }),

    ...mapGetters({
      currentOrganization: 'organizations/currentOrganization',
    }),

    memberOptions() {
      const options = [
        {
          label: 'Select a member',
          value: '',
        },
      ]

      this.members.forEach(member => {
        if (member.id === this.user.id) {
          return
        }

        options.push({
          label: member.name,
          value: member.id,
        })
      })

      return options
    }
  },

  methods: {
    transferOrg() {
      if (!this.transferMemberId) {
        return
      }

      this.$store.dispatch('organizations/setRole', {
        organizationId: this.currentOrganization.id,
        userId: this.transferMemberId,
        role: 'owner',
      })
        .then(() => {
          this.$store.dispatch('organizations/getOrganizationMembers', {
              organizationId: this.currentOrganization.id,
            })
            .then(() => {
              this.$store.dispatch('app/displayNotice', 'Ownership transferred.')
              this.closeModal()
            })
        })
        .catch((error) => {
          this.$store.dispatch('app/displayError', 'Couldn’t transfer ownership.')
          this.closeModal()
          throw error
        })
    }
  }
}
</script>
