<template>
  <modal-headless
    :isOpen="isOpen"
    @close="$emit('close')">
    <DialogTitle
      as="h3"
      class="text-lg font-medium leading-6"
    >
      Transfer {{ typeLabel }} license
    </DialogTitle>

    <template v-if="type === 'plugin'">
      <div class="font-mono text-light">
        {{ $filters.formatPluginLicense(license.key) }}
      </div>
    </template>
    <template v-if="type === 'cms' && license.pluginLicenses.length > 0">
      <div class="font-mono text-light">
        {{ license.key.substr(0, 10) }}
      </div>

      <h4 class="mt-4 font-medium leading-6">Do you want to transfer plugin
        licenses as well?</h4>
      <div class="mt-2 border-t border-b py-2">
        <template
          v-for="(pluginLicense, pKey) in license.pluginLicenses"
          :key="pKey">
          <div class="flex items-center">
            <checkbox></checkbox>
            <div class="ml-4">
              <div class="font-bold">
                {{ pluginLicense.plugin.name }}
              </div>
              <div class="text-light font-mono">
                {{ pluginLicense.key.substr(0, 4) }}
              </div>
            </div>
          </div>
        </template>
      </div>
    </template>

    <field
      :vertical="true"
      label-for="new-username"
      label="New owner’s username">
      <textbox
        id="new-username"
        v-model="newUsername"></textbox>
    </field>

    <template v-slot:footer>
      <btn
        @click="$emit('close')">Cancel
      </btn>

      <btn
        kind="danger"
        :disabled="!newUsername"
        type="button"
        @click="$emit('close')"
      >
        Transfer {{ typeLabel }} license
      </btn>
    </template>
  </modal-headless>
</template>
<script>
import ModalHeadless from '@/console/js/components/ModalHeadless';
import {mapState} from 'vuex';
import {DialogTitle} from '@headlessui/vue'

export default {
  props: ['isOpen', 'type', 'license'],
  components: {ModalHeadless, DialogTitle},
  data() {
    return {
      newUsername: null,
    }
  },
  computed: {
    ...mapState({
      user: state => state.account.user,
    }),
    typeLabel() {
      if (this.type === 'cms') {
        return 'CMS'
      }

      return this.type
    }
  },
}
</script>