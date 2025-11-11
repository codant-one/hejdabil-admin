/* eslint-disable import/order */
import '@/@iconify/icons-bundle'
import App from '@/App.vue'
import ability from '@/plugins/casl/ability'
import layoutsPlugin from '@/plugins/layouts'
import vuetify from '@/plugins/vuetify'
import { loadFonts } from '@/plugins/webfontloader'
import router from '@/router'
import axios from '@axios'
import { abilitiesPlugin } from '@casl/vue'
import '@core/scss/template/index.scss'
import '@styles/styles.scss'
import mitt from 'mitt';
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import VueClipboard from 'vue-clipboard2'

// Importa las librerías
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Asegúrate de que Pusher esté disponible globalmente (a menudo ya lo está, pero es una buena práctica)
window.Pusher = Pusher;

// ----------------------------------------------------------------------
// Configuración de Laravel Echo
// ----------------------------------------------------------------------

const hasCustomHost = !!import.meta.env.VITE_PUSHER_HOST;
const useTLS = import.meta.env.VITE_PUSHER_SSL === 'true' || !hasCustomHost;
const port = Number(import.meta.env.VITE_PUSHER_PORT) || (useTLS ? 443 : 6001);

const baseEchoConfig = {
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
  forceTLS: useTLS,
  encrypted: useTLS,
  disableStats: true,
};

const transportConfig = hasCustomHost
  ? {
      wsHost: import.meta.env.VITE_PUSHER_HOST,
      wsPort: useTLS ? undefined : port,
      wssPort: useTLS ? port : undefined,
      enabledTransports: useTLS ? ['wss'] : ['ws'],
    }
  : {};

window.Echo = new Echo({ ...baseEchoConfig, ...transportConfig });


// La suscripción al canal se movió a @core/components/Notifications.vue
// para que el componente gestione su propia escucha y emita eventos al padre.
// ----------------------------------------------------------------------

loadFonts()

window.axios = axios
// Create vue app
const app = createApp(App)
const emitter = mitt();

// Use plugins
app.use(vuetify)
app.use(createPinia())
app.use(router)
app.use(layoutsPlugin)
app.use(VueClipboard)

app.use(abilitiesPlugin, ability, {
  useGlobalProperties: true,
})

app.provide('emitter', emitter);

// Mount vue app
app.mount('#app')
