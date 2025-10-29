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

window.Echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY, // Usa la clave de tu .env
  wsHost: import.meta.env.VITE_PUSHER_HOST ?? 'billogg', // Usar 'billogg' en lugar de localhost
  wsPort: import.meta.env.VITE_PUSHER_PORT ?? 6001, // El puerto 6001 por defecto
  wssPort: import.meta.env.VITE_PUSHER_PORT ?? 6001, // El puerto 6001 por defecto si usas SSL
  forceTLS: false, // Debe ser 'true' si usas SSL (https)
  disableStats: true, // Deshabilita el envío de estadísticas a Pusher
  enabledTransports: ['ws', 'wss'], // Permite conexiones WebSocket (ws)
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER, // 'mt1' por defecto
  encrypted: false, // Debe ser 'true' si usas SSL (https)
});

console.log('Laravel Echo configurado y conectado.'); // Para verificar en la consola

// ----------------------------------------------------------------------
// CÓDIGO DE PRUEBA: Suscripción a un canal y escucha de un evento
// ----------------------------------------------------------------------

// 1. Suscribirse al canal definido en el backend ('prueba-canal')
window.Echo.channel('prueba-canal') 
    // 2. Escuchar el nombre del evento definido en broadcastAs() ('mi.mensaje.prueba')
    .listen('.mi.mensaje.prueba', (data) => {
        
        console.log('¡Evento WebSocket recibido! Datos:', data);
        
        // 3. Mostrar una notificación simple con el mensaje recibido
        alert(`¡Notificación recibida!\nContenido: ${data.mensaje}`);

        // Opcional: Si usas Vue, puedes usar algo como:
        // this.$root.notificaciones.push(data.mensaje); 
    })
    .error((error) => {
        // En caso de error de conexión
        console.error('Error al escuchar el canal de prueba:', error);
    });

console.log('Escuchando el canal "prueba-canal"...');

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
