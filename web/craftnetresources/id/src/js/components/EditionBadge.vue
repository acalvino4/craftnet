<template>
  <div :class="'edition-badge' + (type ? ' badge-' + type : '')">
    <slot></slot>
  </div>
</template>

<script>
export default {
  props: ['type']
}
</script>

<style lang="scss">
.edition-badge {
  @apply .border .border-solid .border-grey .text-grey-darker .inline-block .px-2 .py-1 .rounded .text-xs .uppercase;
}
</style>
