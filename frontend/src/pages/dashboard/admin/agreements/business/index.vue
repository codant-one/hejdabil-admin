<script setup>

import { ref, watchEffect, inject, computed } from 'vue'
import { useRouter } from 'vue-router'
import { requiredValidator, emailValidator, phoneValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useAuthStores } from '@/stores/useAuth'
import { useAgreementsStores } from '@/stores/useAgreements'
import { useCarInfoStores } from '@/stores/useCarInfo'
import { useCompanyInfoStores } from '@/stores/useCompanyInfo'
import { usePersonInfoStores } from '@/stores/usePersonInfo'
import { useToastsStores } from '@/stores/useToasts'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

const router = useRouter()
const emitter = inject("emitter")

const authStores = useAuthStores()
const carInfoStores = useCarInfoStores()
const agreementsStores = useAgreementsStores()
const companyInfoStores = useCompanyInfoStores()
const personInfoStores = usePersonInfoStores()
const toastsStores = useToastsStores()
const ability = useAppAbility()

const isRequestOngoing = ref(false)
const refForm = ref()
const currentTab = ref(0)

const userData = ref(null)
const role = ref(null)
const currencies = ref([])
const currency_id = ref(1)

const brands = ref([])
const models = ref([])
const brand_id = ref(null)
const model_id = ref(null)
const model = ref(null)
const modelsByBrand = ref([])
const offer_id = ref(null)
const reg_num = ref(null)
const mileage = ref(null)
const price = ref(null)
const comment = ref(null)
const terms_other_conditions = ref(null)

// Kund
const clients = ref([])
const client_types = ref([])
const identifications = ref([])
const client_id = ref(null)
const client_type_id = ref(null)
const identification_id = ref(null)
const fullname = ref(null)
const email = ref(null)
const organization_number = ref(null)
const address = ref(null)
const street = ref(null)
const postal_code = ref(null)
const phone = ref(null)
const disabled_client = ref(false)

watchEffect(async () => {
    isRequestOngoing.value = true

    await agreementsStores.info()

    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    role.value = userData.value.roles[0].name

    const { user_data, userAbilities } = await authStores.me(userData.value)

    localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

    ability.update(userAbilities)

    localStorage.setItem('user_data', JSON.stringify(user_data))

    if(role.value === 'Supplier') {
      offer_id.value = user_data.supplier.user.offers.length + 1
    } else if(role.value === 'User') {
      offer_id.value = user_data.supplier.boss.user.offers.length + 1
    } else {
      offer_id.value = agreementsStores.offer_id + 1
    }

    brands.value = agreementsStores.brands
    models.value = agreementsStores.models 
    currencies.value = agreementsStores.currencies

    clients.value = agreementsStores.clients
    client_types.value = agreementsStores.client_types
    identifications.value = agreementsStores.identifications

    isRequestOngoing.value = false
   
})

  const formatOrgNumber = () => {
    if (!organization_number.value) return

    let numbers = organization_number.value.replace(/\D/g, '')
    if (numbers.length > 4) {
      numbers = numbers.slice(0, -4) + '-' + numbers.slice(-4)
    }
    organization_number.value = numbers
  }

  const isCompanyNumber = value => {
    const cleanNumber = (value ?? '').toString().replace(/[\s\-]/g, '')
    return cleanNumber.startsWith('5')
  }

  const isEntitySearchLoading = computed(() => {
    return companyInfoStores.loading || personInfoStores.loading
  })

  const searchEntity = async () => {
    if (!organization_number.value) return

    if (isCompanyNumber(organization_number.value)) {
      await searchCompany()
    } else {
      await searchPerson()
    }
  }

  const searchCompany = async () => {
    if (!organization_number.value) return

    try {
      const response = await companyInfoStores.getCompanyInfo(organization_number.value)

      if (response) {
        const foretagType = client_types.value.find(t => t.name === 'Företag')
        if (foretagType) client_type_id.value = foretagType.id

        fullname.value = response.organisationsnamn?.organisationsnamnLista?.[0]?.namn || ''
        postal_code.value = response.postadressOrganisation?.postadress?.postnummer || ''
        address.value = response.postadressOrganisation?.postadress?.utdelningsadress || ''
        street.value = response.postadressOrganisation?.postadress?.postort || ''
      }
    } catch (error) {
      toastsStores.addToast({
        message: 'Ingen företag hittades med det registreringsnumret',
        type: 'error',
      })
    }
  }

  const searchPerson = async () => {
    try {
      const response = await personInfoStores.getPersonInfo(organization_number.value)

      if (response?.success && response?.data) {
        const privatType = client_types.value.find(t => t.name === 'Privat')
        if (privatType) client_type_id.value = privatType.id

        fullname.value = response.data.fullname || ''
        postal_code.value = response.data.postnummer || ''
        address.value = response.data.adress || ''
        street.value = response.data.postort || ''
      }
    } catch (error) {
      const errorMessage = error?.response?.data?.message || 'Ingen person hittades med det personnumret'
      toastsStores.addToast({
        message: errorMessage,
        type: 'error',
      })
    }
  }

  const clearClient = () => {
    fullname.value = null
    email.value = null
    organization_number.value = null
    address.value = null
    street.value = null
    postal_code.value = null
    phone.value = null
    client_type_id.value = null
    identification_id.value = null
    disabled_client.value = false
  }

  const selectClient = client => {
    if (!client) return

    const selected = clients.value.find(item => item.id === client)
    if (!selected) return

    fullname.value = selected.fullname
    email.value = selected.email
    organization_number.value = selected.organization_number
    address.value = selected.address
    street.value = selected.street
    postal_code.value = selected.postal_code
    phone.value = selected.phone

    // Si el cliente seleccionado tiene tipo/identificación, asigna si existen
    client_type_id.value = selected.client_type_id ?? client_type_id.value
    identification_id.value = selected.identification_id ?? identification_id.value

    disabled_client.value = true
  }

const selectBrand = brand => {
    if (brand) {
        let _brand = brands.value.find(item => item.id === brand)
    
        model_id.value = ''
        modelsByBrand.value = models.value.filter(item => item.brand_id === _brand.id)
    }
}

const onClearBrand = () => {
    modelsByBrand.value = []
}

const getModels = computed(() => {
    const models = modelsByBrand.value.map((model) => ({
        title: model.name,
        value: model.id
    }))

    if (modelsByBrand.value.length > 0) {
        models.push({ title: 'En annan..', value: 0 })
    }

    return models
})

const selectModel = selected => {

  model.value = selected !== 0 ? null : model.value

}

const searchVehicule = async () => {

  isRequestOngoing.value = true

  const carRes = await carInfoStores.getLicensePlate(reg_num.value)

  isRequestOngoing.value = false
}

const onSubmit = () => {
  refForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      let formData = new FormData()

      //vehicle
      formData.append('reg_num', reg_num.value)
      formData.append('brand_id', brand_id.value)
      formData.append('model_id', model_id.value)
      formData.append('model', model.value)
      formData.append('offerId', offer_id.value)
      formData.append('mileage', mileage.value)
      formData.append('comment', comment.value)
      formData.append('price', price.value)
      formData.append('terms_other_conditions', terms_other_conditions.value)

      //kund (agreement_client)
      formData.append('client_id', client_id.value)
      formData.append('client_type_id', client_type_id.value)
      formData.append('identification_id', identification_id.value)
      formData.append('fullname', fullname.value)
      formData.append('email', email.value)
      formData.append('organization_number', organization_number.value)
      formData.append('address', address.value)
      formData.append('street', street.value)
      formData.append('postal_code', postal_code.value)
      formData.append('phone', phone.value)

      //agreement
      formData.append('agreement_type_id', 4)
      formData.append('currency_id', currency_id.value)
      formData.append('price', price.value)
      formData.append('residual_debt', 0)
      formData.append('guaranty', 0)
      formData.append('insurance_company', 0)

      isRequestOngoing.value = true

      agreementsStores.addAgreement(formData)
          .then((res) => {
              if (res.data.success) {
                  
                  let data = {
                      message: 'Erbjudande framgångsrikt skapat',
                      error: false
                  }

                  router.push({ name : 'dashboard-admin-agreements'})
                  emitter.emit('toast', data)
              }
              isRequestOngoing.value = false
          })
          .catch((err) => {
              
              let data = {
                  message: err.message,
                  error: true
              }

              router.push({ name : 'dashboard-admin-agreements'})
              emitter.emit('toast', data)

              isRequestOngoing.value = false
          })
    }
  })
}
</script>

<template>
  <section>
    <LoadingOverlay :is-loading="isRequestOngoing" />
    <VForm ref="refForm" @submit.prevent="onSubmit">
      <VRow>
        <VCol cols="12" class="py-0">
          <div class="d-flex flex-wrap justify-start justify-sm-space-between gap-y-4 gap-x-6">
            <div class="d-flex flex-column justify-center">
              <h6 class="text-md-h4 text-h6 font-weight-medium">
                Prisförslag
              </h6>
            </div>
            <VSpacer />
            <div class="d-flex flex-column flex-md-row gap-1 gap-md-4 w-100 w-md-auto">
              <VBtn
                variant="tonal"
                color="secondary"
                class="mb-2 w-100 w-md-auto"
                :to="{ name: 'dashboard-admin-agreements' }"
              >
                Tillbaka
              </VBtn>
              <VBtn
                    type="submit"
                    color="primary"
                    class="w-100 w-md-auto"
                    :loading="isRequestOngoing"
                  >
                   Skapa
                  </VBtn>
            </div>
          </div>
        </VCol>
        <VCol cols="12">
            <VCard flat class="px-2 px-md-12">
              <VCardText class="px-2 pt-0 pt-md-5">            
                <VTabs v-model="currentTab" grow>
                  <VTab>Erbjudande</VTab>
                  <VTab>Kund</VTab>
                </VTabs>
                <VCardText class="px-0 px-md-2">
                  <VWindow v-model="currentTab">
                    <VWindowItem class="px-md-5">
                      <h6 class="text-md-h4 text-h5 font-weight-medium my-5">
                        Detaljer om erbjudandet
                      </h6>
                      <VRow>
                        <VCol cols="12" md="6" class="d-none d-md-block"></VCol>
                        <VCol cols="10" md="3">
                          <VTextField
                            v-model="reg_num"
                            label="Regnr"
                            :rules="[requiredValidator]"
                          />
                        </VCol>
                        <VCol cols="2" md="1" class="px-0">
                          <VBtn
                            icon="tabler-search"
                            variant="tonal"
                            color="primary"
                            size="x-small"
                            @click="searchVehicule"
                          />
                        </VCol>
                        <VCol cols="12" md="2">
                          <VTextField
                            v-model="offer_id"
                            label="Offertnummer"
                            readonly
                            disabled
                            prefix="#"
                            density="compact"
                          />
                        </VCol>
                        <VCol cols="12" md="6">
                          <VAutocomplete
                            v-model="brand_id"
                            label="Märke"
                            :items="brands"
                            :item-title="item => item.name"
                            :item-value="item => item.id"
                            autocomplete="off"
                            clearable
                            clear-icon="tabler-x"
                            :rules="[requiredValidator]"
                            @update:modelValue="selectBrand"
                            @click:clear="onClearBrand"
                            :menu-props="{ maxHeight: '300px' }"
                          />
                        </VCol>
                        <VCol cols="12" :md="model_id !== 0 ? 6 : 3">
                          <VAutocomplete
                            v-model="model_id"
                            label="Modell"
                            :items="getModels"
                            autocomplete="off"
                            clearable
                            clear-icon="tabler-x"
                            :rules="[requiredValidator]"
                            @update:modelValue="selectModel"
                            :menu-props="{ maxHeight: '300px' }"
                          />
                        </VCol>
                        <VCol cols="12" md="3" v-if="model_id === 0">
                          <VTextField
                            v-model="model"
                            label="Modellens namn"
                          />
                        </VCol>
                        <VCol cols="12" md="6">
                          <VTextField
                            v-model="mileage"
                            label="Mätarställning"
                            :rules="[requiredValidator]"
                          />
                        </VCol>
                        <VCol cols="12" md="6">
                          <VTextField
                            v-model="price"
                            :label="'Belopp ' + (currencies.find(item => item.id === currency_id)?.code || '')"
                            type="number"
                            min="0"
                            :rules="[requiredValidator]"
                          />
                        </VCol>
                        <VCol cols="12" md="12">
                          <VTextarea
                            v-model="comment"
                            label="Anmärkning"
                            rows="3"
                          />
                        </VCol>
                        <VCol cols="12" md="12">
                          <VTextarea
                            v-model="terms_other_conditions"
                            label="Villkor och bestämmelser"
                            rows="5"
                          />
                        </VCol>
                      </VRow>
                    </VWindowItem>

                    <VWindowItem class="px-md-5">
                      <h6 class="text-md-h4 text-h5 font-weight-medium my-5">
                        Kund
                      </h6>
                      <VRow>
                        <VCol cols="12" md="6">
                          <VAutocomplete
                            v-model="client_id"
                            label="Kunder"
                            :items="clients"
                            :item-title="item => item.fullname"
                            :item-value="item => item.id"
                            autocomplete="off"
                            clearable
                            @click:clear="clearClient"
                            @update:modelValue="selectClient"
                          />
                        </VCol>

                        <VCol cols="10" md="5">
                          <VTextField
                            v-model="organization_number"
                            label="Org/personummer"
                            :rules="[minLengthDigitsValidator(10)]"
                            minLength="11"
                            maxlength="13"
                            :disabled="disabled_client"
                            @input="formatOrgNumber()"
                          />
                        </VCol>
                        <VCol cols="2" md="1" class="px-0 d-flex align-start">
                          <VBtn
                            icon="tabler-search"
                            variant="tonal"
                            color="primary"
                            size="x-small"
                            class="mt-1"
                            @click="searchEntity"
                            :loading="isEntitySearchLoading"
                            :disabled="disabled_client"
                          />
                        </VCol>

                        <VCol cols="12" md="6">
                          <VAutocomplete
                            v-model="client_type_id"
                            label="Kunden är"
                            :items="client_types"
                            :item-title="item => item.name"
                            :item-value="item => item.id"
                            autocomplete="off"
                            :disabled="disabled_client"
                          />
                        </VCol>
                        <VCol cols="12" md="6">
                          <VTextField
                            v-model="fullname"
                            label="Namn"
                            :rules="[requiredValidator]"
                            :disabled="disabled_client"
                          />
                        </VCol>
                        <VCol cols="12" md="12">
                          <VTextField
                            v-model="address"
                            label="Adress"
                            :disabled="disabled_client"
                          />
                        </VCol>
                        <VCol cols="12" md="6">
                          <VTextField
                            v-model="postal_code"
                            label="Postnummer"
                            :disabled="disabled_client"
                          />
                        </VCol>
                        <VCol cols="12" md="6">
                          <VTextField
                            v-model="street"
                            label="Stad"
                            :disabled="disabled_client"
                          />
                        </VCol>
                        <VCol cols="12" md="6">
                          <VTextField
                            v-model="phone"
                            label="Telefon"
                            :rules="phone ? [phoneValidator] : []"
                            :disabled="disabled_client"
                          />
                        </VCol>
                        <VCol cols="12" md="6">
                          <VAutocomplete
                            v-model="identification_id"
                            label="Legitimation"
                            :items="identifications"
                            :item-title="item => item.name"
                            :item-value="item => item.id"
                            autocomplete="off"
                            :disabled="disabled_client"
                          />
                        </VCol>
                        <VCol cols="12" md="12">
                          <VTextField
                            v-model="email"
                            label="E-post"
                            :rules="[requiredValidator, emailValidator]"
                            :disabled="disabled_client"
                          />
                        </VCol>
                      </VRow>
                    </VWindowItem>
                  </VWindow>
                </VCardText>
              </VCardText>
            </VCard>          
        </VCol>
      </VRow>
    </VForm>
  </section>
</template>

<style scoped>
  .justify-content-end {
    justify-content: end !important;
  }
</style>

<route lang="yaml">
  meta:
    action: create
    subject: agreements
</route>