import router from '@/router'
import axios from 'axios'
import { useAlertStore } from '@/stores/useAlerts.js'
import { themeConfig } from '@themeConfig'
import Swal from 'sweetalert2';

const axiosIns = axios.create({
  baseURL: themeConfig.settings.urlbase,
})

// Mapa para almacenar los controladores de aborto
const pendingRequests = new Map();

// Función para generar una clave única para cada petición
const generateRequestKey = (config) => {
  return `${config.method}:${config.url}`;
};

// Función para cancelar peticiones pendientes (útil al cambiar de ruta en Vue Router)
const removePendingRequest = (config) => {
  const key = generateRequestKey(config);
  if (pendingRequests.has(key)) {
    const controller = pendingRequests.get(key);
    controller.abort(); // Cancela la petición anterior
    pendingRequests.delete(key);
  }
};

axiosIns.interceptors.request.use(
  config => {
    const token = localStorage.getItem('accessToken')
    if(token){
      config.headers.Authorization = `Bearer ${token}`
    } 
    
    // Antes de enviar, cancelamos si hay una petición igual en curso (opcional, o para búsquedas rápidas)
    // O simplemente añadimos la señal de aborto para poder cancelarla externamente
    
    removePendingRequest(config); // Cancelar dupleta anterior si existe
    
    const controller = new AbortController();
    config.signal = controller.signal;
    
    const key = generateRequestKey(config);
    pendingRequests.set(key, controller);
    
    return config
  },

  (error) => Promise.reject(error)
);

axiosIns.interceptors.response.use(response => {
  // Limpiamos la referencia al terminar exitosamente
  const key = generateRequestKey(response.config);
  pendingRequests.delete(key);
  return response
}, error => {
  const alertStore = useAlertStore()
  const { config, response: { status }, response: { data } } = error
  const originalRequest = config

  if (status === 401) {
      
    localStorage.removeItem('user_data')
    localStorage.removeItem('userAbilities')
    localStorage.removeItem('accessToken')

    alertStore.setAlert('Du har loggats ut av säkerhetsskäl. Logga in igen för att fortsätta.', 'error')
    
    router.push({ name: 'login' } )
    
  }
  
  // Si es un error de cancelación, lo ignoramos (no mostrar alerta)
  if (axios.isCancel(error)) {
    return Promise.reject(error);
  }

  // Limpiamos referencia en caso de error
  if (originalRequest) {
    const key = generateRequestKey(originalRequest);
    pendingRequests.delete(key);
  }

  // --- Lógica de Retry 429 ---
  if (error.response && error.response.status === 429) {
    Swal.fire({
      icon: 'warning',
      title: 'Tráfico alto',
      text: 'Procesando... por favor espera.',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    });

    await new Promise(resolve => setTimeout(resolve, 2000));
    return api(originalRequest);
  }

  return Promise.reject(data)
})

export { api, pendingRequests };
export default api;
