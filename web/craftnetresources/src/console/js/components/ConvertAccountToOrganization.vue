<template>
    <div class="lg:flex lg:justify-between lg:items-center">
        <div class="lg:mr-6">
            <h3 class="font-bold">Convert your account to an organization</h3>
            <p>You cannot convert this account to an organization until you
                leave all organizations that you’re a member of.</p>
        </div>

        <div class="mt-6 lg:mt-0">
            <btn kind="danger" @click="openModal">Convert
                <strong>{{ user.username }}</strong> to an organization
            </btn>

            <TransitionRoot appear :show="isOpen" as="template">
                <Dialog :open="isOpen" as="div" @close="closeModal">
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
                                <DialogOverlay class="fixed inset-0"/>
                            </TransitionChild>

                            <span class="inline-block h-screen align-middle"
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
                                        Convert your account to an organization
                                    </DialogTitle>
                                    <div class="mt-2">
                                        <p class="text-sm text-light">
                                            You cannot convert this account into
                                            an organization until you leave all
                                            organizations that you’re a member of.
                                        </p>
                                    </div>

                                    <field :vertical="true" label-for="new-username"
                                           label="Organization’s username">
                                        <textbox id="new-username"></textbox>
                                    </field>
                                    <field :vertical="true" label-for="old-username"
                                           :label="`Type “${user.username}” to confirm`">
                                        <textbox id="old-username"
                                                 v-model="oldUsername"></textbox>
                                    </field>

                                    <div
                                        class="mt-4 space-x-reverse space-x-2 flex flex-row-reverse justify-start">
                                        <btn
                                            kind="danger"
                                            :disabled="user.username !== oldUsername"
                                            type="button"
                                            @click="closeModal"
                                        >
                                            Convert
                                            <strong>{{ user.username }}</strong>
                                            to an organization
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
import {mapState} from 'vuex';
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
        const oldUsername = ref(null)

        return {
            isOpen,
            oldUsername,
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
        }),
    },
}
</script>
