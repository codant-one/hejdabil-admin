// src/composables/useExternalLink.js
//
// Abre una URL externa (no un archivo propio).
// - En web: comportamiento idéntico a hoy, window.open en pestaña nueva.
// - En la app nativa: usa @capacitor/browser para abrir un navegador in-app,
//   ya que window.open no funciona de forma confiable dentro de un WebView.

import { Capacitor } from '@capacitor/core'
import { Browser } from '@capacitor/browser'

const isNative = () => Capacitor.isNativePlatform()

export async function openExternalLink(url) {
  if (!isNative()) {
    window.open(url, '_blank')
    return
  }

  await Browser.open({ url })
}