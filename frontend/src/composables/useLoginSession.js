import { Capacitor } from '@capacitor/core'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { usePushNotifications } from '@/composables/usePushNotifications'
import { useNotificationsStore } from '@/stores/useNotifications'
import { useRoute, useRouter } from 'vue-router'

export const useLoginSession = () => {
  const ability = useAppAbility()
  const route = useRoute()
  const router = useRouter()
  const { init, setSessionActive, getPendingRoute, clearPendingRoute } = usePushNotifications()

  const navigate = async (authenticated, beforeNavigate) => {
    const target = route.query.to ? String(route.query.to) : null
    if (!authenticated)
      return router.replace(target || '/')

    void useNotificationsStore().init()

    if (beforeNavigate)
      await beforeNavigate()

    let pendingRoute
    if (Capacitor.isNativePlatform()) {
      await init()
      await setSessionActive(true)
      pendingRoute = await getPendingRoute()
    }

    const navigation = await router.replace(target || pendingRoute || '/info')
    if (pendingRoute)
      await clearPendingRoute()

    return navigation
  }

  const completeLogin = async (response, { rememberMe, email, password, clearPassword = false, beforeNavigate } = {}) => {
    const { qr, token, accessToken, user_data, userAbilities } = response.data
    const pendingTwoFactor = response.message === '2fa' || response.message === '2fa-generate'

    ability.update(userAbilities)
    localStorage.setItem('userAbilities', JSON.stringify(userAbilities))
    localStorage.setItem('user_data', JSON.stringify(user_data))
    localStorage.setItem('accessToken', accessToken)
    localStorage.setItem('two_factor', JSON.stringify({ generate_qr: response.message === '2fa-generate' }))
    localStorage.setItem('is_2fa', JSON.stringify({ status: pendingTwoFactor }))
    if (pendingTwoFactor) {
      localStorage.setItem('token', token)
      if (qr)
        localStorage.setItem('qr', qr)
      else
        localStorage.removeItem('qr')
    } else {
      localStorage.removeItem('token')
      localStorage.removeItem('qr')
    }

    // The password is not remembered while biometric login is active.
    if (clearPassword || !rememberMe || !password)
      localStorage.removeItem('password')
    else
      localStorage.setItem('password', password)
    if (rememberMe !== undefined) {
      localStorage.setItem('remember_me', rememberMe)
      localStorage.setItem('email', rememberMe ? email : '')
    }

    return navigate(response.message === 'login_success', beforeNavigate)
  }

  const completeTwoFactorLogin = async beforeNavigate => {
    for (const key of ['is_2fa', 'two_factor', 'token', 'qr'])
      localStorage.removeItem(key)

    return navigate(true, beforeNavigate)
  }

  return { completeLogin, completeTwoFactorLogin }
}
