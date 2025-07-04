<script setup>

import { requiredValidator, phoneValidator, urlValidator } from '@/@core/utils/validators'
import { useProfileStores } from '@/stores/useProfile'
import { useSuppliersStores } from '@/stores/useSuppliers'
import { useAuthStores } from '@/stores/useAuth'
import { themeConfig } from '@themeConfig'
import { Cropper } from 'vue-advanced-cropper'
import banner from '@images/logos/banner.jpeg'
import logo_ from '@images/logos/favicon@2x.png';
import 'vue-advanced-cropper/dist/style.css'

const authStores = useAuthStores()
const profileStores = useProfileStores()
const suppliersStores = useSuppliersStores()

const refVForm = ref()
const isRequestOngoing = ref(true)

const isConfirmChangeLogoVisible = ref(false)
const cropper = ref()

const data = ref(null)
const userData = ref(null)
const logo = ref(null)
const logoCropped = ref(null)
const logoOld = ref(null)
const filename = ref([])

const supplier = ref(null)
const form = ref({
    company: '',
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
    vat: ''
})

const advisor = ref({
    message: '',
    show: false,  
    type: '',
})

const emit = defineEmits([
  'window',
  'alert'
])

watch(form.value, () => {
  emit('window', true);
}, { deep: true });

watchEffect(fetchData)

async function fetchData() {

    isRequestOngoing.value = true

    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    data.value = await authStores.company()

    supplier.value = await suppliersStores.showSupplier(Number(userData.value.supplier.id))
    //company
    form.value.company = supplier.value.company
    form.value.organization_number = supplier.value.organization_number
    form.value.link = supplier.value.link
    form.value.address = supplier.value.address
    form.value.street = supplier.value.street
    form.value.postal_code = supplier.value.postal_code
    form.value.phone = supplier.value.phone

    //bank
    form.value.bank = supplier.value.bank
    form.value.account_number = supplier.value.account_number

    form.value.iban = supplier.value.iban
    form.value.iban_number = supplier.value.iban_number
    form.value.bic = supplier.value.bic
    form.value.plus_spin = supplier.value.plus_spin
    form.value.swish = supplier.value.swish
    form.value.vat = supplier.value.vat

    logo.value = (data.value.supplier.logo !== null) ? themeConfig.settings.urlStorage + data.value.supplier.logo : logo_ 
    logoCropped.value = (data.value.supplier.logo !== null) ? themeConfig.settings.urlStorage + data.value.supplier.logo : logo_ 
    
    setTimeout(() => {
        emit('window', false)
    }, 500)

    isRequestOngoing.value = false
}

const resetAvatar = () => {
  logoCropped.value = null
  logoOld.value = null
}

const resizeImage = function(file, maxWidth, maxHeight, quality) {
  return new Promise((resolve, reject) => {
    const img = new Image()

    img.src = URL.createObjectURL(file)

    img.onload = () => {
      const canvas = document.createElement('canvas')
      const ctx = canvas.getContext('2d')

      let width = img.width
      let height = img.height

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

      ctx.drawImage(img, 0, 0, width, height)

      canvas.toBlob(blob => {
        resolve(blob)
      }, file.type, quality)
    }
    img.onerror = error => {
      reject(error)
    }
  })
}

const blobToBase64 = blob => {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()

    reader.readAsDataURL(blob)
    reader.onload = () => {
      resolve(reader.result.split(',')[1])
    }
    reader.onerror = error => {
      reject(error)
    }
  })
}

const onImageSelected = event => {
  const file = event.target.files[0]

  if (!file) return
  // logoOld.value = file

  URL.createObjectURL(file)

  resizeImage(file, 1200, 1200, 1)
    .then(async blob => {
        let r = await blobToBase64(blob)
        logoCropped.value = 'data:image/jpeg;base64,' + r
    })
}

const dataURLtoBlob = (dataURL) => {
  const [header, base64] = dataURL.split(',');
  const mimeMatch = header.match(/:(.*?);/);
  const mime = mimeMatch ? mimeMatch[1] : 'image/png'; 
  const binary = atob(base64);
  const len = binary.length;
  const u8arr = new Uint8Array(len);
  for (let i = 0; i < len; i++) {
    u8arr[i] = binary.charCodeAt(i);
  }
  return new Blob([u8arr], { type: mime });
}

const cropImage = async () => {
    if (cropper.value) {
        const result = cropper.value.getResult({
            mime: 'image/png',
            quality: 1,
            fillColor: 'transparent'
        });
        const blob = dataURLtoBlob(result.canvas.toDataURL("image/png"));

        logoOld.value = blob 

        let formData = new FormData()

        formData.append('logo', logoOld.value)
        
        isConfirmChangeLogoVisible.value = false
        isRequestOngoing.value = true

        profileStores.updateLogo(formData)
            .then(async response => {    

                window.scrollTo(0, 0)
                
                isRequestOngoing.value = false
                localStorage.setItem('user_data', JSON.stringify(response.user_data))     

                let r = await blobToBase64(blob)
                logo.value = 'data:image/jpeg;base64,' + r

            }).catch(error => {
                isRequestOngoing.value = false
                console.log('error', error)
                advisor.value.type = 'error'
                advisor.value.show = true
                advisor.value.message = 'Ett fel har inträffat...! (Serverfel)'
                emit('alert', advisor)

                setTimeout(() => {
                    advisor.value.show = false,
                    advisor.value.message = ''
                    emit('alert', advisor)
                }, 5000) 
            })           

    }
}

const onCropChange = (coordinates) => {
    // console.log('coordinates', coordinates)
}

const onSubmit = () => {
    
    refVForm.value?.validate().then(({ valid: isValid }) => {
        if (isValid) {

            let formData = new FormData()

            formData.append('logo', logoOld.value)
            
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

            profileStores.updateSupplier(formData)
                .then(response => {    

                    window.scrollTo(0, 0)
                    
                    isRequestOngoing.value = false

                    advisor.value.type = 'success'
                    advisor.value.message = 'Personlig information uppdaterad. Sidan laddas om automatiskt för att se effekterna...!'
                    advisor.value.show = true
                    emit('alert', advisor)

                    localStorage.setItem('user_data', JSON.stringify(response.user_data))
                    
                    fetchData()

                    setTimeout(() => {
                        advisor.value.show = false,
                        advisor.value.message = ''
                        emit('alert', advisor)
                        location.reload()
                    }, 5000)

                }).catch(error => {
                    isRequestOngoing.value = false

                    advisor.value.type = 'error'
                    advisor.value.show = true
                    advisor.value.message = 'Ett fel har inträffat...! (Serverfel)'
                    emit('alert', advisor)

                    setTimeout(() => {
                        advisor.value.show = false,
                        advisor.value.message = ''
                        emit('alert', advisor)
                    }, 5000) 
                })
            }
    })
}
</script>

<template>
  <section>
    <VDialog
        v-model="isRequestOngoing"
        width="auto"
        persistent>
        <VProgressCircular
            indeterminate
            color="primary"
            class="mb-0"/>
    </VDialog>

    <VRow>
        <VCol cols="12">
            <VCard>
                <VCardText class="p-0">
                    <VImg :src="banner" class="banner-img" cover/>
                </VCardText>

                <VCardText class="tw-bg-tertiary p-0">
                    <VRow no-gutters>
                        <VCol cols="12" md="12" class="d-flex col-logo">
                            <div class="logo-store">
                             <VBadge 
                                @click="isConfirmChangeLogoVisible = true"
                                class="cursor-pointer"
                                color="success">
                                <template #badge>
                                    <VIcon icon="tabler-pencil" />
                                </template>
                                    <VImg :src="logo" class="logo-store-img" contain/>
                            </VBadge>
                            </div>
                        </VCol>
                        <VCol cols="12" md="12" class="py-0 info-logo-store">
                            <VCardItem class="info-store">
                                <span class="store-name pb-3">{{ form.company }}</span>
                            </VCardItem>
                            <VCardItem class="info-store" v-if="form.address !== null">
                                <span class="store-address pb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="18" viewBox="0 0 15 18" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.25736 3.25736C4.38258 2.13214 5.9087 1.5 7.5 1.5C9.0913 1.5 10.6174 2.13214 11.7426 3.25736C12.8679 4.38258 13.5 5.9087 13.5 7.5C13.5 9.82354 11.9882 12.0782 10.3305 13.8279C9.51704 14.6866 8.701 15.3896 8.08749 15.8781C7.85916 16.0599 7.65973 16.2114 7.5 16.3294C7.34027 16.2114 7.14084 16.0599 6.91251 15.8781C6.299 15.3896 5.48296 14.6866 4.66946 13.8279C3.0118 12.0782 1.5 9.82354 1.5 7.5C1.5 5.9087 2.13214 4.38258 3.25736 3.25736ZM7.08357 17.8738C7.08379 17.8739 7.08397 17.874 7.5 17.25L7.91603 17.874C7.6641 18.042 7.33549 18.0417 7.08357 17.8738ZM7.08357 17.8738L7.5 17.25C7.91603 17.874 7.91678 17.8735 7.91699 17.8734L7.91857 17.8723L7.92357 17.869L7.94076 17.8574C7.95536 17.8474 7.97619 17.8332 8.00283 17.8147C8.0561 17.7778 8.13265 17.7241 8.22916 17.6544C8.42209 17.5151 8.69523 17.3117 9.02188 17.0516C9.674 16.5323 10.5455 15.7821 11.4195 14.8596C13.1368 13.0468 15 10.4265 15 7.5C15 5.51088 14.2098 3.60322 12.8033 2.1967C11.3968 0.790176 9.48912 0 7.5 0C5.51088 0 3.60322 0.790176 2.1967 2.1967C0.790176 3.60322 0 5.51088 0 7.5C0 10.4265 1.8632 13.0468 3.58054 14.8596C4.45454 15.7821 5.326 16.5323 5.97812 17.0516C6.30477 17.3117 6.57791 17.5151 6.77084 17.6544C6.86735 17.7241 6.9439 17.7778 6.99717 17.8147C7.02381 17.8332 7.04464 17.8474 7.05924 17.8574L7.07643 17.869L7.08143 17.8723L7.08357 17.8738ZM6 7.5C6 6.67157 6.67157 6 7.5 6C8.32843 6 9 6.67157 9 7.5C9 8.32843 8.32843 9 7.5 9C6.67157 9 6 8.32843 6 7.5ZM7.5 4.5C5.84315 4.5 4.5 5.84315 4.5 7.5C4.5 9.15685 5.84315 10.5 7.5 10.5C9.15685 10.5 10.5 9.15685 10.5 7.5C10.5 5.84315 9.15685 4.5 7.5 4.5Z" fill="white"/>
                                    </svg>
                                    <span>&nbsp;&nbsp;&nbsp;{{ form.address }}&nbsp;&nbsp;&nbsp;</span>
                                </span>
                            </VCardItem>
                        </VCol>
                    </VRow>
                </VCardText>
            </VCard>
        </VCol>
        <VCol cols="12">
            <VCard>
                <VCardTitle class="px-6 py-5 d-flex justify-content-center align-center">
                    <span>Redigera företagsinformation</span>
                </VCardTitle>
                <VCardText>
                    <VForm
                        ref="refVForm"
                        @submit.prevent="onSubmit">
                        <VRow>
                            <VCol cols="12" md="9">
                                <VTextField
                                    v-model="form.company"
                                    label="Företagsnamn"
                                    :rules="[requiredValidator]"
                                />
                            </VCol>
                            <VCol cols="12" md="3">
                                <VTextField
                                    v-model="form.organization_number"
                                    label="Organisationsnummer"
                                    :rules="[requiredValidator]"
                                    disabled
                                />
                            </VCol>
                            <VCol cols="12" md="12">
                                <VTextarea
                                    v-model="form.address"
                                    rows="3"
                                    :rules="[requiredValidator]"
                                    label="Adress"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="form.postal_code"
                                    :rules="[requiredValidator]"
                                    label="Postnummer"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="form.street"
                                    :rules="[requiredValidator]"
                                    label="Stad"
                                />
                            </VCol>                            
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="form.phone"
                                    :rules="[requiredValidator, phoneValidator]"
                                    label="Telefon"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="form.link"
                                    :rules="[urlValidator]"
                                    label="Hemsida"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="form.bank"
                                    :rules="[requiredValidator]"
                                    label="Bank"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="form.iban"
                                    label="Bankgiro"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="form.account_number"
                                    :rules="[requiredValidator]"
                                    label="Kontonummer"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="form.iban_number"
                                    label="Iban nummer"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="form.bic"
                                    label="BIC"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="form.plus_spin"
                                    label="Plusgiro"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="form.swish"
                                    label="Swish"
                                    :rules="[phoneValidator]"
                                />
                            </VCol>
                            <VCol cols="12" md="6">
                                <VTextField
                                    v-model="form.vat"
                                    label="Vat"
                                />
                            </VCol>
                            <VCol cols="12">
                                <VBtn type="submit" class="w-100 w-md-auto">
                                    Spara
                                </VBtn>
                            </VCol>
                        </VRow>
                    </VForm>
                </VCardText>
            </VCard>
        </VCol>
    </VRow>

     <!-- 👉 Confirm change logo -->
    <VDialog
      v-model="isConfirmChangeLogoVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isConfirmChangeLogoVisible = !isConfirmChangeLogoVisible" />

      <!-- Dialog Content -->
      <VCard title="Byt logotyp">
        <VDivider class="mt-4"/>
        <VCardText>
          Logotypen du väljer kommer att visas på din faktura och dina kontrakt.
        </VCardText>
        <VCardText class="d-flex flex-column gap-2">
             <VRow>
                <VCol cols="12" md="12">
                    <Cropper
                        v-if="logoCropped"
                        ref="cropper"
                        class="cropper-container"
                        preview-class="cropper-preview"
                        background-class="cropper-background"
                        :src="logoCropped"
                        :stencil-props="{
                            previewClass: 'cropper-preview-circle'
                        }"
                        @change="onCropChange"
                    />

                </VCol>
                <VCol cols="12" md="12">
                     <VFileInput 
                        v-model="filename"
                        label="Logotyp"
                        class="mb-2"
                        accept="image/png, image/jpeg, image/bmp, image/webp"
                        prepend-icon="tabler-camera"
                        @change="onImageSelected"
                        @click:clear="resetAvatar"
                    />
                </VCol>
            </VRow>
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="isConfirmChangeLogoVisible = false">
              Avbryt 
          </VBtn>
          <VBtn @click="cropImage"> 
              Spara
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

  </section>
</template>
<style scoped>

    ::v-deep .vue-simple-handler {
        background: #9966FF !important;
    }
    ::v-deep .cropper-preview-circle {
        border: dashed 1px #9966FF
    }
    ::v-deep .cropper-background,
    ::v-deep .vue-advanced-cropper__foreground {
        background-color: transparent !important;
    }

    .cropper-container {
        width: 100%;
        height: 400px;
        background-color: #f5f5f5;
        border-radius: 8px;
        overflow: hidden;
    }

    .cropper-preview {
        width: 150px;
        height: 150px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-top: 1rem;
    }

    .banner-img {
        width: 100%;
        height: 170px;
    }

    .info-logo-store {
        display: flex;
        padding-left: 14rem !important;
    }

    .info-store {
        padding-left: 0;
        padding-right: 0;
        padding-top: 24px;
        padding-bottom: 24px;
    }

    .logo-store {
        top: 10%;
        left: 3%;
        z-index: 9999;
        position: absolute;
    }
    
    .logo-store-img {
        width: 173.96px;
        height: 173.96px;
        max-width: 173.96px;
        border-radius: 16px;
        background-color: #F5F5F5;
    }

    .tw-bg-tertiary {
        opacity: 1 !important;
        background-color: #0A1B33 !important
    }

    .store-name {
        font-size: 24px;
        font-style: normal;
        font-weight: 600;
        line-height: 24px;
        color: white;
    }

    .store-address {
        font-size: 16px;
        font-style: normal;
        font-weight: 600;
        line-height: 16px;
        color: white;
        margin-inline-start: 20px;
    }

    @media (max-width: 776px) {

        .cropper-container {
            height: 250px;
        }

        .info-logo-store {
            margin-top: 8%;
            margin-bottom: 2%;
            flex-direction: column;
            padding-left: 1rem !important;
        }

        .logo-store {
            top: 5%;
            left:5%;
        }

        .info-store {
            padding-left: 0;
            padding-right: 0;
            padding-top: 0;
            padding-bottom: 0;
        }

        .store-address {
            margin-inline-start: 0;
        }
    }
</style>