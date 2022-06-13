<template>
  <div
    class="rounded-md p-4"
    :class="[{
        'bg-green-500': kind === 'success',
        'bg-blue-200 dark:bg-blue-800': kind === 'info',
        'bg-yellow-200 dark:bg-yellow-800': kind === 'warning',
        'bg-red-500': kind === 'danger',
    }]">
    <div class="flex">
      <div class="flex-shrink-0 flex">
        <icon
          :icon="icon"
          class="h-5 w-5"
          :class="[{
                    'text-green-800 dark:text-green-200': kind === 'success',
                    'text-blue-800 dark:text-blue-200': kind === 'info',
                    'text-yellow-800 dark:text-yellow-200': kind === 'warning',
                    'text-red-800 dark:text-red-200': kind === 'danger',
                }]"></icon>
      </div>
      <div class="ml-3 flex-1 md:flex md:justify-between">
        <p
          class="text-sm leading-5"
          :class="[{
                   'text-green-800 dark:text-green-200': kind === 'success',
                    'text-blue-800 dark:text-blue-200': kind === 'info',
                    'text-yellow-800 dark:text-yellow-200': kind === 'warning',
                    'text-red-800 dark:text-red-200': kind === 'danger',
                }]">
          <slot></slot>
        </p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    kind: {
      type: String,
      default: 'info',
    },
    icon: {
      type: String,
      default: 'info-circle',
    }
  }
}
</script>