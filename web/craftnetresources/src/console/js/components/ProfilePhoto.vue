<template>
  <div
    :class="[{
      'w-7 h-7': size === 'sm',
      'w-10 h-10': size === 'md',
      'w-12 h-12': size === 'lg',
      'rounded': size === 'sm' && shape === 'rounded',
      'rounded-md': size === 'md' && shape === 'rounded',
      'rounded-lg': size === 'lg' && shape === 'rounded',
      'rounded-full': shape === 'circle',
      'overflow-hidden flex items-center justify-center': true,
      'bg-gray-500/10 dark:bg-white/20': !photoUrl,
    }]"
  >
    <template v-if="photoUrl">
      <img :src="photoUrl" />
    </template>
    <template v-else>
      <icon
        :icon="fallback === 'org' ? 'building' : 'user'"
        set="solid"
        :class="{
            'w-3 h-3': size === 'sm',
            'w-4 h-4': size === 'md',
            'w-5 h-5': size === 'lg',
          }"
        class="text-gray-500 dark:text-white/50" />
    </template>
  </div>
</template>

<script>
export default {
  props: {
    size: {
      type: String,
      default: 'sm',
    },
    photoUrl: {
      type: String,
      required: false,
    },
    fallback: {
      type: String,
      default: 'user',
    },
    shape: {
      type: String,
      default: 'rounded',
    },
  }
}
</script>