<template>
  <div>
    <h2>Addresses</h2>

    <form @submit.prevent="getAddressesInfo">
      <field
        :vertical="true"
        label="Country Code "
      >
        <textbox
          v-model="countryCode"
          autocomplete="off"
        />
      </field>

      <btn class="mt-4" type="submit">Refresh</btn>
    </form>

    <template v-if="addressInfo">
      <field
        :vertical="true"
        label="Subdivision"
      >
        <dropdown
          v-model="address.subdivision"
          :options="subdivisionOptions"
        />
      </field>

      <field
        :vertical="true"
        label="Subdivision Child"
      >
        <dropdown
          v-model="address.subdivisionChild"
          :options="subdivisionChildrenOptions"
        />
      </field>

      <template v-for="(usedField, usedFieldKey) in addressInfo.format.usedFields" :key="usedFieldKey">
        <div>
          <field
            :vertical="true"
            :label="usedField"
            :required="!!addressInfo.format.requiredFields.find(f => f === usedField)"
          >
            <textbox
              v-model="address[usedField]"
            />
          </field>
        </div>
      </template>

      <hr>

      <h3>Format</h3>

      <pre>{{addressInfo.format}}</pre>
    </template>
  </div>
</template>

<script>
import {mapState} from 'vuex';
import Textbox from '../../../../common/ui/components/Textbox';
import Field from '../../../../common/ui/components/Field';
import Dropdown from '../../../../common/ui/components/Dropdown';

export default {
  data() {
    return {
      countryCode: 'BR',
      address: {}
    }
  },

  components: {Dropdown, Field, Textbox},

  computed: {
    ...mapState({
      addressInfo: state => state.addresses.info,
    }),

    subdivisionOptions() {
      if (!this.addressInfo) {
        return [];
      }

      const options = []

      for (const subdivisionCode in this.addressInfo.subdivisions) {
        const subdivision = this.addressInfo.subdivisions[subdivisionCode]

        options.push({
          value: subdivisionCode,
          label: subdivision.name,
        });
      }

      return options;
    },

    selectedSubdivision() {
      if (!this.addressInfo) {
        return null;
      }

      return this.addressInfo.subdivisions[this.address.subdivision];
    },

    subdivisionChildrenOptions() {
      if (!this.selectedSubdivision) {
          return [];
      }

      const options = []

      for (const childCode in this.selectedSubdivision.children) {
        const child = this.selectedSubdivision.children[childCode]

        options.push({
          value: childCode,
          label: child.name,
        });
      }

      return options;
    }
  },

  methods: {
    getAddressesInfo() {
      this.$store.dispatch('addresses/getInfo', {
        parents: [this.countryCode]
      });
    },
  },

  mounted() {
      this.getAddressesInfo();
  }
}
</script>