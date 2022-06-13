<template>
  <pane class="mb-0">
    <div class="flex items-center">
      <div class="sm:mr-3">
        <h2
          class="mb-0 text-lg"
          v-if="!partner.enabled">Complete your
          profile ({{ totalValid }}/{{ total }})</h2>
      </div>

      <div class="w-24">
        <div class="bg-separator rounded-full max-w-sm">
          <div
            class="h-2 bg-green-500 rounded-full"
            :class="{
                        'w-0': totalValid === 0,
                        [`w-${totalValid}/${total}`]: totalValid > 0,
                    }"></div>
        </div>
      </div>
    </div>

    <ul class="pl-6 list-disc mt-4">
      <li
        v-for="(status, key) in statuses"
        :key="key"
        :class="{'text-light line-through': status.valid}">
        {{ status.message }}
      </li>
    </ul>
  </pane>
</template>

<script>
export default {
  props: ['partner', 'statuses', 'totalValid', 'total'],
}
</script>
