<script setup>
import { usePayoutsStores } from '@/stores/usePayouts'
import { avatarText } from '@core/utils/formatters'
import { themeConfig } from '@themeConfig'

const props = defineProps({
  isDialogVisible: {
    type: Boolean,
    required: true,
  },
  payoutId: {
    type: [Number, String],
    default: null,
  },
})

const emit = defineEmits(['update:isDialogVisible', 'payoutUpdated'])

const payoutsStores = usePayoutsStores()
const payout = ref(null)
const isLoading = ref(false)
const isSaving = ref(false)
const selectedStatus = ref(null)

const statusOptions = [
  { title: 'Created', value: 'CREATED' },
  { title: 'Debited', value: 'DEBITED' },
  { title: 'Paid', value: 'PAID' },
  { title: 'Error', value: 'ERROR' },
  { title: 'Cancelled', value: 'CANCELLED' }
]

const statusColors = {
  'CREATED': 'info',
  'DEBITED': 'warning', 
  'PAID': 'success',
  'ERROR': 'error',
  'CANCELLED': 'secondary'
}

const dialogVisible = computed({
  get: () => props.isDialogVisible,
  set: (value) => emit('update:isDialogVisible', value),
})

const fetchPayout = async () => {
  if (!props.payoutId) return
  
  try {
    isLoading.value = true
    const data = await payoutsStores.showPayout(props.payoutId)
    payout.value = data
    selectedStatus.value = data.status
  } catch (error) {
    console.error('Error fetching payout:', error)
  } finally {
    isLoading.value = false
  }
}

const updateStatus = async () => {
  if (!payout.value || selectedStatus.value === payout.value.status) return
  
  try {
    isSaving.value = true
    const response = await payoutsStores.updatePayout(payout.value.id, {
      status: selectedStatus.value
    })
    
    if (response.data.success) {
      payout.value = response.data.data.payout
      // Emitir evento para refrescar la lista
      emit('payoutUpdated', payout.value)
    }
  } catch (error) {
    console.error('Error updating payout:', error)
    // Revertir el estado en caso de error
    selectedStatus.value = payout.value.status
  } finally {
    isSaving.value = false
  }
}

watch(() => props.isDialogVisible, (newVal) => {
  if (newVal && props.payoutId) {
    fetchPayout()
  }
})

const closeDialog = () => {
  dialogVisible.value = false
  payout.value = null
}
</script>

<template>
  <VDialog
    v-model="dialogVisible"
    max-width="1200px"
    scrollable
  >
    <VCard>
      <VCardTitle class="d-flex align-center justify-space-between">
        <span>Payout Detaljer #{{ payoutId }}</span>
        <VBtn
          icon
          size="small"
          variant="text"
          @click="closeDialog"
        >
          <VIcon icon="tabler-x" />
        </VBtn>
      </VCardTitle>

      <VDivider />

      <VCardText v-if="isLoading" class="text-center py-10">
        <VProgressCircular indeterminate color="primary" />
      </VCardText>

      <VCardText v-else-if="payout" class="pa-5" style="max-height: 70vh;">
        <VRow>
          <!-- Información General -->
          <VCol cols="12" md="6">
            <VCard variant="outlined">
              <VCardTitle class="text-subtitle-1 py-3">
                Allmän Information
              </VCardTitle>
              <VDivider />
              <VCardText>
                <VList density="compact">
                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Payout ID
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.id }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Swish ID
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.swish_id || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Reference
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.reference || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Belopp
                    </VListItemTitle>
                    <VListItemSubtitle class="text-h6 text-success">
                      {{ payout.amount }} SEK
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Valuta
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.currency || 'SEK' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Status
                    </VListItemTitle>
                    <VListItemSubtitle>
                      <VSelect
                        v-model="selectedStatus"
                        :items="statusOptions"
                        density="compact"
                        variant="outlined"
                        hide-details
                        :disabled="isSaving"
                        class="mt-2"
                        style="max-width: 200px;"
                      />
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem v-if="selectedStatus !== payout.status">
                    <VBtn
                      color="primary"
                      size="small"
                      :loading="isSaving"
                      @click="updateStatus"
                    >
                      Spara Status
                    </VBtn>
                  </VListItem>
                </VList>
              </VCardText>
            </VCard>
          </VCol>

          <!-- Información de Swish -->
          <VCol cols="12" md="6">
            <VCard variant="outlined">
              <VCardTitle class="text-subtitle-1 py-3">
                Swish Detaljer
              </VCardTitle>
              <VDivider />
              <VCardText>
                <VList density="compact">
                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Payer Alias
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.payer_alias || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Payee Alias
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.payee_alias || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Payee SSN
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.payee_ssn || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Payout Type
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.payout_type || 'PAYOUT' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Payout UUID
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2 text-truncate">
                      {{ payout.payout_instruction_uuid || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Instruction Date
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.instruction_date || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Callback URL
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2 text-truncate">
                      {{ payout.callback_url || 'N/A' }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem v-if="payout.location_url">
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Location URL
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2 text-truncate">
                      <a :href="payout.location_url" target="_blank" class="text-primary">
                        {{ payout.location_url }}
                      </a>
                    </VListItemSubtitle>
                  </VListItem>
                </VList>
              </VCardText>
            </VCard>
          </VCol>

          <!-- Información del Usuario Creador -->
          <VCol cols="12" md="6" v-if="payout.user">
            <VCard variant="outlined">
              <VCardTitle class="text-subtitle-1 py-3">
                Skapad Av
              </VCardTitle>
              <VDivider />
              <VCardText>
                <div class="d-flex align-center gap-4">
                  <VAvatar
                    :variant="payout.user.avatar ? 'outlined' : 'tonal'"
                    size="50"
                  >
                    <VImg
                      v-if="payout.user.avatar"
                      :src="themeConfig.settings.urlStorage + payout.user.avatar"
                    />
                    <span v-else>{{ avatarText(payout.user.name) }}</span>
                  </VAvatar>
                  <div>
                    <div class="font-weight-medium">
                      {{ payout.user.name }} {{ payout.user.last_name || '' }}
                    </div>
                    <div class="text-sm text-disabled">
                      {{ payout.user.email }}
                    </div>
                  </div>
                </div>
              </VCardText>
            </VCard>
          </VCol>

          <!-- Fechas -->
          <VCol cols="12" md="6">
            <VCard variant="outlined">
              <VCardTitle class="text-subtitle-1 py-3">
                Tidsstämplar
              </VCardTitle>
              <VDivider />
              <VCardText>
                <VList density="compact">
                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Skapad
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.created_at }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem>
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Uppdaterad
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.updated_at }}
                    </VListItemSubtitle>
                  </VListItem>
                </VList>
              </VCardText>
            </VCard>
          </VCol>

          <!-- Mensaje -->
          <VCol cols="12" v-if="payout.message">
            <VCard variant="outlined">
              <VCardTitle class="text-subtitle-1 py-3">
                Meddelande
              </VCardTitle>
              <VDivider />
              <VCardText class="text-body-2">
                {{ payout.message }}
              </VCardText>
            </VCard>
          </VCol>

          <!-- Error Message -->
          <VCol cols="12" v-if="payout.error_message">
            <VCard variant="outlined" color="error">
              <VCardTitle class="text-subtitle-1 py-3 text-error">
                <VIcon icon="tabler-alert-circle" class="me-2" />
                Fel Meddelande
                <VChip v-if="payout.error_code" size="small" color="error" class="ms-2">
                  {{ payout.error_code }}
                </VChip>
              </VCardTitle>
              <VDivider />
              <VCardText>
                <VAlert type="error" variant="tonal" density="compact">
                  {{ payout.error_message }}
                </VAlert>
              </VCardText>
            </VCard>
          </VCol>

          <!-- Request Payload JSON -->
          <VCol cols="12" md="6" v-if="payout.request_payload">
            <VCard variant="outlined">
              <VCardTitle class="text-subtitle-1 py-3">
                Request Payload
              </VCardTitle>
              <VDivider />
              <VCardText>
                <pre class="text-xs overflow-auto" style="max-height: 300px;">{{ JSON.stringify(payout.request_payload, null, 2) }}</pre>
              </VCardText>
            </VCard>
          </VCol>

          <!-- Response Data JSON -->
          <VCol cols="12" md="6" v-if="payout.response_data">
            <VCard variant="outlined">
              <VCardTitle class="text-subtitle-1 py-3">
                Response Data
              </VCardTitle>
              <VDivider />
              <VCardText>
                <pre class="text-xs overflow-auto" style="max-height: 300px;">{{ JSON.stringify(payout.response_data, null, 2) }}</pre>
              </VCardText>
            </VCard>
          </VCol>

          <!-- Signature Info -->
          <VCol cols="12" v-if="payout.signature || payout.signing_certificate_serial_number">
            <VCard variant="outlined">
              <VCardTitle class="text-subtitle-1 py-3">
                Signatur Information
              </VCardTitle>
              <VDivider />
              <VCardText>
                <VList density="compact">
                  <VListItem v-if="payout.signing_certificate_serial_number">
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Certificate Serial Number
                    </VListItemTitle>
                    <VListItemSubtitle class="text-body-2">
                      {{ payout.signing_certificate_serial_number }}
                    </VListItemSubtitle>
                  </VListItem>

                  <VListItem v-if="payout.signature">
                    <VListItemTitle class="font-weight-medium text-body-2">
                      Signature (Base64)
                    </VListItemTitle>
                    <VListItemSubtitle class="text-xs text-truncate">
                      {{ payout.signature }}
                    </VListItemSubtitle>
                  </VListItem>
                </VList>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>

      <VDivider />

      <VCardActions>
        <VSpacer />
        <VBtn
          color="secondary"
          variant="outlined"
          @click="closeDialog"
        >
          Stäng
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<style scoped>
pre {
  background-color: rgba(var(--v-theme-on-surface), 0.05);
  padding: 0.75rem;
  border-radius: 4px;
  overflow-x: auto;
  font-size: 0.75rem;
  line-height: 1.4;
}
</style>
