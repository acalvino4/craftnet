<template>
  <div class="thank-you">
    <div class="icon">
      <icon
        icon="check"
        class="text-light w-4 h-4" />
    </div>
    <h1>Thank You!</h1>
    <p>Your order has been processed successfully.</p>
    <p>
      <router-link
        class="nav-link"
        to="/">Continue to Craft ID
      </router-link>
    </p>
  </div>
</template>

<style lang="scss">
.thank-you {
  @apply text-center py-8;

  .icon {
    @apply border border-separator border-solid inline-block rounded rounded-full relative mb-8;
    width: 80px;
    height: 80px;

    svg {
      @apply mt-8 mb-4 text-light absolute;
      top: calc(50% - 54px);
      left: calc(50% - 24px);
      width: 48px;
      height: 48px;
    }
  }

  h1 {
    @apply mb-2;
  }
}
</style>
