// src/composables/useFileDownload.js
//
// Maneja la apertura/descarga de archivos que hoy usas con window.open(url).
// - En web (navegador normal): comportamiento idéntico al actual, abre en pestaña nueva.
// - En la app nativa (Capacitor): descarga el archivo, lo guarda temporalmente
//   y abre el diálogo nativo de "Abrir con / Compartir" para que el usuario
//   pueda verlo o guardarlo donde quiera.

import { Capacitor } from '@capacitor/core'
import { Filesystem, Directory } from '@capacitor/filesystem'
import { Share } from '@capacitor/share'
import { themeConfig } from '@themeConfig'

const isNative = () => Capacitor.isNativePlatform()

/**
 * Los archivos bajo /storage/ se sirven estáticos (Nginx/Apache), sin pasar
 * por el kernel HTTP de Laravel, así que el middleware de CORS nunca los toca.
 * Por eso, dentro de la app nativa, pedimos el archivo a través del endpoint
 * proxy-image (que sí pasa por Laravel) en vez de hacer fetch directo a storage.
 */
const toProxiedUrl = url => `${themeConfig.settings.urlbase}proxy-image?url=${url}`

function getFileNameFromUrl(url) {
  try {
    const clean = url.split('?')[0]
    const name = decodeURIComponent(clean.substring(clean.lastIndexOf('/') + 1))
    return name || `archivo-${Date.now()}`
  } catch {
    return `archivo-${Date.now()}`
  }
}

function blobToBase64(blob) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onloadend = () => {
      // reader.result viene como "data:<mime>;base64,AAAA..." — solo nos interesa la parte base64
      const base64 = reader.result.split(',')[1]
      resolve(base64)
    }
    reader.onerror = reject
    reader.readAsDataURL(blob)
  })
}

/**
 * Guarda/comparte un Blob que ya fue descargado manualmente (ej. desde un
 * endpoint proxy con fetch()), sin necesidad de volver a pedirlo por URL.
 * Útil para reemplazar el patrón "crear <a download> y simular click".
 * @param {Blob} blob - El blob ya obtenido (ej. await response.blob())
 * @param {string} fileName - Nombre de archivo a usar al guardar/compartir
 */
export async function saveAndShareBlob(blob, fileName) {
  if (!isNative()) {
    // Mismo comportamiento que tienes hoy en web: <a download> simulado
    const blobUrl = URL.createObjectURL(blob)
    const link = document.createElement('a')

    link.href = blobUrl
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(blobUrl)
    return
  }

  const base64Data = await blobToBase64(blob)

  const savedFile = await Filesystem.writeFile({
    path: fileName,
    data: base64Data,
    directory: Directory.Cache,
  })

  await Share.share({
    title: fileName,
    url: savedFile.uri,
    dialogTitle: 'Abrir o compartir archivo',
  })
}

/**
 * Abre o descarga un archivo a partir de su URL completa.
 * @param {string} url - URL completa del archivo (ej. themeConfig.settings.urlStorage + file)
 * @param {string} [fileName] - Nombre de archivo a usar; si no se pasa, se infiere de la URL
 */
export async function openOrDownloadFile(url, fileName) {
  if (!isNative()) {
    // Mismo comportamiento que tienes hoy en web
    window.open(url, '_blank')
    return
  }

  const finalName = fileName || getFileNameFromUrl(url)

  const response = await fetch(toProxiedUrl(url))
  if (!response.ok) {
    throw new Error(`No se pudo descargar el archivo (status ${response.status})`)
  }

  const blob = await response.blob()
  const base64Data = await blobToBase64(blob)

  const savedFile = await Filesystem.writeFile({
    path: finalName,
    data: base64Data,
    directory: Directory.Cache,
  })

  await Share.share({
    title: finalName,
    url: savedFile.uri,
    dialogTitle: 'Abrir o compartir archivo',
  })
}