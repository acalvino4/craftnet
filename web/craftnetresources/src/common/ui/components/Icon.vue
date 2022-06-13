<template>
  <component
    class="inline-block"
    :is="computedIcon"></component>
</template>

<script>

import outlineIcons from '@/common/ui/icons/outline'
import solidIcons from '@/common/ui/icons/solid'

export default {
  props: {
    icon: String,
    set: {
      type: String,
      default: 'outline',
    },
  },

  computed: {
    computedIcon() {
      if (this.set === 'outline' && outlineIcons[this.icon]) {
        return outlineIcons[this.icon]
      }

      if (this.set === 'solid' && solidIcons[this.icon]) {
        return solidIcons[this.icon]
      }

      if (outlineIcons[this.icon]) {
        return outlineIcons[this.icon]
      }
      if (solidIcons[this.icon]) {
        return solidIcons[this.icon]
      }

      return null
    },
  }
}
</script>