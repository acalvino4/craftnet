<template>
    <button class="text-gray-500 flex flex-1 rounded-md py-1 px-2"
            @click="showModal = true">
        <icon icon="search"
              class="mr-4 text-gray-500 md:text-gray-400 w-6 h-6"/>

        <div class="flex">
            <div class="mr-3 hidden md:block">
                Search for anything
            </div>

            <span
                class="hidden md:block text-gray-400 dark:text-gray-600 text-sm leading-5 py-0.5 px-1.5 border border-gray-300 dark:border-gray-700 rounded-md">
                    <span class="sr-only">Press </span>
                    <kbd class="font-sans">
                        <abbr title="Command" class="no-underline">⌘</abbr>
                    </kbd>
                    <span class="sr-only"> and </span>
                    <kbd class="font-sans px-0.5">/</kbd>
                    <span class="sr-only"> to search</span>
                </span>
        </div>
    </button>

    <search-modal :showModal="showModal"
                  @close="showModal = false"></search-modal>
</template>

<script>
import SearchModal from '@/console/js/components/SearchModal';

export default {
    components: {SearchModal},

    data() {
        return {
            showModal: false,
        }
    },

    methods: {
        clickAway() {
            this.showModal = false
        },
        commandListener(e) {
            if (e.key === 'Escape') {
                this.showModal = false
                return null
            }

            if (e.key === "/" && (e.ctrlKey || e.metaKey)) {
                this.showModal = !this.showModal
                return null
            }
        }
    },

    mounted() {
        window.addEventListener('keydown', this.commandListener)
    },
    unmounted() {
        window.removeEventListener('keydown', this.commandListener)
    },
}
</script>
