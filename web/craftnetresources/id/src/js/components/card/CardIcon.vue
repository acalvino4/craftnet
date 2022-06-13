<template>
  <div
    v-if="cardType"
    class="card-icon">
    <img
      :src="staticImageUrl('cards/'+cardType+'.svg')"
      height="20" />
  </div>

</template>

<script>
import helpers from '../../mixins/helpers'

export default {
  mixins: [helpers],

  props: ['brand'],

  computed: {
    cardType() {
      switch (this.brand) {
        case 'Visa':
          return 'visa';
        case 'American Express':
          return 'amex';
        case 'MasterCard':
          return 'mastercard';
        case 'Discover':
          return 'discover';
        case 'JCB':
          return 'jcb';
        case 'Diners Club':
          return 'diners';
      }
    }
  }
}
</script>

<style lang="scss">
.card-icon {
  @apply .inline-block;
}
</style>
