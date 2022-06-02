<template>
    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog :open="isOpen" as="div" @close="$emit('close')">
            <div
                class="fixed inset-0 z-10 flex md:py-16 md:justify-center">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0"
                        enter-to="opacity-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100"
                        leave-to="opacity-0"
                    >
                        <DialogOverlay class="fixed inset-0 bg-gray-200 dark:bg-black bg-opacity-90"/>
                    </TransitionChild>

                    <span class="inline-block h-screen align-middle"
                          aria-hidden="true">&#8203;</span>

                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <div
                            class="relative flex flex-col w-full md:max-w-2xl inline-block overflow-hidden text-left align-middle transition-all transform bg-primary md:border md:rounded-xl"
                            v-bind="$attrs"
                        >
                            <div class="fixed z-10 w-full backdrop-filter backdrop-blur bg-primary bg-opacity-80">
                                <div class="relative p-4">
                                    <button @click="$emit('close')" class="rounded">
                                        <icon icon="x" class="w-6 h-6" />
                                    </button>
                                </div>
                            </div>

                            <div class="flex-1 overflow-auto p-8">
                                <div class="mt-8">
                                    <slot></slot>
                                </div>
                            </div>

                            <template v-if="$slots.footer">
                                <div class="border-t px-8 py-6 space-x-2 flex justify-end items-center">
                                    <slot name="footer"></slot>
                                </div>
                            </template>
                        </div>
                    </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script>
import {Dialog, DialogOverlay, TransitionChild, TransitionRoot} from '@headlessui/vue';

export default {
    inheritAttrs: false,

    props: {
        isOpen: Boolean,
    },

    components: {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogOverlay,
    }
}
</script>