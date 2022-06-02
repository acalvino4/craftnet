<template>
    <component class="inline-block" :class="{
        [`w-${computedSize} h-${computedSize}`]: this.computedSize,
    }" :is="computedIcon"></component>
</template>

<script>

import outlineIcons from '@/common/ui/icons/outline'
import solidIcons from '@/common/ui/icons/solid'

export default {
    props: {
        icon: String,
        set: {
            type: String,
            default: 'outline',
        },
        size: {
            type: [String, Number],
            default: 'base'
        }
    },

    computed: {
        computedIcon() {
            if (this.set === 'outline' && outlineIcons[this.icon]) {
                return outlineIcons[this.icon]
            }

            if (this.set === 'solid' && solidIcons[this.icon]) {
                return solidIcons[this.icon]
            }

            if (outlineIcons[this.icon]) {
                return outlineIcons[this.icon]
            }
            if (solidIcons[this.icon]) {
                return solidIcons[this.icon]
            }

            return null
        },

        computedSize() {
            if (!this.size) {
                return null
            }

            if (Number.isInteger(this.size)) {
                return this.size
            }

            const predefinedSizes = {
                sm: 3,
                base: 4,
                lg: 5,
                xl: 6,
                '2xl': 8,
                '3xl': 10,
                '4xl': 12,
                '5xl': 16,
            }

            let size = this.size

            if (predefinedSizes[this.size]) {
                size = predefinedSizes[this.size]
            }

            return size
        },
    }
}
</script>