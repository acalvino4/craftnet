<template>
    <sidebar-layout>
        <ul>
            <li>
                <router-link @click="$emit('closeSidebar')" to="/licenses/cms">
                    Craft CMS
                </router-link>
            </li>
            <li>
                <router-link @click="$emit('closeSidebar')"
                             to="/licenses/plugins">Plugins
                </router-link>
            </li>
            <li>
                <router-link @click="$emit('closeSidebar')"
                             to="/licenses/claim">Claim License
                </router-link>
            </li>
        </ul>
    </sidebar-layout>
</template>

<script>
import SidebarLayout from '@/console/js/components/SidebarLayout'

export default {
    components: {
        SidebarLayout
    }
}
</script>