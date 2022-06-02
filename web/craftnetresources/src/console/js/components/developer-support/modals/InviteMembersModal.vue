<template>
    <modal-headless :isOpen="isOpen" @close="$emit('close')">
        <DialogTitle
            as="h3"
            class="text-lg font-medium leading-6"
        >
            Invite members
        </DialogTitle>

        <p class="text-gray-700 dark:text-gray-300">Enter
            the email addresses of the users you would like
            to invite, and choose the role they should
            have.</p>

        <field label="Email adresses"
               instructions="Comma-separated list of emails."
               :vertical="true">
            <textbox></textbox>
        </field>

        <template v-slot:footer>
            <btn @click="$emit('close')">Cancel</btn>
            <btn
                kind="primary"
                type="button"
                @click="$emit('close')"
            >
                Invite members
            </btn>
        </template>
    </modal-headless>
</template>

<script>
import {DialogTitle} from '@headlessui/vue';
import ModalHeadless from '@/console/js/components/ModalHeadless';

export default {
    props: ['isOpen'],

    components: {
        ModalHeadless,
        DialogTitle,
    }
}
</script>