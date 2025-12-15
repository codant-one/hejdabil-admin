import { createVuetify } from 'vuetify'
import defaults from './defaults'
import { icons } from './icons'
import theme from './theme'

// Styles
import 'vuetify/styles'
import '@core/scss/template/libs/vuetify/index.scss'

export default createVuetify({
  defaults,
  icons,
  theme,
  display: {
    thresholds: {
      xs: 0,
      sm: 600,
      md: 960,
      lg: 1024,  // Cambiar lg a 1024px
      xl: 1280,
      xxl: 1920,
    },
  },
})
