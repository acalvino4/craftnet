<template>
    <div :class="'page-alert' + (type ? ' ' + type : '')" role="alert">
        <div class="main-content">
            <slot></slot>
        </div>
    </div>
</template>

<script>
export default {
    props: ['type']
}
</script>

<style lang="scss">
.page-alert {
    @apply rounded-none p-4 mb-6 rounded-md text-sm leading-5 font-medium;
    @apply bg-blue-200 dark:bg-blue-800 border-blue-400 text-blue-800 dark:text-blue-200;

    &.warning {
        @apply bg-yellow-200 dark:bg-yellow-800 border-yellow-400 text-yellow-800 dark:text-yellow-200;
    }
}
</style>
