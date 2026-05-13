import router from '@/router'
import axios from 'axios'
import { useAlertStore } from '@/stores/useAlerts.js'
import { themeConfig } from '@themeConfig'

const axiosIns = axios.create({
  baseURL: themeConfig.settings.urlbase,
})

const INACTIVE_USER_MESSAGE = 'Denna användare har inaktiverats. Kontakta administratören.'
const DEFAULT_AUTH_LOGOUT_MESSAGE = 'Din session har avslutats. Logga in igen för att fortsätta.'

const isInactiveUserError = (rawMessage, rawErrors) => {
  const normalizedMessage = String(rawMessage || '').toLowerCase()
  const normalizedErrors = String(rawErrors || '').toLowerCase()

  return (
    normalizedMessage.includes('user_inactive')
    || normalizedMessage.includes('user inactive')
    || normalizedErrors.includes('inaktiverats')
    || normalizedErrors.includes('inaktiverad')
    || normalizedErrors.includes('deactivated')
  )
}

const resolveAuthFailureMessage = (rawMessage, rawErrors) => {
  if (isInactiveUserError(rawMessage, rawErrors)) {
    return INACTIVE_USER_MESSAGE
  }

  return DEFAULT_AUTH_LOGOUT_MESSAGE
}

axiosIns.interceptors.request.use(
  config => {
    if (config.skipAuthHeader) {
      return config
    }

    const token = localStorage.getItem('accessToken')
    if(token){
      config.headers.Authorization = `Bearer ${token}`
    }
    
    return config
})

let isRedirectingToLogin = false

axiosIns.interceptors.response.use(response => {
  return response
}, error => {
  const alertStore = useAlertStore()
  const config = error?.config
  const status = error?.response?.status
  const data = error?.response?.data
  const rawErrors = typeof data?.errors === 'string'
    ? data.errors
    : ''
  const rawMessage = typeof data?.message === 'string'
    ? data.message
    : typeof error?.message === 'string'
      ? error.message
      : ''
  const normalizedMessage = rawMessage.toLowerCase()

  const isAuthFailure = status === 401
    || (
      status === 403
      && (
        normalizedMessage.includes('user is not logged in')
        || normalizedMessage.includes('unauthenticated')
      )
    )
    || (
      status === 500
      && normalizedMessage.includes('user is not logged in')
    )

  if (config?.skipAuthRedirect) {
    return Promise.reject(data ?? error)
  }

  if (isAuthFailure) {
    localStorage.removeItem('user_data')
    localStorage.removeItem('userAbilities')
    localStorage.removeItem('accessToken')

    if (window?.Echo) {
      try {
        window.Echo.disconnect()
      } catch (disconnectError) {
      }
    }

    const isAlreadyOnLogin = router.currentRoute.value?.name === 'login'

    if (!isRedirectingToLogin && !isAlreadyOnLogin) {
      isRedirectingToLogin = true
      const message = resolveAuthFailureMessage(rawMessage, rawErrors)
      alertStore.setAlert(message, 'error')
      router.push({
        name: 'login',
        query: {
          reason: 'force_logout',
          message,
        },
      }).finally(() => {
        isRedirectingToLogin = false
      })
    }
  }

  return Promise.reject(data ?? error)
})

export default axiosIns
