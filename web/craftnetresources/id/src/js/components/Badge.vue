<template>
  <div :class="'badge' + (type ? ' badge-' + type : '')">
    <slot></slot>
  </div>
</template>

<script>
export default {
  props: ['type']
}
</script>

<style lang="scss">
.badge {
  @apply .bg-grey-dark .text-white .inline-block .px-2 .py-1 .rounded .text-sm;
}

.badge-success {
  @apply .bg-green;
}
</style>
