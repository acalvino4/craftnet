<template>
    <sidebar-layout>
        <ul>
            <li>
                <router-link @click="$emit('closeSidebar')"
                             to="/developer/plugins">Plugins
                </router-link>
            </li>
            <li>
                <router-link @click="$emit('closeSidebar')"
                             to="/developer/sales">Sales
                </router-link>
            </li>
        </ul>
    </sidebar-layout>
</template>

<script>
import SidebarLayout from '@/console/js/components/SidebarLayout'

export default {
    components: {
        SidebarLayout,
    }
}
</script>
