import { canNavigate } from '@layouts/plugins/casl'
import { setupLayouts } from 'virtual:generated-layouts'
import { createRouter, createWebHistory } from 'vue-router'
import { isUserLoggedIn } from './utils'
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
       // if (userData) {
         // if (!userData.full_profile) {            
            return { name: 'complete-profile' }
          //} else {            
          //  return { name: 'dashboard-panel' }
         // }
        //}

       // return { name: 'login' }
      },
    },
    {
      path: '/complete-profile',
      name: 'complete-profile-redirect',
      redirect: () => {
       // const userData = JSON.parse(localStorage.getItem('user_data') || 'null')
       // //if (userData) {
          //if (!userData.full_profile) {            
            return { name: 'complete-profile' }
         // } else {            
          //  return { name: 'dashboard-panel' }
        //  }
      //  }

       // return { name: 'login' }
      },
    },
    ...setupLayouts(routes),
  ],
})

router.beforeEach((to) => {
  const isLoggedIn = isUserLoggedIn()

  if (to.meta?.redirectIfLoggedIn === false || to.meta?.public === true) {
    return true
  }

  if (to.meta?.redirectIfLoggedIn && isLoggedIn) {
    return { name: 'info' }
  }

  if (!canNavigate(to)) {
    if (isLoggedIn) {
      return { name: 'not-authorized' }
    } else {
      return { 
        name: 'login', 
        query: to.fullPath !== '/' ? { to: to.fullPath } : {} 
      }
    }
  }

  return true
})

export default router
