<template>
    <div class="hidden">
        <textbox
            ref="quantityInput"
            v-model="itemQuantity"
            :min="minQuantity"
            :max="maxQuantity"
            step="1"
            @keydown="onQuantityKeyDown($event, itemKey)"
            @input="onQuantityInput($event, itemKey)"
            :disabled="1 === 1 || (item.lineItem.purchasable.type === 'cms-edition' || item.lineItem.purchasable.type === 'plugin-edition' ? false : true)"
        />
    </div>
</template>

<script>
export default {
    props: {
        item: Object,
        itemKey: Number,
    },
    data() {
        return {
            minQuantity: 1,
            maxQuantity: 1000,
        }
    },
    computed: {
        itemQuantity: {
            get() {
                if (typeof this.$store.state.cart.itemsQuantity == undefined) {
                    return null
                }

                if (this.itemKey == undefined) {
                    return null
                }

                if (typeof this.$store.state.cart.itemsQuantity[this.itemKey] == undefined) {
                    return null
                }

                return this.$store.state.cart.itemsQuantity[this.itemKey]
            },

            set(value) {
                this.$store.commit('cart/updateItemQuantity', {
                    itemKey: this.itemKey,
                    value,
                })
            },
        }
    },
    methods: {
        onQuantityInput(value, itemKey) {
            value = parseInt(value)

            if (isNaN(value) || value < this.minQuantity) {
                value = this.minQuantity
            } else if (value > this.maxQuantity) {
                value = this.maxQuantity
            }

            this.$store.commit('cart/updateItemQuantity', {
                itemKey,
                value
            })

            this.$refs.quantityInput[itemKey].$el.value = value
        },

        onQuantityKeyDown($event) {
            let charCode = ($event.which) ? $event.which : $event.keyCode

            // prevent `e` and `-` to prevent exponent and negative notations
            if (charCode === 69 || charCode === 189) {
                $event.preventDefault()

                return false
            }
        },
    }
}
</script>