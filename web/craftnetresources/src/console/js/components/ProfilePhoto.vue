<template>
  <div
    :class="{
      'w-7 h-7 rounded': size === 'md',
      'w-12 h-12 rounded-md': size === 'lg',
      'overflow-hidden flex items-center justify-center': true,
      'bg-gray-500/10 dark:bg-gray-400': !photoUrl,
    }"
  >
    <template v-if="photoUrl">
      <img :src="photoUrl" />
    </template>
    <template v-else>
      <template v-if="fallback === 'org'">
        <icon
          icon="building"
          set="solid"
          :class="{
          'w-3 h-3': size === 'md',
          'w-5 h-5': size === 'lg',
        }"
          class="text-gray-500" />
      </template>
      <template v-else>
        <icon
          icon="user"
          set="solid"
          class="w-3 h-3 text-gray-500" />
      </template>

    </template>
  </div>
</template>

<script>
export default {
  props: {
    size: {
      type: String,
      default: 'md',
    },
    photoUrl: {
      type: String,
      required: false,
    },
    fallback: {
      type: String,
      default: 'user',
    },
  }
}
</script>