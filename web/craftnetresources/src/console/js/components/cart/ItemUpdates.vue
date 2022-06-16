<template>
  <div
    class="md:flex"
    :class="{
            'border-t mt-6 pt-6': item.lineItem.purchasable.type != 'plugin-renewal' && item.lineItem.purchasable.type != 'cms-renewal',
            'mt-2': !(item.lineItem.purchasable.type != 'plugin-renewal' && item.lineItem.purchasable.type != 'cms-renewal')
        }">
    <div class="md:flex-1">
      <div
        v-if="item.lineItem.purchasable.type != 'plugin-renewal' && item.lineItem.purchasable.type != 'cms-renewal'"
        class="font-bold">
        Updates
      </div>
      <div
        class="mt-2">
        <div class="max-w-sm relative">
          <template
            v-if="item.lineItem.purchasable.type === 'cms-edition' || item.lineItem.purchasable.type === 'plugin-edition'">
            <dropdown
              :fullwidth="true"
              v-model="selectedExpiryDates[item.id]"
              :options="itemUpdateOptions(itemKey)"
              @input="onSelectedExpiryDateChange(itemKey)"
            />
          </template>
          <template v-else>
                        <span>Updates until <strong>{{
                            item.lineItem.options.expiryDate
                          }}</strong></span>
          </template>
          <spinner
            v-if="itemLoading(itemKey)"
            class="absolute top-2 -right-2 transform translate-x-full"
          ></spinner>
        </div>
      </div>
    </div>

    <template
      v-if="item.lineItem.adjustments.filter(lineItemAdustment => lineItemAdustment.sourceSnapshot.type == 'extendedUpdates').length == 1">
      <template
        v-for="(adjustment, adjustmentKey) in item.lineItem.adjustments.filter(lineItemAdustment => lineItemAdustment.sourceSnapshot.type == 'extendedUpdates')"
        :key="itemKey + 'updates-adjustment-' + adjustmentKey">
        <div class="mt-4 text-right">
          <div class="font-semibold text-lg">
            {{ $filters.currency(adjustment.amount) }}
          </div>

          <div class="mt-1">
            <a @click="removeUpdate(itemKey, item.id)">Remove</a>
          </div>
        </div>
      </template>
    </template>
  </div>
</template>

<script>

import {mapGetters, mapState} from 'vuex';

export default {
  props: {
    item: Object,
    itemKey: Number,
  },

  computed: {
    ...mapState({
      expiryDateOptions: state => state.pluginStore.expiryDateOptions,
    }),

    ...mapGetters({
      cartItems: 'cart/cartItems',
      cartItemsData: 'cart/cartItemsData',
      itemLoading: 'cart/itemLoading',
    }),

    selectedExpiryDates: {
      get() {
        return JSON.parse(JSON.stringify(this.$store.state.cart.selectedExpiryDates))
      },
      set(newValue) {
        this.$store.commit('cart/updateSelectedExpiryDates', newValue)
      },
    },

    loadingItems: {
      get() {
        return JSON.parse(JSON.stringify(this.$store.state.cart.loadingItems))
      },
      set(newValue) {
        this.$store.commit('cart/updateLoadingItems', newValue)
      },
    }
  },

  methods: {
    itemUpdateOptions(itemKey) {
      const item = this.cartItems[itemKey]
      const renewalPrice = parseFloat(item.lineItem.purchasable.renewalPrice)

      let options = []
      let selectedOption = 0

      this.expiryDateOptions.forEach((option, key) => {
        if (option[0] === item.lineItem.options.expiryDate) {
          selectedOption = key
        }
      })

      for (let i = 0; i < this.expiryDateOptions.length; i++) {
        const expiryDateOption = this.expiryDateOptions[i]
        const value = expiryDateOption[0]
        const price = renewalPrice * (i - selectedOption)
        const nbYears = i + 1;

        let label = `${nbYears} ${nbYears === 1 ? 'year' : 'years'}`

        if (price !== 0) {
          let sign = ''

          if (price > 0) {
            sign = '+'
          }

          label += " (" + sign + this.$filters.currency(price) + ")"
        }

        options.push({
          label: label,
          value: value,
        })
      }

      return options
    },

    onSelectedExpiryDateChange(itemKey) {
      this.$store.commit('cart/updateLoadingItem', {itemKey, value: true})

      let item = this.cartItemsData[itemKey]
      item.expiryDate = this.selectedExpiryDates[item.id]

      this.$store.dispatch('cart/updateItem', {itemKey, item})
        .then(() => {
          this.$store.commit('cart/deleteLoadingItem', itemKey)
        })
    },

    removeUpdate(itemKey, itemId) {
      this.selectedExpiryDates[itemId] = '1y'
      this.onSelectedExpiryDateChange(itemKey)
    },
  }
}
</script>