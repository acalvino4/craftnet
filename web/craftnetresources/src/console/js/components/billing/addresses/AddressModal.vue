<template>
  <modal-headless
    :isOpen="isOpen"
    @close="close"
  >
    <form method="post" action="#" @submit.prevent="save">
      <h3>
        <template v-if="address.id">
          Edit Address #{{address.id}}
        </template>
        <template v-else>
          Add new address
        </template>
      </h3>

      <div>
        <field
          :vertical="true"
          label="Title"
        >
          <textbox
            v-model="address.title"
          />

          <template v-if="countriesLoading">
            <spinner class="ml-4 relative top-1" />
          </template>
        </field>

        <field
          :vertical="true"
          label="Country"
        >
          <dropdown
            v-model="address.countryCode"
            :options="countriesOptions"
            @change="getAddressesInfo"
          />

          <template v-if="countriesLoading">
            <spinner class="ml-4 relative top-1" />
          </template>
        </field>

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
        </template>
      </div>

      <input class="hidden" type="submit" value="Save" />
    </form>
    <template v-slot:footer>
      <btn @click="close">Cancel</btn>
      <btn kind="primary" @click="save">Save</btn>
    </template>
  </modal-headless>
</template>
<script>
import ModalHeadless from '../../ModalHeadless';
import {mapState} from 'vuex';

export default {
  components: {ModalHeadless},
  data() {
    return {
      countriesLoading: false,
      address: {
        title: 'Test title',
        countryCode: null,
      }
    }
  },

  props: {
    isOpen: {
      type: Boolean,
      default: false,
    },
    editAddress: {
      type: Object,
      default: null,
    }
  },

  watch: {
    editAddress() {
      this.address = JSON.parse(JSON.stringify(this.editAddress))
      console.log('edit address change', this.address.id)
      this.getAddressesInfo()
    }
  },

  computed: {
    ...mapState({
      countries: state => state.addresses.countries,
      addressInfo: state => state.addresses.info,
    }),

    countriesOptions() {
      const options = [
        {
          value: '',
          label: '',
        }
      ]

      if (!this.countries) {
        return options;
      }


      for (const countryCode in this.countries) {
        const country = this.countries[countryCode]

        options.push({
          value: countryCode,
          label: country.name,
        });
      }

      return options;
    },

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
        parents: [this.address.countryCode]
      });
    },
    save() {
      this.$store.dispatch('addresses/saveAddress', this.address)
        .then(() => {
          this.$emit('close')
        })
    },
    close() {
      this.$emit('close')
      this.$store.commit('addresses/updateInfo', {info: null})
    },
  },

  mounted() {
    this.countriesLoading = true
    this.$store.dispatch('addresses/getCountries')
      .then(() => {
        this.countriesLoading = false
      })
      .catch(() => {
        this.countriesLoading = false
      })
  }
}
</script>