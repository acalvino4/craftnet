<template>
  <div>
    <!-- CMS edition -->
    <template
      v-if="item.lineItem.purchasable.type === 'cms-edition'">
      <div class="sm:flex sm:items-center">
        <div class="sm:mr-3">
          <strong class="text-xl">Craft
            CMS</strong>
        </div>
        <edition-badge class="mt-2 sm:mt-0">
          {{
            item.lineItem.purchasable.name
          }}
        </edition-badge>
      </div>
    </template>

    <!-- Plugin edition -->
    <template
      v-else-if="item.lineItem.purchasable.type === 'plugin-edition'">
      <div class="flex">
        <div
          class="sm:flex sm:items-center">
          <div class="sm:mr-3">
            <strong
              class="text-xl"
              v-if="item.plugin">{{
                item.plugin.name
              }}</strong>
          </div>
          <edition-badge class="mt-2 sm:mt-0">
            {{
              item.lineItem.purchasable.name
            }}
          </edition-badge>
        </div>
      </div>
    </template>

    <!-- Renewals & others -->
    <template v-else>
      <div>
        <strong class="text-xl">
          <template
            v-if="item.lineItem.purchasable.type === 'cms-renewal'">
            Craft CMS
            {{
              item.lineItem.description
            }}
          </template>
          <template v-else>
            {{
              item.lineItem.description
            }}
          </template>
        </strong>

        <div class="text-light">
          <code>{{
              item.lineItem.options.licenseKey.substr(0, 4)
            }}</code>
        </div>
      </div>
    </template>
  </div>
</template>
<script>
import EditionBadge from '@/console/js/components/EditionBadge';

export default {
  components: {EditionBadge},

  props: {
    item: Object,
  }
}
</script>