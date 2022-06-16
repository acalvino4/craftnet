<template>
  <div class="border-t py-12 md:flex">
    <item-quantity
      :item="item"
      :item-key="itemKey"></item-quantity>

    <item-icon :item="item"></item-icon>

    <div class="mt-6 md:mt-0 flex-1">
      <div>
        <div class="flex justify-between">
          <item-description
            :item="item"
            :item-key="itemKey"></item-description>

          <div class="text-right">
            <!-- Subtotal -->
            <div
              class="font-semibold text-xl">
              {{
                $filters.currency(item.lineItem.subtotal)
              }}
            </div>

            <!-- Remove button -->
            <div class="mt-1">
              <a @click="removeFromCart(itemKey)">Remove</a>
            </div>
          </div>
        </div>

        <item-updates
          :item="item"
          :item-key="itemKey"></item-updates>
      </div>

      <item-adjustments
        :item="item"
        :item-key="itemKey"></item-adjustments>
    </div>
  </div>
</template>

<script>
import ItemIcon from '@/console/js/components/cart/ItemIcon';
import ItemDescription from '@/console/js/components/cart/ItemDescription';
import ItemQuantity from '@/console/js/components/cart/ItemQuantity';
import ItemUpdates from '@/console/js/components/cart/ItemUpdates';
import ItemAdjustments from '@/console/js/components/cart/ItemAdjustments';
import {mapActions} from 'vuex';

export default {
  props: {
    item: Object,
    itemKey: Number,
  },

  components: {
    ItemIcon,
    ItemDescription,
    ItemQuantity,
    ItemUpdates,
    ItemAdjustments,
  },

  methods: {
    ...mapActions({
      removeFromCart: 'cart/removeFromCart',
    }),
  }
}
</script>