<script setup>

import { useRoute } from 'vue-router'
import { useSignableDocumentsStores } from '@/stores/useSignableDocuments'
import { avatarText } from '@/@core/utils/formatters'
import pdfIcon from '@images/icon-pdf-documento.png'
import VuePdfEmbed from 'vue-pdf-embed'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

const route = useRoute()
const documentsStores = useSignableDocumentsStores()

const isLoading = ref(true)
const error = ref('')
const documentData = ref(null)
const isPreviewDialogVisible = ref(false)
const isLoadingPreview = ref(false)
const previewPdfSource = ref(null)
const previewError = ref('')

const events = computed(() => {
  if (!documentData.value) return []

  const items = []

  // Created event
  items.push({
    key: 'created',
    title: 'Dokument skapat',
    meta: new Date(documentData.value.created_at).toLocaleString('sv-SE', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }),
    text: documentData.value.title,
    color: 'primary',
  })

  // Use most recent token for status markers
  const latestToken = (documentData.value.tokens || []).length
    ? [...documentData.value.tokens].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0]
    : null

  if (latestToken) {
    // Sent event
    items.push({
      key: 'sent',
      title: 'Signeringsförfrågan skickad',
      meta: new Date(latestToken.created_at).toLocaleString('sv-SE', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }),
      text: `Skickad till ${latestToken.recipient_email}`,
      color: 'success',
    })

    // Viewed event (first time the client opened the link)
    if (latestToken.viewed_at) {
      items.push({
        key: 'viewed',
        title: 'Dokument visat av kunden',
        meta: new Date(latestToken.viewed_at).toLocaleString('sv-SE', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }),
        text: 'Kunden har öppnat signeringslänken.',
        color: 'primary',
      })
    }

    // Signed event
    if (latestToken.signature_status === 'signed' && latestToken.signed_at) {
      items.push({
        key: 'signed',
        title: 'Dokument signerat',
        meta: new Date(latestToken.signed_at).toLocaleString('sv-SE', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false }),
        text: 'Signeringen är slutförd.',
        color: 'info',
      })
    } else if (latestToken.signature_status === 'sent') {
      items.push({
        key: 'pending',
        title: 'Väntar på signering',
        meta: 'Aktiv begäran',
        text: 'Mottagaren har inte signerat ännu.',
        color: 'warning',
      })
    }
  }

  return items
})

const openPreview = async () => {
  if (!documentData.value) return
  isPreviewDialogVisible.value = true
  isLoadingPreview.value = true
  previewError.value = ''
  try {
    const response = await documentsStores.getAdminPreviewPdf(documentData.value.id)
    previewPdfSource.value = URL.createObjectURL(response.data)
  } catch (e) {
    previewError.value = 'Kunde inte ladda PDF-förhandsvisning.'
  } finally {
    isLoadingPreview.value = false
  }
}

watch(isPreviewDialogVisible, val => {
  if (!val && previewPdfSource.value) {
    URL.revokeObjectURL(previewPdfSource.value)
    previewPdfSource.value = null
  }
})

onUnmounted(() => {
  if (previewPdfSource.value) URL.revokeObjectURL(previewPdfSource.value)
})

onMounted(async () => {
  try {
    isLoading.value = true
    const id = route.params.id
    const doc = await documentsStores.showDocument(id)
    documentData.value = doc
  } catch (e) {
    error.value = 'Kunde inte hämta dokumentets information.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <section>
    <VRow>
      <VCol cols="12">
        <VCard :title="documentData ? `Spårare: ${documentData.title}` : 'Spårare'">
          <VCardText>
            <VAlert v-if="error" type="error" class="mb-4">{{ error }}</VAlert>

            <div v-if="isLoading">
              <LoadingOverlay :is-loading="isLoading" />
            </div>

            <template v-else>
              <div class="d-flex align-center justify-space-between flex-wrap mb-6">
                <div class="d-flex align-center gap-3">
                  <VAvatar size="36" :variant="documentData?.user?.avatar ? 'outlined' : 'tonal'">
                    <VImg v-if="documentData?.user?.avatar" :src="documentData?.user?.avatar" style="border-radius: 50%;" />
                    <span v-else>{{ avatarText(documentData?.user?.name || 'U') }}</span>
                  </VAvatar>
                  <div class="d-flex flex-column">
                    <span class="font-weight-medium">{{ documentData?.user?.name }} {{ documentData?.user?.last_name ?? '' }}</span>
                    <span class="text-sm text-disabled">{{ documentData?.user?.email }}</span>
                  </div>
                </div>
                <div class="d-inline-flex align-center timeline-chip pointer" @click="openPreview" title="Förhandsvisa PDF">
                  <img :src="pdfIcon" height="20" class="me-2" alt="pdf">
                  <span class="app-timeline-text font-weight-medium">{{ documentData?.file?.split('/').pop() }}</span>
                </div>
              </div>

              <VTimeline side="end" align="start" line-inset="8" truncate-line="start" density="compact">
                <VTimelineItem v-for="item in events" :key="item.key" size="x-small" :dot-color="item.color">
                  <div class="d-flex justify-space-between align-center gap-2 flex-wrap mb-2">
                    <span class="app-timeline-title">{{ item.title }}</span>
                    <span class="app-timeline-meta">{{ item.meta }}</span>
                  </div>
                  <div class="app-timeline-text mt-1">{{ item.text }}</div>
                  <div v-if="item.key==='created' && documentData?.file" class="d-inline-flex align-center timeline-chip mt-2 pointer" @click="openPreview" title="Förhandsvisa PDF">
                    <img :src="pdfIcon" height="20" class="me-2" alt="pdf">
                    <span class="app-timeline-text font-weight-medium">{{ documentData?.file?.split('/').pop() }}</span>
                  </div>
                  <div v-if="item.key==='signed' && documentData?.tokens?.length && documentData.tokens[0]?.signed_pdf_path" class="d-inline-flex align-center timeline-chip mt-2 pointer" @click="openPreview" title="Visa signerad PDF">
                    <img :src="pdfIcon" height="20" class="me-2" alt="signed-pdf">
                    <span class="app-timeline-text font-weight-medium">Signerad PDF</span>
                  </div>
                </VTimelineItem>
              </VTimeline>
            </template>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- PDF Preview Dialog -->
    <VDialog v-model="isPreviewDialogVisible" max-width="900">
      <VCard>
        <VCardTitle class="d-flex justify-space-between align-center">
          <span>Förhandsvisa dokument</span>
          <VBtn icon variant="text" @click="isPreviewDialogVisible = false">
            <VIcon icon="tabler-x" />
          </VBtn>
        </VCardTitle>
        <VDivider />
        <VCardText class="d-flex justify-center" style="min-height:400px;">
          <VProgressCircular v-if="isLoadingPreview" indeterminate color="primary" />
          <div v-else class="w-100">
            <VAlert v-if="previewError" type="error" class="mb-4">{{ previewError }}</VAlert>
            <vue-pdf-embed v-if="previewPdfSource && !previewError" :source="previewPdfSource" style="width:100%;" />
            <VAlert v-else-if="!previewError" type="warning">Ingen PDF tillgänglig.</VAlert>
          </div>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
  
</template>

<route lang="yaml">
  meta:
    action: view
    subject: signed-documents
</route>

<style scoped>
.timeline-chip {
  border-radius: 6px;
  padding: 2px 8px;
  background: rgba(var(--v-theme-on-surface), 0.04);
}
.pointer { cursor: pointer; }
</style>