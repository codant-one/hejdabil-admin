<script setup>

import { useTheme } from 'vuetify'
import { ref, onMounted, nextTick } from 'vue'
import VuePdfEmbed from 'vue-pdf-embed'
import SignaturePad from 'signature_pad'
import axios from '@/plugins/axios'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue"
import VideoLoader from "@/components/common/VideoLoader.vue";
import logo from "@images/logos/billogg-logo.svg";

const props = defineProps({
  token: {
    type: String,
    required: true,
  },
})

// --- Refs de Estado (Ahora más simples) ---
const isLoading = ref(true)
const pdfSource = ref(null)
const signaturePlacement = ref({ x: null, y: null, page: 1, isStatic: false, visible: false, alignment: 'left' })
const isSignatureModalVisible = ref(false)
const signaturePad = ref(null)
const finalState = ref(null)
const isSubmitting = ref(false)
const isAlreadySigned = ref(false)
const signedInfo = ref(null)
const pdfContainer = ref(null)
const computedPlacement = ref({ left: null, top: null })
// --- Refs de Elementos del DOM ---
const signatureCanvas = ref(null)
const isRequestOngoing = ref(false)
// --- Lógica del Tema (para el lienzo de firma) ---
const { global } = useTheme()

// --- Funciones del Componente (Refactorizadas) ---

const checkTokenStatus = async () => {
  try {
    const response = await axios.get(`/signatures/${props.token}/status`)
    
    if (response.data.status === 'signed') {
      // El documento ya está firmado
      isAlreadySigned.value = true
      signedInfo.value = {
        signedAt: response.data.signed_date_formatted,
        agreementId: response.data.agreement_id,
        message: response.data.message
      }
      return true
    } else if (response.data.status === 'expired') {
      finalState.value = {
        type: 'error',
        icon: 'mdi-alert-circle-outline',
        title: 'Länk utgången',
        message: 'Denna signeringslänk har löpt ut.',
      }
      return false
    }
    
    return response.data.status === 'sent'
  } catch (error) {
    console.error("Kunde inte verifiera token status:", error)
    if (error.response?.status === 404) {
      finalState.value = {
        type: 'error',
        icon: 'mdi-alert-circle-outline',
        title: 'Ogiltig länk',
        message: 'Signeringslänken är ogiltig.',
      }
    }
    return false
  }
}

// Cargar el PDF según el estado
const loadPdf = async () => {
  try {
    let pdfResponse
    
    if (isAlreadySigned.value) {
      // Cargar el PDF firmado
      pdfResponse = await axios.get(`/signatures/${props.token}/get-signed-pdf`, { 
        responseType: 'blob' 
      })
    } else {
      // Cargar el PDF sin firmar
      pdfResponse = await axios.get(`/signatures/${props.token}/get-unsigned-pdf`, { 
        responseType: 'blob' 
      })
    }
    
    pdfSource.value = URL.createObjectURL(pdfResponse.data)
  } catch (error) {
    console.error("Kunde inte ladda PDF:", error)
    throw error
  }
}

// Cargar los detalles de posicionamiento de la firma (solo si no está firmado)
const loadSignatureDetails = async () => {
  try {
    const response = await axios.get(`/signatures/${props.token}/details`)
    
    const { placement_x, placement_y, placement_page, signature_alignment } = response.data

    if (placement_x !== null && placement_y !== null) {
      signaturePlacement.value = {
        x: placement_x,
        y: placement_y,
        page: placement_page || 1,
        isStatic: false,
        visible: true,
        alignment: signature_alignment || 'left',
      }
    } else {
      signaturePlacement.value = {
        x: null, 
        y: null,
        page: 1,
        isStatic: true,
        visible: true,
        alignment: signature_alignment || 'left',
      }
    }
  } catch (error) {
    console.error("Kunde inte ladda signeringsdetaljer:", error)
    throw error
  }
}

// Calcula la posición del placeholder considerando múltiples páginas
// y desplaza el scroll del contenedor para poner el placeholder en vista
const calculatePlaceholderPosition = (shouldScroll = false) => {
  if (signaturePlacement.value.isStatic || !signaturePlacement.value.visible) {
    return
  }

  const container = pdfContainer.value
  if (!container) {
    setTimeout(() => calculatePlaceholderPosition(shouldScroll), 200)
    return
  }

  const pages = Array.from(container.querySelectorAll('canvas, svg, img'))
  if (pages.length === 0) {
    setTimeout(() => calculatePlaceholderPosition(shouldScroll), 200)
    return
  }

  const targetPageIndex = (signaturePlacement.value.page || 1) - 1
  const targetPage = pages[targetPageIndex] || pages[0]

  // Usar EXACTAMENTE el mismo método que el admin
  const pageRect = targetPage.getBoundingClientRect()
  const containerRect = container.getBoundingClientRect()

  // Dimensiones de la página
  const pageWidth = pageRect.width
  const pageHeight = pageRect.height

  // Convertir porcentajes a píxeles locales (inversa del cálculo del admin)
  const x_percent = parseFloat(signaturePlacement.value.x)
  const y_percent = parseFloat(signaturePlacement.value.y)
  
  const localX = (x_percent / 100) * pageWidth
  const localY = (y_percent / 100) * pageHeight

  // EXACTA fórmula del admin en handleAdminPdfClick
  const absoluteX = (pageRect.left - containerRect.left) + localX + (container.scrollLeft || 0)
  const absoluteY = (pageRect.top - containerRect.top) + localY + (container.scrollTop || 0)

  computedPlacement.value = { left: absoluteX, top: absoluteY }

  // Debug log para mobile
  if (window.innerWidth <= 768) {
    console.log('📱 Mobile Position:', {
      screenWidth: window.innerWidth,
      pageWidth,
      pageHeight,
      percentX: x_percent,
      percentY: y_percent,
      localX,
      localY,
      absoluteX,
      absoluteY
    })
  }

  // Desplazar la VENTANA para que el placeholder quede visible
  if (shouldScroll) {
    setTimeout(() => {
      const placeholder = document.querySelector('.signature-placeholder')
      if (placeholder) {
        const rect = placeholder.getBoundingClientRect()
        const offsetFactor = window.innerWidth <= 768 ? 0.5 : 0.35
        const scrollY = window.scrollY + rect.top - (window.innerHeight * offsetFactor)
        window.scrollTo({ top: scrollY, behavior: 'smooth' })
      }
    }, 500)
  }
}

// Carga tanto el PDF como los detalles de la firma
const loadSignatureData = async () => {
  isLoading.value = true
  
  try {
    // Primero verificar el estado del token
    const canProceed = await checkTokenStatus()
    
    if (!canProceed && !isAlreadySigned.value) {
      isLoading.value = false
      return
    }
    
    // Cargar el PDF
    await loadPdf()
    
    // Solo cargar detalles de firma si no está firmado
    if (!isAlreadySigned.value) {
      await loadSignatureDetails()
      
      // Esperar a que el PDF se renderice y calcular posición
      await nextTick()
      // Primer cálculo sin scroll - esperar más para que el PDF se renderice completamente
      setTimeout(() => {
        calculatePlaceholderPosition(false)
      }, 500)
      
      // Segundo cálculo para asegurar que el PDF está completamente renderizado
      setTimeout(() => {
        calculatePlaceholderPosition(false)
      }, 1000)
      
      // Después de que el video termine (aprox 3 segundos), hacer scroll al placeholder
      setTimeout(() => {
        calculatePlaceholderPosition(true)
      }, 3500)
      
      // Recalcular al redimensionar y al hacer scroll con debounce
      let resizeTimeout
      const recalculateHandler = () => {
        clearTimeout(resizeTimeout)
        resizeTimeout = setTimeout(() => {
          calculatePlaceholderPosition(false)
        }, 100)
      }
      
      window.addEventListener('resize', recalculateHandler)
      window.addEventListener('scroll', recalculateHandler, { passive: true })
      
      // También recalcular cuando el contenedor hace scroll
      if (pdfContainer.value) {
        pdfContainer.value.addEventListener('scroll', recalculateHandler, { passive: true })
      }
    }
    
  } catch (error) {
    console.error("Ett fel uppstod:", error)
    if (!finalState.value) {
      finalState.value = {
        type: 'error',
        icon: 'mdi-alert-circle-outline',
        title: 'Fel',
        message: 'Kunde inte ladda dokumentet.',
      }
    }
  } finally {
    isLoading.value = false
  }
}

// Abrir el diálogo de firma
const openSignatureModal = () => {
  if (isAlreadySigned.value) return // No permitir firmar si ya está firmado
  
  isSignatureModalVisible.value = true
  nextTick(() => {
    const canvas = signatureCanvas.value
    if (canvas) {
      signaturePad.value = new SignaturePad(canvas, {
        backgroundColor: 'rgba(0, 0, 0, 0)',
        penColor: global.name.value === 'dark' ? 'rgb(220, 220, 240)' : 'rgb(0, 0, 0)',
      })
      const ratio = Math.max(window.devicePixelRatio || 1, 1)
      canvas.width = canvas.offsetWidth * ratio
      canvas.height = canvas.offsetHeight * ratio
      canvas.getContext("2d").scale(ratio, ratio)
      signaturePad.value.clear()
    }
  })
}

// Cerrar el diálogo de firma
const closeSignatureModal = (accepted) => {
  isSignatureModalVisible.value = false
  if (accepted && signaturePad.value && !signaturePad.value.isEmpty()) {
    submitFinalSignature(signaturePad.value.toDataURL('image/png'))
  }
}

// Enviar la firma al backend
const submitFinalSignature = async (signatureImage) => {
  isRequestOngoing.value = true
  if (isSubmitting.value) return
  isSubmitting.value = true
  
  try {
    const payload = {
      signature: signatureImage,
    }
    const response = await axios.post(`/signatures/submit/${props.token}`, payload)

    finalState.value = {
      type: 'success',
      icon: 'mdi-check-circle-outline',
      title: 'Signering slutförd!',
      message: response.data.message,
      downloadUrl: response.data.download_url,
    }
  } catch (error) {
    finalState.value = {
      type: 'error',
      icon: 'mdi-alert-circle-outline',
      title: 'Ett fel uppstod',
      message: error.response?.data?.message || 'Kunde inte slutföra signeringen.',
    }
  } finally {
    isSubmitting.value = false
    isRequestOngoing.value = false
  }
}

// Descargar el PDF firmado
const downloadSignedPdf = () => {
  if (pdfSource.value) {
    const link = document.createElement('a')
    link.href = pdfSource.value
    link.download = `avtal-${signedInfo.value?.agreementId}-signerat.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }
}

onMounted(loadSignatureData);
</script>

<template>
  <VideoLoader />
  <LoadingOverlay :is-loading="isRequestOngoing" />
  <div class="signing-container">
    <!-- Header con logo -->
    <div class="signing-header">
      <img :src="logo" width="121" height="40" alt="Billogg" />
    </div>

    <!-- Estado de carga y final -->
    <div v-if="isLoading || finalState" class="signing-content">
    <VCard class="pa-8 text-center" max-width="500">
      <div v-if="finalState">
        <VIcon :icon="finalState.icon" :color="finalState.type" size="64" class="mb-4" />
        <VCardTitle class="text-h5">{{ finalState.title }}</VCardTitle>
        <VCardText>{{ finalState.message }}</VCardText>
        <VBtn 
          v-if="finalState.downloadUrl" 
          :href="finalState.downloadUrl" 
          target="_blank" 
          color="success" 
          class="mt-4" 
          prepend-icon="mdi-cloud-download-outline"
        >
          Ladda ner signerat avtal
        </VBtn>
      </div>
    </VCard>
    </div>

    <!-- Visor de PDF cuando ya está firmado -->
    <div v-if="!isLoading && !finalState && isAlreadySigned" class="signing-content">
      <VCard class="signing-card">
        <!-- Banner informativo de documento firmado -->
        <VAlert
          color="success"
          class="ma-4 mb-0 alert-no-shrink custom-alert"
        >
          <VAlertTitle class="text-h6">Dokumentet är redan signerat</VAlertTitle>
          <div>
            <p class="mb-2">Avtal #{{ signedInfo?.agreementId }} signerades {{ signedInfo?.signedAt }}</p>
            <VBtn
              color="success"
              variant="outlined"
              size="small"
              prepend-icon="mdi-download"
              class="mb-2"
              @click="downloadSignedPdf"
            >
              Ladda ner signerat dokument
            </VBtn>
          </div>
        </VAlert>
        
        <VDivider class="mt-4" />
        
        <!-- Visor del PDF firmado (solo lectura) -->
        <div class="pdf-container read-only">
          <div style="position: relative;">
            <vue-pdf-embed :source="pdfSource" />
          </div>
        </div>
      </VCard>
    </div>

    <!-- Visor de PDF para firma (cuando no está firmado) -->
    <div v-if="!isLoading && !finalState && !isAlreadySigned" class="signing-content">
      <VCard class="signing-card">
        <div class="pdf-container">
          <div ref="pdfContainer" style="position: relative;">
            <vue-pdf-embed :source="pdfSource" />
            
            <!-- Placeholder de firma clickeable (posición dinámica) -->
            <div 
              v-if="signaturePlacement.visible && !signaturePlacement.isStatic && computedPlacement.left !== null"
              class="signature-placeholder"
              :style="{
                left: computedPlacement.left + 'px', 
                top: computedPlacement.top + 'px'
              }"
              @click.stop="openSignatureModal"
            >
              <span class="signature-placeholder-content btn-light">
                <VIcon size="16" icon="custom-pencil" />
                <span>Signera här</span>
              </span>
            </div>
            
            <!-- Placeholder de firma estática -->
            <div 
              v-if="signaturePlacement.visible && signaturePlacement.isStatic"
              class="signature-placeholder static-signature-position"
              :class="{ 
                'align-left': signaturePlacement.alignment === 'left',
                'align-right': signaturePlacement.alignment === 'right'
              }"
              @click.stop="openSignatureModal"
            >
              <span class="signature-placeholder-content">
                <VIcon size="16" icon="custom-pencil" />
                <span>Signera här</span>
              </span>
            </div>
          </div>
        </div>
      </VCard>
    </div>

    <!-- Diálogo para firmar (el lienzo) -->
    <VDialog 
      v-model="isSignatureModalVisible"
      max-width="500"
      persistent
      class="signature-dialog"
    >
      <VCard>
        <VCardText class="without-padding v-card-custom-title">
          <VIcon icon="custom-signature" class="mr-4" size="32"></VIcon>
          Rita din signatur
        </VCardText>
        <VCardText class="without-padding">
          <div class="signature-pad-wrapper">
            <canvas ref="signatureCanvas"></canvas>
          </div>
        </VCardText>
        <VCardText class="d-flex justify-end gap-4 btn-box">
          <VBtn class="btn-light" @click="closeSignatureModal(false)">Avbryt</VBtn>
          <VBtn 
            class="btn-gradient" 
            @click="closeSignatureModal(true)" 
            :disabled="isSubmitting" 
            :loading="isSubmitting"
          >
            Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </div>
</template>

<style lang="scss">
  .signature-dialog {
    max-width: 500px;
    border-radius: 16px;
    gap: 32px;
    padding: 24px;

    .v-overlay__content {

      .v-dialog-close-btn {
        top: 16px !important;
        right: 24px;
        transform: none !important;
        height: 16px !important;
        width: 16px !important;
        padding: 0px !important;
      }

      .v-card {
        .v-card-text {
          padding: 24px 24px 24px !important;

          &.without-padding {
            padding: 24px 24px 0px !important;
          }

          &.v-card-custom-title {
            font-weight: 600;
            font-size: 24px;
            line-height: 100%;
            color: #5d5d5d;
          }

          @media (max-width: 991px) {
            &.btn-box {
              flex-direction: column-reverse;
              gap: 8px;
            }
          }
        }
      }
  }
  }
</style>
<style scoped>
  
.signing-container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: linear-gradient(90deg, #D8FFE4 0%, #C6FFEB 50%, #C0FEFF 100%);
  overflow-y: auto;
}

.signing-header {
  display: flex;
  align-items: center;
  justify-content: start;
  padding: 16px 24px;
  background: transparent;
}

.signing-content {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 0 24px 24px;
  margin-bottom: 0;
}

.signing-card {
  width: 100%;
  max-width: 1280px;
  display: flex;
  flex-direction: column;
  background: transparent;
  border-radius: 8px;
  box-shadow: none;
  overflow: visible;
}

.alert-no-shrink {
  flex-shrink: 0;
}

.pdf-container {
  position: relative;
  flex-grow: 1;
  overflow: visible;
  padding: 0;
  background-color: transparent;
}

.pdf-container.read-only {
  background-color: transparent;
}

/* Contenedor del vue-pdf-embed */
:deep(.pdf-container > div) {
  width: 100% !important;
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* Contenedor interno de vue-pdf-embed */
:deep(.pdf-container .vue-pdf-embed) {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  width: 100%;
}

:deep(.pdf-container .vue-pdf-embed > div) {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  width: 100%;
}

/* Cada página del PDF como una hoja separada con fondo blanco */
:deep(.pdf-container .vue-pdf-embed canvas),
:deep(.pdf-container .vue-pdf-embed img),
:deep(.pdf-container .vue-pdf-embed svg) {
  display: block !important;
  width: 100% !important;
  height: auto !important;
  background: #fff !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
  border-radius: 4px !important;
}

.signature-placeholder {
  position: absolute;
  z-index: 10;
  cursor: pointer;
  display: flex;
  justify-content: start;
}

.signature-placeholder-content {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: solid 1px #6e9383;
  background-color: transparent;
  border-radius: 56px;
  padding: 8px 16px;
  color: #6e9383;
  font-weight: 500;
  font-size: 15px;
  white-space: nowrap;
  cursor: pointer;
  transition: all 0.2s ease;
}

.signature-placeholder-content:hover {
  border-color: #416054;
  color: #416054;
  background-color: rgba(110, 147, 131, 0.1);
}

.signing-card-title {
  font-size: 1rem;
  font-weight: 500;
  color: #333;
}

/* Posicionamiento para la firma estática */
.static-signature-position.align-left {
  bottom: 12%;
  left: 25%;
  top: auto;
  transform: translate(-50%, -50%);
}

.static-signature-position.align-right {
  bottom: 15%;
  left: 75%;
  top: auto;
  transform: translate(-50%, -50%);
}

.signature-pad-wrapper {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 4px;
}

canvas {
  width: 100%;
  height: 200px;
  display: block;
}

/* Responsive */
@media (max-width: 768px) {
  .signing-container {
    padding: 0.5rem;
  }

  .signing-content {
    padding: 0 12px 12px;
  }

  .signing-card {
    max-height: none;
    max-width: 100%;
  }

  /* Asegurar que el PDF se escale correctamente en mobile */
  .pdf-container {
    width: 100%;
  }

  .signature-placeholder-content {
    padding: 0 3px;
    font-size: 6px;
    gap: 1px;
    border-radius: 16px;
  }
  
  .signature-placeholder-content .v-icon {
    font-size: 4px !important;
    width: 4px !important;
    height: 4px !important;
  }

  .static-signature-position.align-left {
    bottom: 10%;
  }

  .static-signature-position.align-right {
    bottom: 12%;
  }
}
</style>

<route lang="yaml">
  meta:
    layout: blank
    redirectIfLoggedIn: false
    public: true    # opcional, claro indicador de que es pública
</route>