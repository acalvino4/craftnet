<template>
    <div class="empty">
        <div class="empty-body">
            <slot></slot>
        </div>
    </div>
</template>

<style lang="scss">
.empty {
    @apply flex items-center justify-center;
    min-height: 500px;

    .empty-body {
        @apply text-center py-8;
    }
}
</style>
