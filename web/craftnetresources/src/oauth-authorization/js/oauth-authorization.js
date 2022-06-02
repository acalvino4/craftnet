import '../sass/oauth-authorization.scss'
import {createApp} from 'vue';
import Root from './App';
import ui from '../../common/ui/plugin'

const app = createApp(Root)

app.use(ui)

app.mount('#oauth-authorization-app')