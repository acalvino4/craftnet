<template>
    <div :class="'badge' + (type ? ' badge-' + type : '')">
        <slot></slot>
    </div>
</template>

<script>
export default {
    props: ['type']
}
</script>

<style lang="scss">
.badge {
    @apply bg-gray-600 dark:bg-gray-400 text-white inline-block px-2 py-1 rounded text-sm;
}

.badge-success {
    @apply bg-green-500;
}
</style>
