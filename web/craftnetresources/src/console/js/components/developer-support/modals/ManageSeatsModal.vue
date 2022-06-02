<template>
    <modal-headless :isOpen="isOpen" @close="$emit('close')">
        <DialogTitle
            as="div"
            class="leading-6 text-center space-y-4"
        >
            <icon :icon="proPlan.icon"
                  class="text-blue-500 w-12 h-12"/>
            <h1 class="font-medium">Manage support seats</h1>
        </DialogTitle>

        <div class="mt-8 space-y-4">
            <div
                class="flex items-center space-x-6 border-t border-b py-6">
                <div class="flex-1">
                    <strong>{{ proPlan.name }}
                        Support</strong>

                    <ul class="mt-2">
                        <li class="relative pl-6"
                            v-for="(feature, featureKey) in proPlan.features"
                            :key="'features-'+featureKey">
                            <icon icon="check"
                                  class="text-green-500 absolute -ml-6 mt-1 w-4 h-4"/>
                            <span>{{ feature }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div>
                Seat cost
                <div><span class="text-xl">$79</span> <span
                    class="text-sm text-light"> per month</span>
                </div>
            </div>
            <div>
                Total seats
                <div class="flex space-x-2">
                    <textbox value="1"/>
                    <btn>-</btn>
                    <btn>+</btn>
                </div>
            </div>
            <div>
                Total cost
                <div><span class="text-xl">$395</span> <span
                    class="text-sm text-light"> per month</span>
                </div>
            </div>
        </div>

        <template v-slot:footer>
            <btn @click="$emit('close')">Cancel</btn>

            <btn
                kind="primary"
                type="button"
                @click="$emit('close')"
            >
                Update
            </btn>
        </template>
    </modal-headless>
</template>

<script>
import {DialogTitle} from '@headlessui/vue';
import {mapGetters} from 'vuex';
import helpers from '@/console/js/mixins/helpers';
import ModalHeadless from '@/console/js/components/ModalHeadless';

export default {
    props: ['isOpen'],

    mixins: [helpers],

    components: {
        ModalHeadless,
        DialogTitle,
    },

    computed: {
        ...mapGetters({
            proPlan: 'developerSupport/proPlan',
        }),
    }
}
</script>