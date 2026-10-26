// src/composables/usePdfSource.js
//
// Descarga un PDF con fetch() y entrega los bytes (Uint8Array) a <VuePdfEmbed>,
// en lugar de dejar que pdf.js lo pida por URL (carga por rangos/streaming),
// que en algunos iPhone (Safari y WKWebView de Capacitor) deja el visor en blanco.
// También centraliza los límites de consumo para iOS.

import { computed, onScopeDispose, ref, shallowRef, toValue, watch } from 'vue'
import { Capacitor } from '@capacitor/core'

// Tope de :scale en iOS (el canvas final es width * devicePixelRatio * scale).
export const PDF_IOS_MAX_SCALE = 1.5

// En iOS, los PDF con más páginas que esto se muestran de a una (anterior/siguiente).
export const PDF_IOS_PAGED_THRESHOLD = 5

// Capa de texto (selección/copia) en iOS. false = desactivada para ahorrar memoria/DOM.
export const PDF_IOS_TEXT_LAYER = false

const detectIOS = () => {
  try {
    if (Capacitor.getPlatform() === 'ios')
      return true
  } catch {
    // Capacitor no disponible: seguir con userAgent
  }

  if (typeof navigator === 'undefined')
    return false

  // iPadOS 13+ se identifica como "MacIntel" con pantalla táctil
  return /iPad|iPhone|iPod/.test(navigator.userAgent)
    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
}

export const isIOSPdfDevice = detectIOS()

export const pdfTextLayerEnabled = !isIOSPdfDevice || PDF_IOS_TEXT_LAYER

export const capPdfScale = scale => (isIOSPdfDevice ? Math.min(scale, PDF_IOS_MAX_SCALE) : scale)

// "%PDF". La especificación permite que la cabecera aparezca dentro de los primeros 1024 bytes.
const PDF_HEADER = [0x25, 0x50, 0x44, 0x46]

const hasPdfHeader = bytes => {
  const lastStart = Math.min(bytes.length - PDF_HEADER.length, 1024)

  for (let i = 0; i <= lastStart; i++) {
    if (PDF_HEADER.every((byte, offset) => bytes[i + offset] === byte))
      return true
  }

  return false
}

// pdf.js transfiere (y deja vacío) al worker un Uint8Array que ocupa todo su ArrayBuffer.
// Con una vista 1 byte más corta que su buffer, pdf.js hace su propia copia, así los mismos
// bytes sirven para varios <VuePdfEmbed> (miniaturas) y sobreviven a re-montajes (:key).
const toReusablePdfBytes = buffer => {
  const bytes = new Uint8Array(new ArrayBuffer(buffer.byteLength + 1), 0, buffer.byteLength)

  bytes.set(new Uint8Array(buffer))

  return bytes
}

/**
 * @param {string | import('vue').Ref<string> | (() => string)} url URL del PDF (proxy, storage o blob:).
 *   Si es vacía/null no se descarga nada y pdfSource queda en null.
 */
export function usePdfSource(url) {
  const pdfSource = shallowRef(null)
  const isLoading = ref(false)
  const error = shallowRef(null)
  let controller = null

  const abort = () => {
    controller?.abort()
    controller = null
  }

  const reload = async () => {
    abort()
    pdfSource.value = null
    error.value = null

    const currentUrl = toValue(url)
    if (!currentUrl) {
      isLoading.value = false

      return
    }

    const currentController = new AbortController()

    controller = currentController
    isLoading.value = true

    try {
      const response = await fetch(currentUrl, { signal: currentController.signal })
      if (!response.ok)
        throw new Error(`PDF request failed (HTTP ${response.status})`)

      const bytes = toReusablePdfBytes(await response.arrayBuffer())
      if (!hasPdfHeader(bytes))
        throw new Error('The response is not a PDF file')

      if (!currentController.signal.aborted)
        pdfSource.value = bytes
    } catch (e) {
      if (currentController.signal.aborted)
        return

      console.error('Could not load PDF:', currentUrl, e)
      error.value = e
    } finally {
      if (controller === currentController) {
        controller = null
        isLoading.value = false
      }
    }
  }

  // Para @loading-failed / @rendering-failed de <VuePdfEmbed>
  const handleError = e => {
    console.error('Could not display PDF:', e)
    error.value = e || new Error('PDF rendering failed')
  }

  watch(() => toValue(url), reload, { immediate: true })
  onScopeDispose(abort)

  return { pdfSource, isLoading, error, reload, handleError }
}

/**
 * Paginación solo para iOS: con más de PDF_IOS_PAGED_THRESHOLD páginas se renderiza una
 * página a la vez. En el resto de plataformas `page` es undefined (todas las páginas, como antes).
 * @param source El pdfSource devuelto por usePdfSource (reinicia la página al cambiar).
 */
export function usePdfPagination(source) {
  const numPages = ref(0)
  const currentPage = ref(1)

  const isPaged = computed(() => isIOSPdfDevice && numPages.value > PDF_IOS_PAGED_THRESHOLD)

  // En iOS solo se pinta la página 1 hasta conocer el total, para no reservar
  // canvas para todas las páginas de un PDF largo antes de decidir.
  const page = computed(() => {
    if (!isIOSPdfDevice)
      return undefined
    if (!numPages.value)
      return 1

    return isPaged.value ? currentPage.value : undefined
  })

  const onLoaded = doc => {
    numPages.value = Number(doc?.numPages || 0)
    currentPage.value = Math.min(currentPage.value, Math.max(1, numPages.value))
  }

  watch(() => toValue(source), () => {
    numPages.value = 0
    currentPage.value = 1
  })

  return { page, currentPage, numPages, isPaged, onLoaded }
}
