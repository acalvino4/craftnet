<template>
  <div class="list-group-item px-4 py-5 sm:p-6">
    <slot></slot>
  </div>
</template>

<style lang="scss">
.list-group {
  .list-group-item {
    &:not(:last-child) {
      @apply border-b;
    }
  }
}
</style>