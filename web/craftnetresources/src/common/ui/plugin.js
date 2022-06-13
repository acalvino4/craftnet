import './sass/styles.scss'
import CraftComponents from './components'

const install = (app) => {
  Object.keys(CraftComponents).forEach(name => {
    app.component(name, CraftComponents[name])
  })
}

export default {install}
