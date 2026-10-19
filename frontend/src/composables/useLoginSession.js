import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useRoute, useRouter } from 'vue-router'

export const useLoginSession = () => {
  const ability = useAppAbility()
  const route = useRoute()
  const router = useRouter()

  const navigate = message => router.replace(
    route.query.to
      ? String(route.query.to)
      : message === 'login_success' ? '/info' : '/',
  )

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

    if (response.message === 'login_success' && beforeNavigate)
      await beforeNavigate()

    return navigate(response.message)
  }

  const completeTwoFactorLogin = async beforeNavigate => {
    for (const key of ['is_2fa', 'two_factor', 'token', 'qr'])
      localStorage.removeItem(key)

    if (beforeNavigate)
      await beforeNavigate()

    return navigate('login_success')
  }

  return { completeLogin, completeTwoFactorLogin }
}
