import { Capacitor } from '@capacitor/core'
import { Preferences } from '@capacitor/preferences'
import { AccessControl, BiometricAuthError, NativeBiometric } from '@capgo/capacitor-native-biometric'
import axios from '@axios'
import { useLoginSession } from '@/composables/useLoginSession'

const server = 'bilflogg.se'
const preferenceKey = 'biometric_login_user'
const passwordLoginMessage = 'Biometrisk inloggning har upphört. Logga in med ditt lösenord och aktivera funktionen igen.'

const prompt = {
  title: 'Logga in på Bilflogg',
  reason: 'Bekräfta din identitet för att logga in.',
  subtitle: 'Bekräfta din identitet för att logga in.',
  negativeButtonText: 'Avbryt',
}

const getDeviceName = () => {
  const platform = Capacitor.getPlatform()
  const ua = navigator.userAgent

  const model = platform === 'android'
    ? ua.match(/Android [\d.]+; ([^;)]+?)(?: Build|;|\))/)?.[1]
    : ua.match(/iPhone|iPad/)?.[0]

  return `${platform}${model ? ` - ${model}` : ''}`.slice(0, 100)
}

export const useBiometricLogin = () => {
  const { completeLogin } = useLoginSession()

  const clearLocal = async () => {
    // Hide the option even if deleting the secure credentials fails.
    await Preferences.remove({ key: preferenceKey })
    await NativeBiometric.deleteCredentials({ server })
  }

  const requirePassword = async () => {
    try {
      await clearLocal()
    } catch (error) {
      throw Object.assign(new Error(`${passwordLoginMessage} Det gick inte att rensa den säkra lagringen.`, { cause: error }), { requiresPassword: true })
    }
    throw Object.assign(new Error(passwordLoginMessage), { requiresPassword: true })
  }

  const isAvailable = async () => {
    if (!Capacitor.isNativePlatform())
      return false

    const result = await NativeBiometric.isAvailable({ useFallback: false })

    return result.isAvailable && result.strongBiometryIsAvailable
  }

  const isEnabled = async () => {
    if (!Capacitor.isNativePlatform())
      return false

    const { value } = await Preferences.get({ key: preferenceKey })
    const user = JSON.parse(localStorage.getItem('user_data') || 'null')
    if (value === null || (user && value !== String(user.id)))
      return false

    const { isSaved } = await NativeBiometric.isCredentialsSaved({ server })
    if (!isSaved)
      await Preferences.remove({ key: preferenceKey })

    return isSaved
  }

  const enable = async () => {
    if (!Capacitor.isNativePlatform())
      return
    if (!await isAvailable())
      throw new Error('Biometrisk autentisering är inte tillgänglig på den här enheten.')

    const user = JSON.parse(localStorage.getItem('user_data') || 'null')
    if (!user?.id || !localStorage.getItem('accessToken'))
      throw new Error('Logga in med ditt lösenord innan du aktiverar biometrisk inloggning.')

    const { data } = await axios.post('auth/device-token', { device_name: getDeviceName() })

    // Clear the flag first: partial enrollment must never appear enabled.
    await Preferences.remove({ key: preferenceKey })
    try {
      await NativeBiometric.setCredentials({
        server,
        username: String(user.id),
        password: data.data.token,
        accessControl: AccessControl.BIOMETRY_CURRENT_SET,
        title: 'Aktivera biometrisk inloggning',
        negativeButtonText: 'Avbryt',
      })
      await Preferences.set({ key: preferenceKey, value: String(user.id) })
    } catch (error) {
      await clearLocal()
      throw error
    }
  }

  const login = async () => {
    if (!Capacitor.isNativePlatform())
      return
    if (!await isEnabled())
      throw new Error('Logga in med ditt lösenord och aktivera biometrisk inloggning.')

    // This API binds biometric verification to the protected token read.
    let credentials
    try {
      credentials = await NativeBiometric.getSecureCredentials({ server, ...prompt })
    } catch (error) {
      if (Number(error.code) === BiometricAuthError.NO_PROTECTED_CREDENTIALS_FOUND)
        return requirePassword()
      throw error
    }

    const response = await axios.post('auth/device-login', { token: credentials.password }, {
      skipAuthHeader: true,
      skipAuthRedirect: true,
      validateStatus: status => status === 401 || (status >= 200 && status < 300),
    })

    if (response.status === 401) {
      return requirePassword()
    }

    return completeLogin(response.data)
  }

  const disable = async () => {
    if (!Capacitor.isNativePlatform())
      return

    await axios.delete('auth/device-token')
    await clearLocal()
  }

  return { isAvailable, isEnabled, enable, login, disable }
}
