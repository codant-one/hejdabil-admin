<script setup>

import { nextTick } from 'vue'
import { useDisplay } from 'vuetify'
import { requiredValidator, phoneValidator, urlValidator, minLengthDigitsValidator, emailValidator } from '@/@core/utils/validators'
import { scrollElementIntoScrollableParent } from '@/@core/composable/useMobilePaginationScroll'
import { useProfileStores } from '@/stores/useProfile'
import { useAuthStores } from '@/stores/useAuth'
import { useConfigsStores } from '@/stores/useConfigs'
import { themeConfig } from '@themeConfig'
import { Cropper } from 'vue-advanced-cropper'
import LoadingOverlay from '@/components/common/LoadingOverlay.vue'
import modalWarningIcon from '@/assets/images/icons/alerts/modal-warning-icon.svg'
import SignaturePad from 'signature_pad'
import 'vue-advanced-cropper/dist/style.css'

const { width: windowWidth } = useWindowSize()
const { mdAndDown } = useDisplay()

const sectionEl = ref(null)
const refVForm = ref()
const cropper = ref()
const cropperSignature = ref()
const signaturePadCanvas = ref(null)
const signaturePadInstance = ref(null)

const authStores = useAuthStores()
const configsStores = useConfigsStores()
const profileStores = useProfileStores()

const isRequestOngoing = ref(true)
const isConfirmChangeLogoVisible = ref(false)
const isConfirmChangeSignatureVisible = ref(false)
const isSignaturePadDialogVisible = ref(false)
const isFormEdited = ref(false)
const dialog = ref(false)

const data = ref(null)
const userData = ref(null)
const role = ref('')
const logo = ref(null)
const logoCropped = ref(null)
const logoOld = ref(null)
const filename = ref([])
const signature = ref(null)
const signatureCropped = ref(null)
const signatureOld = ref(null)
const signatureFilename = ref([])
const isHydratingForm = ref(false)
const initialFormSnapshot = ref('')

let nextRoute = null

const advisor = ref({
  message: '',
  show: false,
  type: '',
})

const form = ref({
  company: '',
  email: '',
  name: '',
  last_name: '',
  organization_number: '',
  address: '',
  street: '',
  postal_code: '',
  phone: '',
  link: '',
  bank: '',
  iban: '',
  account_number: '',
  iban_number: '',
  bic: '',
  plus_spin: '',
  swish: '',
  vat: '',
  payout_number: '',
})

const snackbarLocation = computed(() => windowWidth.value < 1024 ? '' : 'top end')

const isAdminRole = computed(() => role.value === 'SuperAdmin' || role.value === 'Administrator')

const companyDetail = computed(() => {
  if (role.value === 'User')
    return userData.value?.supplier?.boss?.user?.user_detail ?? null

  return userData.value?.user_detail ?? null
})

const companyPayoutNumber = computed(() => {
  if (role.value === 'User')
    return userData.value?.supplier?.boss?.payout_number ?? ''

  return userData.value?.supplier?.payout_number ?? ''
})

const scrollToSectionTop = async () => {
  if (!mdAndDown.value || !sectionEl.value)
    return

  await nextTick()
  scrollElementIntoScrollableParent({
    element: sectionEl.value,
    offset: 16,
    behavior: 'smooth',
  })
}

const setAdvisor = (type, message) => {
  advisor.value.type = type
  advisor.value.message = message
  advisor.value.show = true
}

const clearAdvisorLater = delay => {
  setTimeout(() => {
    advisor.value.show = false
    advisor.value.message = ''
  }, delay)
}

const getFormSnapshot = () => JSON.stringify({
  ...form.value,
})

const syncInitialFormSnapshot = () => {
  initialFormSnapshot.value = getFormSnapshot()
  isFormEdited.value = false
}

const fetchImageAsBlob = async url => {
  try {
    const response = await fetch(`${themeConfig.settings.urlbase}proxy-image?url=${url}`)
    const blob = await response.blob()

    return URL.createObjectURL(blob)
  } catch {
    return url
  }
}

const applyCompanyForm = detail => {
  form.value.company = detail?.company ?? ''
  form.value.email = detail?.email ?? ''
  form.value.name = detail?.name ?? ''
  form.value.last_name = detail?.last_name ?? ''
  form.value.organization_number = detail?.organization_number ?? ''
  form.value.link = detail?.link ?? ''
  form.value.address = detail?.address ?? ''
  form.value.street = detail?.street ?? ''
  form.value.postal_code = detail?.postal_code ?? ''
  form.value.phone = detail?.phone ?? ''
  form.value.bank = detail?.bank ?? ''
  form.value.account_number = detail?.account_number ?? ''
  form.value.iban = detail?.iban ?? ''
  form.value.iban_number = detail?.iban_number ?? ''
  form.value.bic = detail?.bic ?? ''
  form.value.plus_spin = detail?.plus_spin ?? ''
  form.value.swish = detail?.swish ?? ''
  form.value.vat = detail?.vat ?? ''
}

const applyCompanyMedia = async ({ logoPath = null, signaturePath = null }) => {
  logo.value = logoPath
  signature.value = signaturePath
  logoCropped.value = logoPath ? await fetchImageAsBlob(logoPath) : null
  signatureCropped.value = signaturePath ? await fetchImageAsBlob(signaturePath) : null
}

async function fetchData() {
  isRequestOngoing.value = true
  isHydratingForm.value = true

  try {
    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    role.value = userData.value?.roles?.[0]?.name ?? ''

    if (isAdminRole.value) {
      await Promise.all([
        configsStores.getFeature('company'),
        configsStores.getFeature('logo'),
        configsStores.getFeature('signature'),
      ])

      const detail = configsStores.getFeaturedConfig('company') ?? {}
      const logoConfig = configsStores.getFeaturedConfig('logo') ?? {}
      const signatureConfig = configsStores.getFeaturedConfig('signature') ?? {}

      data.value = detail
      applyCompanyForm(detail)
      form.value.payout_number = ''

      await applyCompanyMedia({
        logoPath: logoConfig?.logo ? `${themeConfig.settings.urlStorage}${logoConfig.logo}` : null,
        signaturePath: signatureConfig?.img_signature ? `${themeConfig.settings.urlStorage}${signatureConfig.img_signature}` : null,
      })
    } else {
      data.value = await authStores.company()

      const detail = companyDetail.value

      applyCompanyForm(detail)
      form.value.payout_number = companyPayoutNumber.value

      await applyCompanyMedia({
        logoPath: detail?.logo ? `${themeConfig.settings.urlStorage}${detail.logo}` : null,
        signaturePath: detail?.img_signature ? `${themeConfig.settings.urlStorage}${detail.img_signature}` : null,
      })
    }

    syncInitialFormSnapshot()
  } catch {
    setAdvisor('error', 'Ett serverfel uppstod. Försök igen.')
    clearAdvisorLater(5000)
  } finally {
    isHydratingForm.value = false
    isRequestOngoing.value = false
  }
}

onBeforeRouteLeave((to, from, next) => {
  if (isFormEdited.value) {
    dialog.value = true
    nextRoute = next

    return
  }

  next()
})

watch(form, () => {
  if (isHydratingForm.value)
    return

  isFormEdited.value = getFormSnapshot() !== initialFormSnapshot.value
}, { deep: true })

const resetAvatar = () => {
  logoCropped.value = null
  logoOld.value = null
}

const resetSignature = () => {
  signatureCropped.value = null
  signatureOld.value = null
}

const resizeImage = (file, maxWidth, maxHeight, quality) => new Promise((resolve, reject) => {
  const image = new Image()

  image.src = URL.createObjectURL(file)
  image.onload = () => {
    const canvas = document.createElement('canvas')
    const context = canvas.getContext('2d')

    let width = image.width
    let height = image.height

    if (maxWidth && width > maxWidth) {
      height *= maxWidth / width
      width = maxWidth
    }

    if (maxHeight && height > maxHeight) {
      width *= maxHeight / height
      height = maxHeight
    }

    canvas.width = width
    canvas.height = height

    context.drawImage(image, 0, 0, width, height)
    canvas.toBlob(blob => resolve(blob), file.type, quality)
  }

  image.onerror = error => reject(error)
})

const blobToBase64 = blob => new Promise((resolve, reject) => {
  const reader = new FileReader()

  reader.readAsDataURL(blob)
  reader.onload = () => resolve(reader.result.split(',')[1])
  reader.onerror = error => reject(error)
})

const dataURLtoBlob = dataURL => {
  const [header, base64] = dataURL.split(',')
  const mimeMatch = header.match(/:(.*?);/)
  const mime = mimeMatch ? mimeMatch[1] : 'image/png'
  const binary = atob(base64)
  const length = binary.length
  const bytes = new Uint8Array(length)

  for (let index = 0; index < length; index++)
    bytes[index] = binary.charCodeAt(index)

  return new Blob([bytes], { type: mime })
}

const onImageSelected = event => {
  const file = event?.target?.files?.[0]

  if (!file)
    return

  resizeImage(file, 1200, 1200, 1).then(async blob => {
    const base64 = await blobToBase64(blob)

    logoCropped.value = `data:image/jpeg;base64,${base64}`
  })
}

const onSignatureImageSelected = event => {
  const file = event?.target?.files?.[0]

  if (!file)
    return

  resizeImage(file, 1200, 1200, 1).then(async blob => {
    const base64 = await blobToBase64(blob)

    signatureCropped.value = `data:image/jpeg;base64,${base64}`
  })
}

const cropImage = async () => {
  if (!cropper.value)
    return

  const result = cropper.value.getResult({
    mime: 'image/png',
    quality: 1,
    fillColor: 'transparent',
  })

  const blob = dataURLtoBlob(result.canvas.toDataURL('image/png'))
  const formData = new FormData()

  logoOld.value = blob
  formData.append('logo', logoOld.value)

  isConfirmChangeLogoVisible.value = false
  isRequestOngoing.value = true

  if (isAdminRole.value) {
    configsStores.postLogo({
      key: 'logo',
      params: formData,
    })
      .then(async () => {
        await scrollToSectionTop()

        const base64 = await blobToBase64(blob)

        logo.value = `data:image/jpeg;base64,${base64}`
      })
      .catch(() => {
        setAdvisor('error', 'Ett serverfel uppstod. Försök igen.')
        clearAdvisorLater(5000)
      })
      .finally(() => {
        isRequestOngoing.value = false
      })

    return
  }

  profileStores.updateLogo(formData)
    .then(async response => {
      await scrollToSectionTop()
      localStorage.setItem('user_data', JSON.stringify(response.user_data))

      const base64 = await blobToBase64(blob)

      logo.value = `data:image/jpeg;base64,${base64}`
      userData.value = response.user_data
    })
    .catch(() => {
      setAdvisor('error', 'Ett serverfel uppstod. Försök igen.')
      clearAdvisorLater(5000)
    })
    .finally(() => {
      isRequestOngoing.value = false
    })
}

const cropSignatureImage = async () => {
  if (!cropperSignature.value)
    return

  const result = cropperSignature.value.getResult({
    mime: 'image/png',
    quality: 1,
    fillColor: 'transparent',
  })

  const blob = dataURLtoBlob(result.canvas.toDataURL('image/png'))
  const formData = new FormData()

  signatureOld.value = blob
  formData.append('img_signature', signatureOld.value)

  isConfirmChangeSignatureVisible.value = false
  isRequestOngoing.value = true

  if (isAdminRole.value) {
    configsStores.postSignature({
      key: 'signature',
      params: formData,
    })
      .then(async () => {
        await scrollToSectionTop()

        const base64 = await blobToBase64(blob)

        signature.value = `data:image/jpeg;base64,${base64}`
      })
      .catch(() => {
        setAdvisor('error', 'Ett serverfel uppstod. Försök igen.')
        clearAdvisorLater(5000)
      })
      .finally(() => {
        isRequestOngoing.value = false
      })

    return
  }

  profileStores.updateSignature(formData)
    .then(async response => {
      await scrollToSectionTop()
      localStorage.setItem('user_data', JSON.stringify(response.user_data))

      const base64 = await blobToBase64(blob)

      signature.value = `data:image/jpeg;base64,${base64}`
      userData.value = response.user_data
    })
    .catch(() => {
      setAdvisor('error', 'Ett serverfel uppstod. Försök igen.')
      clearAdvisorLater(5000)
    })
    .finally(() => {
      isRequestOngoing.value = false
    })
}

const openSignaturePadDialog = () => {
  isSignaturePadDialogVisible.value = true

  nextTick(() => {
    const canvas = signaturePadCanvas.value

    if (!canvas)
      return

    signaturePadInstance.value = new SignaturePad(canvas, {
      backgroundColor: 'rgba(255, 255, 255, 0)',
      penColor: 'rgb(0, 0, 0)',
    })

    const ratio = Math.max(window.devicePixelRatio || 1, 1)

    canvas.width = canvas.offsetWidth * ratio
    canvas.height = canvas.offsetHeight * ratio
    canvas.getContext('2d').scale(ratio, ratio)
    signaturePadInstance.value.clear()
  })
}

const clearSignaturePad = () => {
  signaturePadInstance.value?.clear()
}

const confirmLeave = () => {
  dialog.value = false
  nextRoute?.()
  nextRoute = null
}

const cancelLeave = () => {
  dialog.value = false
  nextRoute?.(false)
  nextRoute = null
}

const saveSignatureFromPad = async () => {
  if (!signaturePadInstance.value || signaturePadInstance.value.isEmpty()) {
    isSignaturePadDialogVisible.value = false

    return
  }

  const signatureDataUrl = signaturePadInstance.value.toDataURL('image/png')
  const blob = dataURLtoBlob(signatureDataUrl)
  const formData = new FormData()

  formData.append('img_signature', blob, 'signature.png')

  isSignaturePadDialogVisible.value = false
  isRequestOngoing.value = true

  if (isAdminRole.value) {
    configsStores.postSignature({
      key: 'signature',
      params: formData,
    })
      .then(async () => {
        await scrollToSectionTop()
        signature.value = signatureDataUrl
      })
      .catch(() => {
        setAdvisor('error', 'Ett serverfel uppstod. Försök igen.')
        clearAdvisorLater(5000)
      })
      .finally(() => {
        isRequestOngoing.value = false
      })

    return
  }

  profileStores.updateSignature(formData)
    .then(async response => {
      await scrollToSectionTop()
      localStorage.setItem('user_data', JSON.stringify(response.user_data))
      signature.value = signatureDataUrl
      userData.value = response.user_data
    })
    .catch(() => {
      setAdvisor('error', 'Ett serverfel uppstod. Försök igen.')
      clearAdvisorLater(5000)
    })
    .finally(() => {
      isRequestOngoing.value = false
    })
}

const onCropChange = () => {}

const formatOrgNumber = () => {
  let numbers = form.value.organization_number.replace(/\D/g, '')

  if (numbers.length > 4)
    numbers = `${numbers.slice(0, -4)}-${numbers.slice(-4)}`

  form.value.organization_number = numbers
}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (!isValid)
      return

    if (isAdminRole.value) {
      isRequestOngoing.value = true

      const currentCompanyConfig = configsStores.getFeaturedConfig('company') ?? {}

      configsStores.postFeature({
        key: 'company',
        params: {
          value: {
            ...currentCompanyConfig,
            company: form.value.company,
            email: form.value.email,
            name: form.value.name,
            last_name: form.value.last_name,
            organization_number: form.value.organization_number,
            address: form.value.address,
            street: form.value.street,
            postal_code: form.value.postal_code,
            phone: form.value.phone,
            link: form.value.link,
            bank: form.value.bank,
            iban: form.value.iban,
            account_number: form.value.account_number,
            iban_number: form.value.iban_number,
            bic: form.value.bic,
            plus_spin: form.value.plus_spin,
            swish: form.value.swish,
            vat: form.value.vat,
          },
        },
      })
        .then(async () => {
          await scrollToSectionTop()
          setAdvisor('success', 'Uppdaterad företagsinformation.')
          await fetchData()
          syncInitialFormSnapshot()
          clearAdvisorLater(5000)
        })
        .catch(() => {
          setAdvisor('error', 'Ett serverfel uppstod. Försök igen.')
          clearAdvisorLater(5000)
        })
        .finally(() => {
          isRequestOngoing.value = false
        })

      return
    }

    const formData = new FormData()

    formData.append('logo', logoOld.value ?? '')
    formData.append('company', form.value.company)
    formData.append('organization_number', form.value.organization_number)
    formData.append('address', form.value.address)
    formData.append('street', form.value.street)
    formData.append('postal_code', form.value.postal_code)
    formData.append('phone', form.value.phone)
    formData.append('link', form.value.link)
    formData.append('bank', form.value.bank)
    formData.append('iban', form.value.iban)
    formData.append('account_number', form.value.account_number)
    formData.append('iban_number', form.value.iban_number)
    formData.append('bic', form.value.bic)
    formData.append('plus_spin', form.value.plus_spin)
    formData.append('swish', form.value.swish)
    formData.append('vat', form.value.vat)

    isRequestOngoing.value = true

    profileStores.updateCompany(formData)
      .then(async response => {
        await scrollToSectionTop()

        setAdvisor('success', 'Personlig information uppdaterad.')
        localStorage.setItem('user_data', JSON.stringify(response.user_data))
        userData.value = response.user_data

        await fetchData()
        syncInitialFormSnapshot()

      })
      .catch(() => {
        setAdvisor('error', 'Ett serverfel uppstod. Försök igen.')
        clearAdvisorLater(5000)
      })
      .finally(() => {
        isRequestOngoing.value = false
      })
  })
}

function resizeSectionToRemainingViewport() {
  const element = sectionEl.value

  if (!element)
    return

  const rect = element.getBoundingClientRect()
  const remaining = Math.max(0, window.innerHeight - rect.top - 25)

  element.style.minHeight = `${remaining}px`
}

onMounted(() => {
  fetchData()
  resizeSectionToRemainingViewport()
  window.addEventListener('resize', resizeSectionToRemainingViewport)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', resizeSectionToRemainingViewport)
})
</script>

<template>
  <section ref="sectionEl" class="page-section bg-white">
    <LoadingOverlay :is-loading="isRequestOngoing" />
    <VSnackbar
      v-model="advisor.show"
      transition="scroll-y-reverse-transition"
      :location="snackbarLocation"
      :color="advisor.type"
      class="snackbar-alert snackbar-dashboard"
    >
      {{ advisor.message }}
    </VSnackbar>

    <VCard class="card-fill">
      <VCardText v-if="windowWidth < 1024" class="pb-0">
        <div class="d-flex flex-column gap-4 flex-1">
          <VBtn
            class="btn-light"
            style="width: 120px;"
            :to="{ name: 'dashboard-settings' }"
          >
            <VIcon icon="custom-return" size="24" />
            Tillbaka
          </VBtn>

          <span class="title-settings pb-4 border-bottom-settings">
            Mitt företag
          </span>
        </div>
      </VCardText>

      <VCardText class="pb-0">
        <div class="settings-layout border-bottom-settings pb-5">
          <div class="settings-layout__sidebar">
            <div class="d-flex flex-column gap-4">
              <span class="subtitle-settings">Varumärke och utseende</span>
              <span class="text-settings">
                Anpassa hur ditt företag presenteras.
                Lägg till logotyp och uppdatera företagsinformation, bankuppgifter och signatur för ett enhetligt intryck i avtal och fakturor.
              </span>
            </div>
          </div>
          <div class="settings-layout__content">
            <div
              class="d-flex flex-wrap"
              :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
              :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
            >
              <div class="d-flex justify-start" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                
                <div class="logo-store">
                  <VBtn
                    v-if="role !== 'User'"
                    type="button"
                    :block="windowWidth < 1024"
                    class="logo-button btn-ghost btn-white-logo"
                    :class="windowWidth < 1024 ? 'w-40' : 'w-auto'"
                    @click="isConfirmChangeLogoVisible = true"
                  >
                    <div class="btn-white-logo v-btn__content">
                      <VIcon icon="custom-pencil" size="24" />
                    </div>
                  </VBtn>

                  <VImg :src="logo" class="logo-store-img" contain />
                </div>
              </div>
              <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                <-- Color Settings -->
              </div>
            </div>
          </div>          
        </div>
      </VCardText>

      <VCardText>
        <div class="settings-layout">
          <div class="settings-layout__sidebar">
            <div class="d-flex flex-column gap-4">
              <span class="subtitle-settings">Företagsinformation</span>
              <span class="text-settings">
                Fyll i dina företagsuppgifter och kontaktinformation.
                Denna information används i avtal och fakturor.
              </span>
            </div>
          </div>

          <div class="settings-layout__content d-flex flex-column gap-6">

            <VForm
              ref="refVForm"
              class="card-form"
              @submit.prevent="onSubmit"
            >
              <div
                class="d-flex flex-wrap"
                :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
              >
                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Företagsnamn*" />
                  <VTextField
                    v-model="form.company"
                    :disabled="role === 'User'"
                    :rules="[requiredValidator]"
                  />
                </div>

                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Organisationsnummer*" />
                  <VTextField
                    v-model="form.organization_number"
                    :disabled="role === 'Supplier' || role === 'User'"
                    :rules="[requiredValidator, minLengthDigitsValidator(10)]"
                    minLength="11"
                    maxlength="11"
                    @input="formatOrgNumber()"
                  />
                </div>

                <template v-if="isAdminRole">
                  <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Namn*" />
                    <VTextField
                      v-model="form.name"
                      :rules="[requiredValidator]"
                    />
                  </div>

                  <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Efternamn*" />
                    <VTextField
                      v-model="form.last_name"
                      :rules="[requiredValidator]"
                    />
                  </div>
                </template>

                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Adress*" />
                  <VTextField
                    v-model="form.address"
                    :disabled="role === 'User'"
                    :rules="[requiredValidator]"
                  />
                </div>

                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Postnummer*" />
                  <VTextField
                    v-model="form.postal_code"
                    :disabled="role === 'User'"
                    :rules="[requiredValidator]"
                  />
                </div>

                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Stad*" />
                  <VTextField
                    v-model="form.street"
                    :disabled="role === 'User'"
                    :rules="[requiredValidator]"
                  />
                </div>

                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Telefon*" />
                  <VTextField
                    v-model="form.phone"
                    :disabled="role === 'User'"
                    :rules="[requiredValidator, phoneValidator]"
                  />
                </div>

                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Hemsida" />
                  <VTextField
                    v-model="form.link"
                    :disabled="role === 'User'"
                    :rules="[urlValidator]"
                  />
                </div>

                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Bank*" />
                  <VTextField
                    v-model="form.bank"
                    :disabled="role === 'User'"
                    :rules="[requiredValidator]"
                  />
                </div>

                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Bankgiro" />
                  <VTextField
                    v-model="form.iban"
                    :disabled="role === 'User'"
                  />
                </div>

                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kontonummer*" />
                  <VTextField
                    v-model="form.account_number"
                    :disabled="role === 'User'"
                    :rules="[requiredValidator]"
                  />
                </div>

                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Iban nummer" />
                  <VTextField
                    v-model="form.iban_number"
                    :disabled="role === 'User'"
                  />
                </div>

                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="BIC" />
                  <VTextField
                    v-model="form.bic"
                    :disabled="role === 'User'"
                  />
                </div>

                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Plusgiro" />
                  <VTextField
                    v-model="form.plus_spin"
                    :disabled="role === 'User'"
                  />
                </div>

                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Swish" />
                  <VTextField
                    v-model="form.swish"
                    :disabled="role === 'User'"
                    :rules="[phoneValidator]"
                  />
                </div>

                <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Vat" />
                  <VTextField
                    v-model="form.vat"
                    :disabled="role === 'User'"
                  />
                </div>

                <template v-if="isAdminRole">
                  <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post*" />
                    <VTextField
                      v-model="form.email"
                      :rules="[emailValidator, requiredValidator]"
                    />
                  </div>
                </template>

                <div v-else :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Payout number" />
                  <VTextField
                    v-model="form.payout_number"
                    disabled
                  />
                </div>

                <div class="w-100">
                  <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Signatur" />
                  <div class="d-flex align-center gap-4" :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'">
                    <div class="signature-preview-box">
                      <VImg :src="signature" class="signature-image" />
                    </div>

                    <div class="d-flex flex-column gap-2 signature-actions-box">
                      <VBtn
                        type="button"
                        class="btn-light w-auto"
                        block
                        :disabled="role === 'User'"
                        @click="isConfirmChangeSignatureVisible = true"
                      >
                        <VIcon icon="custom-upload" size="24" />
                        Ladda upp fil
                      </VBtn>

                      <VBtn
                        type="button"
                        :disabled="role === 'User'"
                        class="btn-ghost w-auto"
                        @click="openSignaturePadDialog"
                      >
                        <VIcon icon="custom-pencil" size="24" />
                        Rita signatur
                      </VBtn>
                    </div>
                  </div>

                  <div v-if="role !== 'User'" class="p-0 d-flex w-100">
                    <VBtn
                      type="submit"
                      :block="windowWidth < 1024"
                      class="btn-gradient"
                      :class="windowWidth < 1024 ? 'w-40 mt-2' : 'w-25 mt-6'"
                    >
                      Spara
                    </VBtn>
                  </div>
                </div>               
              </div>
            </VForm>
          </div>
        </div>
      </VCardText>
    </VCard>

    <VDialog
      v-model="dialog"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="cancelLeave"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <img :src="modalWarningIcon" alt="Warning" class="action-icon">
          <div class="dialog-title">
            Avsluta utan att spara
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          <strong>Du har osparade ändringar.</strong> Är du säker på att du vill lämna sidan?
        </VCardText>
        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="cancelLeave"
          >
            Avbryt
          </VBtn>
          <VBtn
            class="btn-gradient"
            @click="confirmLeave"
          >
            Ja, fortsätt
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog
      v-model="isConfirmChangeLogoVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmChangeLogoVisible = !isConfirmChangeLogoVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box mt-2">
          <div class="dialog-title">Byt logotyp</div>
        </VCardText>

        <VCardText class="dialog-text">
          Logotypen du väljer kommer att visas på din faktura och dina kontrakt.
        </VCardText>

        <VCardText class="dialog-text">
          <VRow>
            <VCol cols="12" md="12">
              <Cropper
                v-if="logoCropped"
                ref="cropper"
                class="cropper-container"
                preview-class="cropper-preview"
                background-class="cropper-background"
                :src="logoCropped"
                :stencil-props="{ previewClass: 'cropper-preview-circle' }"
                @change="onCropChange"
              />
            </VCol>

            <VCol cols="12" md="12" class="pt-0">
              <div class="form-field d-flex flex-column gap-1">
                <label>Logotyp</label>
                <div class="d-flex flex-wrap gap-2">
                  <VIcon size="40" icon="custom-camera" />
                  <VFileInput
                    v-model="filename"
                    class="mb-2"
                    accept="image/png, image/jpeg, image/bmp, image/webp"
                    prepend-icon=""
                    @change="onImageSelected"
                    @click:clear="resetAvatar"
                  />
                </div>
              </div>
              <VLabel class="mb-1 text-body-2 text-high-emphasis text-sm" text="Tillåtna format JPG, GIF, PNG." />
            </VCol>
          </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-0">
          <VBtn class="btn-light" @click="isConfirmChangeLogoVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="cropImage">
            Spara
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog
      v-model="isConfirmChangeSignatureVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmChangeSignatureVisible = !isConfirmChangeSignatureVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-signature" class="action-icon" />
          <div class="dialog-title">Byt firma</div>
        </VCardText>

        <VCardText class="dialog-text">
          Firma du väljer kommer att visas på dina kontrakt.
        </VCardText>

        <VCardText class="dialog-text">
          <div
            :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
            :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
          >
            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
              <Cropper
                v-if="signatureCropped"
                ref="cropperSignature"
                class="cropper-container"
                preview-class="cropper-preview"
                background-class="cropper-background"
                :src="signatureCropped"
                :stencil-props="{ aspectRatio: 16 / 9 }"
              />
            </div>

            <div class="mt-4" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
              <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Firm" />
              <div class="d-flex flex-wrap gap-2">
                <VIcon size="40" icon="custom-camera" />
                <VFileInput
                  v-model="signatureFilename"
                  class="mb-2"
                  accept="image/png, image/jpeg, image/bmp, image/webp"
                  prepend-icon=""
                  @change="onSignatureImageSelected"
                  @click:clear="resetSignature"
                />
              </div>
            </div>
          </div>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-3">
          <VBtn class="btn-light" @click="isConfirmChangeSignatureVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="cropSignatureImage">
            Spara
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog
      v-model="isSignaturePadDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isSignaturePadDialogVisible = !isSignaturePadDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-signature" class="action-icon" />
          <div class="dialog-title">Rita din signatur</div>
        </VCardText>

        <VCardText class="dialog-text">
          <div class="signature-pad-wrapper">
            <canvas ref="signaturePadCanvas"></canvas>
          </div>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-ghost" @click="isSignaturePadDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-light" @click="clearSignaturePad">
            Rensa
          </VBtn>
          <VBtn class="btn-gradient" @click="saveSignatureFromPad">
            Acceptera & Spara
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<style scoped>
:deep(.vue-simple-handler) {
  background: #57F287 !important;
}

:deep(.cropper-preview-circle) {
  border: dashed 1px #57F287;
}

:deep(.cropper-background),
:deep(.vue-advanced-cropper__foreground) {
  background-color: transparent !important;
}

:deep(.logo-store-img .v-img__img) {
  width: 75%;
  height: 75%;
  display: block;
  margin: auto;
  position: relative;
  top: auto;
  left: auto;
}

:deep(.logo-store-img .v-responsive__sizer) {
  flex: 0;
}

.v-btn.btn-white-logo {
  background-color: transparent !important;
  color: #FFFFFF !important;
}

.v-btn.btn-white-logo .v-btn__content {
  z-index: 0;
  color: #FFFFFF !important;
}

.cropper-container {
  width: 100%;
  height: 250px;
  background-color: #f5f5f5;
  border-radius: 8px;
  overflow: hidden;
}

.cropper-preview {
  width: 100px;
  height: 100px;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-top: 1rem;
}

.info-store {
  padding: 0;
}

.logo-store {
  z-index: 2;
  position: relative;
}

.logo-store-img {
  width: 144px;
  height: 144px;
  max-width: 144px;
  background: linear-gradient(90deg, #57F287 0%, #00EEB0 50%, #00FFFF 100%);
  border-radius: 50% !important;
  object-fit: cover;
  box-shadow: 0 0 30px 0 rgba(0, 0, 0, 0.25);
}

.logo-store .logo-button {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 3;
}

.store-name {
  font-weight: 700;
  font-size: 32px;
  line-height: 24px;
  letter-spacing: 0;
  color: #454545;
}

.signature-preview-box {
  width: 50%;
}

.signature-actions-box {
  width: 50%;
}

.signature-image {
  flex: 1 1;
  width: 100%;
  height: 104px;
  border-radius: 8px;
  border: solid 1px #878787;
  opacity: 0.8;
  background-color: #f6f6f6;
}

.signature-pad-wrapper {
  border: 1px solid #e7e7e7;
  border-radius: 8px;
  background-color: #f6f6f6;
}

.signature-pad-wrapper canvas {
  width: 100%;
  height: 232px;
  display: block;
  cursor: crosshair;
}

@media (max-width: 776px) {
  .cropper-container {
    height: 250px;
  }

  .logo-store .logo-button {
    top: 50%;
    left: 50%;
    right: auto;
    transform: translate(-50%, -50%);
  }

  .signature-preview-box,
  .signature-actions-box {
    width: 100%;
  }

  .store-name {
    text-align: center;
    line-height: 1.2;
  }
}
</style>

<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
