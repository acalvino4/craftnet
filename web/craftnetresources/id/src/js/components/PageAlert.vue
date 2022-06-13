<template>
  <div
    :class="'page-alert' + (type ? ' ' + type : '')"
    role="alert">
    <div class="main-content">
      <slot></slot>
    </div>
  </div>
</template>

<script>
export default {
  props: ['type']
}
</script>

<style lang="scss">
.page-alert {
  @apply .-mx-8 .-mt-6 .rounded-none .px-8 .py-4 .mb-6;
  @apply .bg-blue-lighter .border-blue-light .text-blue-darker;

  &.warning {
    @apply .bg-orange-lighter .border-orange-light .text-orange-darker;
  }
}
</style>
