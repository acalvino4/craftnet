<template>
    <a id="sidebar-toggle" href="#" @click.prevent="$emit('toggleSidebar')"
       class="block px-3 py-2 text-gray-500 hover:text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-800 rounded-md">
        <template v-if="showingSidebar">
            <icon icon="x" :size="null" class="w-5 h-5"/>
        </template>
        <template v-else>
            <icon icon="menu" :size="null" class="w-5 h-5"/>
        </template>
    </a>
</template>

<script>
export default {
    props: ['showingSidebar', 'context'],
}
</script>
