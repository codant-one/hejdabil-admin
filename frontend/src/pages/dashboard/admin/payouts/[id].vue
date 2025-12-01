<script setup>
import { usePayoutsStores } from '@/stores/usePayouts'
import { avatarText } from '@core/utils/formatters'
import { themeConfig } from '@themeConfig'

definePage({
  meta: {
    subject: 'payouts',
    action: 'read',
  },
})

const route = useRoute()
const router = useRouter()
const payoutsStores = usePayoutsStores()

const payout = ref(null)
const isLoading = ref(true)

const statusColors = {
  'CREATED': 'info',
  'DEBITED': 'warning', 
  'PAID': 'success',
  'ERROR': 'error',
  'CANCELLED': 'secondary'
}

const fetchPayout = async () => {
  try {
    isLoading.value = true
    const data = await payoutsStores.showPayout(route.params.id)
    payout.value = data
  } catch (error) {
    console.error('Error fetching payout:', error)
    router.push({ name: 'dashboard-admin-payouts' })
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchPayout()
})

const goBack = () => {
  router.push({ name: 'dashboard-admin-payouts' })
}
</script>

<template>
  <section>
    <VRow>
      <VCol cols="12">
        <VCard v-if="isLoading">
          <VCardText class="text-center py-10">
            <VProgressCircular indeterminate color="primary" />
          </VCardText>
        </VCard>

        <VCard v-else-if="payout">
          <VCardTitle class="d-flex align-center gap-2">
            <VBtn
              icon
              size="small"
              variant="text"
              @click="goBack"
            >
              <VIcon icon="tabler-arrow-left" />
            </VBtn>
            <span>Payout Detaljer #{{ payout.id }}</span>
          </VCardTitle>

          <VDivider />

          <VCardText>
            <VRow>
              <!-- Información General -->
              <VCol cols="12" md="6">
                <VCard variant="outlined">
                  <VCardTitle class="text-h6">
                    Allmän Information
                  </VCardTitle>
                  <VDivider />
                  <VCardText>
                    <VList>
                      <VListItem>
                        <VListItemTitle class="font-weight-medium">
                          Payout ID
                        </VListItemTitle>
                        <VListItemSubtitle>
                          {{ payout.id }}
                        </VListItemSubtitle>
                      </VListItem>

                      <VListItem>
                        <VListItemTitle class="font-weight-medium">
                          Swish ID
                        </VListItemTitle>
                        <VListItemSubtitle>
                          {{ payout.swish_id || 'N/A' }}
                        </VListItemSubtitle>
                      </VListItem>

                      <VListItem>
                        <VListItemTitle class="font-weight-medium">
                          Reference
                        </VListItemTitle>
                        <VListItemSubtitle>
                          {{ payout.reference || 'N/A' }}
                        </VListItemSubtitle>
                      </VListItem>

                      <VListItem>
                        <VListItemTitle class="font-weight-medium">
                          Belopp
                        </VListItemTitle>
                        <VListItemSubtitle class="text-h6 text-success">
                          {{ payout.amount }} SEK
                        </VListItemSubtitle>
                      </VListItem>

                      <VListItem>
                        <VListItemTitle class="font-weight-medium">
                          Valuta
                        </VListItemTitle>
                        <VListItemSubtitle>
                          {{ payout.currency || 'SEK' }}
                        </VListItemSubtitle>
                      </VListItem>

                      <VListItem>
                        <VListItemTitle class="font-weight-medium">
                          Status
                        </VListItemTitle>
                        <VListItemSubtitle>
                          <VChip
                            :color="statusColors[payout.status] || 'default'"
                            size="small"
                          >
                            {{ payout.status }}
                          </VChip>
                        </VListItemSubtitle>
                      </VListItem>
                    </VList>
                  </VCardText>
                </VCard>
              </VCol>

              <!-- Información de Swish -->
              <VCol cols="12" md="6">
                <VCard variant="outlined">
                  <VCardTitle class="text-h6">
                    Swish Detaljer
                  </VCardTitle>
                  <VDivider />
                  <VCardText>
                    <VList>
                      <VListItem>
                        <VListItemTitle class="font-weight-medium">
                          Payer Alias
                        </VListItemTitle>
                        <VListItemSubtitle>
                          {{ payout.payer_alias || 'N/A' }}
                        </VListItemSubtitle>
                      </VListItem>

                      <VListItem>
                        <VListItemTitle class="font-weight-medium">
                          Payee Alias
                        </VListItemTitle>
                        <VListItemSubtitle>
                          {{ payout.payee_alias || 'N/A' }}
                        </VListItemSubtitle>
                      </VListItem>

                      <VListItem>
                        <VListItemTitle class="font-weight-medium">
                          Payee SSN
                        </VListItemTitle>
                        <VListItemSubtitle>
                          {{ payout.payee_ssn || 'N/A' }}
                        </VListItemSubtitle>
                      </VListItem>

                      <VListItem>
                        <VListItemTitle class="font-weight-medium">
                          Payout Type
                        </VListItemTitle>
                        <VListItemSubtitle>
                          {{ payout.payout_type || 'PAYOUT' }}
                        </VListItemSubtitle>
                      </VListItem>

                      <VListItem>
                        <VListItemTitle class="font-weight-medium">
                          Instruction Date
                        </VListItemTitle>
                        <VListItemSubtitle>
                          {{ payout.instruction_date || 'N/A' }}
                        </VListItemSubtitle>
                      </VListItem>

                      <VListItem>
                        <VListItemTitle class="font-weight-medium">
                          Callback URL
                        </VListItemTitle>
                        <VListItemSubtitle class="text-truncate">
                          {{ payout.callback_url || 'N/A' }}
                        </VListItemSubtitle>
                      </VListItem>
                    </VList>
                  </VCardText>
                </VCard>
              </VCol>

              <!-- Información del Usuario Creador -->
              <VCol cols="12" md="6" v-if="payout.user">
                <VCard variant="outlined">
                  <VCardTitle class="text-h6">
                    Skapad Av
                  </VCardTitle>
                  <VDivider />
                  <VCardText>
                    <div class="d-flex align-center gap-4 mb-4">
                      <VAvatar
                        :variant="payout.user.avatar ? 'outlined' : 'tonal'"
                        size="60"
                      >
                        <VImg
                          v-if="payout.user.avatar"
                          :src="themeConfig.settings.urlStorage + payout.user.avatar"
                        />
                        <span v-else>{{ avatarText(payout.user.name) }}</span>
                      </VAvatar>
                      <div>
                        <div class="font-weight-medium text-h6">
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
                  <VCardTitle class="text-h6">
                    Tidsstämplar
                  </VCardTitle>
                  <VDivider />
                  <VCardText>
                    <VList>
                      <VListItem>
                        <VListItemTitle class="font-weight-medium">
                          Skapad
                        </VListItemTitle>
                        <VListItemSubtitle>
                          {{ payout.created_at }}
                        </VListItemSubtitle>
                      </VListItem>

                      <VListItem>
                        <VListItemTitle class="font-weight-medium">
                          Uppdaterad
                        </VListItemTitle>
                        <VListItemSubtitle>
                          {{ payout.updated_at }}
                        </VListItemSubtitle>
                      </VListItem>
                    </VList>
                  </VCardText>
                </VCard>
              </VCol>

              <!-- Mensaje y Payload -->
              <VCol cols="12" v-if="payout.message">
                <VCard variant="outlined">
                  <VCardTitle class="text-h6">
                    Meddelande
                  </VCardTitle>
                  <VDivider />
                  <VCardText>
                    {{ payout.message }}
                  </VCardText>
                </VCard>
              </VCol>

              <!-- Payload Completo (JSON) -->
              <VCol cols="12" v-if="payout.payload">
                <VCard variant="outlined">
                  <VCardTitle class="text-h6">
                    Payload (JSON)
                  </VCardTitle>
                  <VDivider />
                  <VCardText>
                    <pre class="text-sm">{{ JSON.stringify(JSON.parse(payout.payload), null, 2) }}</pre>
                  </VCardText>
                </VCard>
              </VCol>

              <!-- Error Message si existe -->
              <VCol cols="12" v-if="payout.error_message">
                <VCard variant="outlined" color="error">
                  <VCardTitle class="text-h6">
                    Fel Meddelande
                  </VCardTitle>
                  <VDivider />
                  <VCardText>
                    <VAlert type="error" variant="tonal">
                      {{ payout.error_message }}
                    </VAlert>
                  </VCardText>
                </VCard>
              </VCol>
            </VRow>
          </VCardText>

          <VDivider />

          <VCardActions>
            <VBtn
              color="secondary"
              variant="outlined"
              @click="goBack"
            >
              <VIcon icon="tabler-arrow-left" start />
              Tillbaka
            </VBtn>
            <VSpacer />
            <!-- Aquí puedes agregar más acciones si es necesario -->
          </VCardActions>
        </VCard>
      </VCol>
    </VRow>
  </section>
</template>

<style scoped>
pre {
  background-color: rgba(var(--v-theme-on-surface), 0.05);
  padding: 1rem;
  border-radius: 4px;
  overflow-x: auto;
}
</style>
