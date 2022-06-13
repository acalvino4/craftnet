<template>
  <div class="list-group-item">
    <slot></slot>
  </div>
</template>

<style lang="scss">
.list-group-item {
  @apply .border .border-grey-light .px-4 .py-4;

  margin-bottom: -1px;

  &:first-child {
    @apply .rounded-t;
  }

  &:last-child {
    @apply .rounded-b;
  }
}
</style>