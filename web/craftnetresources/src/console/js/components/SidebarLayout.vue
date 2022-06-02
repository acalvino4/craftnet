<template>
    <div class="flex w-full">
        <div class="sidebar">
            <slot></slot>
        </div>

        <div class="flex-1 px-8">
            <div class="page-alerts">
                <template v-if="$route.meta.stripeAccountAlert">
                    <stripe-account-alert></stripe-account-alert>
                </template>
            </div>

            <router-view></router-view>
        </div>
    </div>
</template>

<script>
import StripeAccountAlert from '@/console/js/components/StripeAccountAlert';

export default {
    components: {
        StripeAccountAlert,
    }
}
</script>

<style lang="scss">
.app {
    .sidebar {
        @apply hidden overflow-auto;

        ul {
            li {
                a {
                    @apply block text-gray-800 dark:text-gray-200 px-4 py-2 mx-4 no-underline rounded-md;

                    &:hover {
                        @apply text-gray-800 dark:text-gray-200;
                    }

                    &.active {
                        @apply bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200;
                    }

                    &.disabled {
                        @apply text-gray-300 dark:text-gray-700;
                    }
                }
            }
        }
    }
}

@media (max-width: 767px) {
    .app {
        .sidebar {
            &.showing-sidebar {
                @apply block bg-primary absolute inset-0 z-10;
                top: 61px;
            }
        }
    }
}

@media (min-width: 768px) {
    .app {
        .sidebar {
            @apply w-64 block flex-shrink-0;
        }
    }
}
</style>