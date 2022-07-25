import '../sass/app.scss'

import {createApp} from 'vue'
import VueClickAway from "vue3-click-away"
import store from './store'
import router from './router'
import Root from './App.vue'
import ui from '../../common/ui/plugin'
import axios from 'axios'
import PortalVue from 'portal-vue'

window.axios = axios

const app = createApp(Root)
app.use(VueClickAway)
app.use(router)
app.use(store)
app.use(ui)
app.use(PortalVue)

import filters from './filters'

app.config.globalProperties.$filters = filters

app.mount('#app')

window.craftIdApp = app
