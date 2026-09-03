// src/plugins/nativeStorageBridge.js
//
// Puente entre localStorage y @capacitor/preferences.
// Objetivo: mantener accessToken / user_data / userAbilities persistentes
// dentro de la app nativa (Android/iOS) SIN modificar los ~100 puntos del
// código que ya leen/escriben localStorage de forma síncrona.
//
// Funciona en dos partes:
// 1. hydrateLocalStorageFromPreferences(): al arrancar la app, si localStorage
//    está vacío para esas llaves, las restaura desde Preferences.
// 2. installStorageBridge(): intercepta setItem/removeItem de localStorage
//    para espejar automáticamente los cambios hacia Preferences.
//
// En web (navegador normal, no empaquetado), ambas funciones no hacen nada:
// localStorage sigue funcionando exactamente igual que hoy.

import { Capacitor } from '@capacitor/core'
import { Preferences } from '@capacitor/preferences'

const BRIDGED_KEYS = ['accessToken', 'user_data', 'userAbilities']

const isNative = () => Capacitor.isNativePlatform()

/**
 * Restaura en localStorage cualquier llave "puenteada" que exista en
 * Preferences pero no en localStorage. Debe llamarse (con await) ANTES
 * de montar la app de Vue, para que CASL/router/axios ya encuentren
 * los datos correctos en su primera lectura síncrona.
 */
export async function hydrateLocalStorageFromPreferences() {
  if (!isNative()) return

  for (const key of BRIDGED_KEYS) {
    const alreadyHasLocalValue = localStorage.getItem(key) !== null
    if (alreadyHasLocalValue) continue

    try {
      const { value } = await Preferences.get({ key })
      if (value !== null && value !== undefined) {
        localStorage.setItem(key, value)
      }
    } catch (error) {
      console.warn(`[nativeStorageBridge] No se pudo leer "${key}" de Preferences`, error)
    }
  }
}

/**
 * Intercepta localStorage.setItem/removeItem para espejar automáticamente
 * las llaves puenteadas hacia Preferences. Llamar una sola vez, junto con
 * hydrateLocalStorageFromPreferences(), al arrancar la app.
 */
export function installStorageBridge() {
  if (!isNative()) return
  if (localStorage.__nativeBridgeInstalled) return // evita instalar dos veces

  const originalSetItem = localStorage.setItem.bind(localStorage)
  const originalRemoveItem = localStorage.removeItem.bind(localStorage)

  localStorage.setItem = (key, value) => {
    originalSetItem(key, value)

    if (BRIDGED_KEYS.includes(key)) {
      Preferences.set({ key, value }).catch(error => {
        console.warn(`[nativeStorageBridge] No se pudo escribir "${key}" en Preferences`, error)
      })
    }
  }

  localStorage.removeItem = key => {
    originalRemoveItem(key)

    if (BRIDGED_KEYS.includes(key)) {
      Preferences.remove({ key }).catch(error => {
        console.warn(`[nativeStorageBridge] No se pudo borrar "${key}" en Preferences`, error)
      })
    }
  }

  localStorage.__nativeBridgeInstalled = true
}