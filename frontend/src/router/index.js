import { canNavigate } from '@layouts/plugins/casl'
import { setupLayouts } from 'virtual:generated-layouts'
import { createRouter, createWebHistory } from 'vue-router'
import { isUserLoggedIn } from './utils'
import { pendingRequests } from '@/plugins/axios'; // Importa el mapa de peticiones
import routes from '~pages'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: () => {
        const is_2fa = JSON.parse(localStorage.getItem('is_2fa') || 'null')
        const two_factor = JSON.parse(localStorage.getItem('two_factor') || 'null')

        if (two_factor && is_2fa?.status) {
          if (two_factor.generate_qr) { 
            return { name: '2fa-generate' }
          } else {            
            return { name: '2fa' }
          }
        }

        return { name: 'login' }
      },
    },
    {
      path: '/info',
      name: 'info',
      redirect: () => {
        const userData = JSON.parse(localStorage.getItem('user_data') || 'null')
        if (userData) {
          if (!userData.full_profile) {            
            return { name: 'complete-profile' }
          } else {            
            return { name: 'dashboard-panel' }
          }
        }

        return { name: 'login' }
      },
    },
    {
      path: '/complete-profile',
      name: 'complete-profile-redirect',
      redirect: () => {
        const userData = JSON.parse(localStorage.getItem('user_data') || 'null')
        if (userData) {
          if (!userData.full_profile) {            
            return { name: 'complete-profile' }
          } else {            
            return { name: 'dashboard-panel' }
          }
        }

        return { name: 'login' }
      },
    },
    ...setupLayouts(routes),
  ],
})

// Antes de cada navegación
router.beforeEach((to, from, next) => {
    // Cancelar todas las peticiones pendientes
    pendingRequests.forEach((controller) => {
        controller.abort();
    });
    pendingRequests.clear();
    
    next();
})

export default router
