<template>
    <div class="mr-4 md:mr-4">
        <div class="md:px-6">
            <template
                v-if="item.lineItem.purchasable.type === 'cms-edition'">
                <img
                    class="w-12 h-12 md:w-16 md:h-16"
                    :src="staticImageUrl('craft.svg')"
                />
            </template>
            <template
                v-else-if="item.lineItem.purchasable.type === 'plugin-edition' && item.plugin">
                <img
                    v-if="item.plugin.iconUrl"
                    :src="item.plugin.iconUrl"
                    class="w-12 h-12 md:w-16 md:h-16"
                />
            </template>
            <template v-else-if="item.lineItem.purchasable.type === 'plugin-renewal'">
                <div
                    class="w-12 h-12 md:w-16 md:h-16 text-gray-500 bg-gray-100 dark:bg-gray-700 rounded-md flex items-center justify-center">
                    <icon icon="refresh"
                          class="w-6 h-6"/>
                </div>
            </template>
            <template v-else>
                <div
                    class="w-12 h-12 md:w-16 md:h-16 text-gray-500 bg-gray-100 dark:bg-gray-700 rounded-md flex items-center justify-center">
                    <icon icon="shopping-cart"
                          class="w-6 h-6"/>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
import helpers from '@/console/js/mixins/helpers';

export default {
    mixins: [helpers],

    props: {
        item: Object,
    }
}
</script>