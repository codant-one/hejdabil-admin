<script setup>

import { nextTick } from 'vue'; // nextTick para asegurar que el DOM esté listo
import { requiredValidator, phoneValidator, urlValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { useProfileStores } from '@/stores/useProfile'
import { useAuthStores } from '@/stores/useAuth'
import { themeConfig } from '@themeConfig'
import { Cropper } from 'vue-advanced-cropper'
import banner from '@images/logos/banner2.jpg'
import logo_ from '@images/logos/favicon@2x.png';
import SignaturePad from 'signature_pad';
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import 'vue-advanced-cropper/dist/style.css'

const { width: windowWidth } = useWindowSize();

const authStores = useAuthStores()
const profileStores = useProfileStores()

const refVForm = ref()
const isRequestOngoing = ref(true)

const isConfirmChangeLogoVisible = ref(false)
const cropper = ref()

const data = ref(null)
const userData = ref(null)
const role = ref(null)
const logo = ref(null)
const logoCropped = ref(null)
const logoOld = ref(null)
const filename = ref([])

const isConfirmChangeSignatureVisible = ref(false) // Para el diálogo de la firma
const cropperSignature = ref() // Referencia para el nuevo cropper de la firma
const signature = ref(null) // URL de la firma actual
const signatureCropped = ref(null) // Imagen de la firma para el cropper
const signatureOld = ref(null) // Blob de la firma recortada, lista para enviar
const signatureFilename = ref([]) // Para el v-model del nuevo VFileInput

const isSignaturePadDialogVisible = ref(false)
const signaturePadCanvas = ref(null)
const signaturePadInstance = ref(null)

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
    vat: '',
    payout_number: ''
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
    role.value = userData.value.roles[0].name

    //console.log('boss.company', userData.value.supplier.boss.user.user_detail.company)
    //company
    form.value.company = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.company : userData.value.user_detail.company
    form.value.organization_number = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.organization_number : userData.value.user_detail.organization_number
    form.value.link = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.link : userData.value.user_detail.link
    form.value.address = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.address : userData.value.user_detail.address
    form.value.street = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.street : userData.value.user_detail.street
    form.value.postal_code = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.postal_code :  userData.value.user_detail.postal_code
    form.value.phone = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.phone : userData.value.user_detail.phone

    //bank
    form.value.bank = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.bank : userData.value.user_detail.bank
    form.value.account_number = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.account_number : userData.value.user_detail.account_number

    form.value.iban = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.iban : userData.value.user_detail.iban
    form.value.iban_number = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.iban_number : userData.value.user_detail.iban_number
    form.value.bic = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.bic : userData.value.user_detail.bic
    form.value.plus_spin = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.plus_spin : userData.value.user_detail.plus_spin
    form.value.swish = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.swish : userData.value.user_detail.swish
    form.value.vat = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.vat : userData.value.user_detail.vat
    form.value.payout_number = role.value === 'User' ? userData.value.supplier.boss.payout_number : userData.value.supplier.payout_number  

    logo.value = 
        role.value === 'User' ?
        (userData.value.supplier.boss.user.user_detail.logo !== null) ? themeConfig.settings.urlStorage + userData.value.supplier.boss.user.user_detail.logo : logo_  :
        (userData.value.user_detail.logo !== null) ? themeConfig.settings.urlStorage + userData.value.user_detail.logo : logo_ 
    logoCropped.value = 
        role.value === 'User' ?
        (userData.value.supplier.boss.user.user_detail.logo !== null) ? await fetchImageAsBlob(themeConfig.settings.urlStorage + userData.value.supplier.boss.user.user_detail.logo) : logo_  :
        (userData.value.user_detail.logo !== null) ? await fetchImageAsBlob(themeConfig.settings.urlStorage + userData.value.user_detail.logo) : logo_ 

    signature.value = 
        role.value === 'User' ?
        (userData.value.supplier.boss.user.user_detail.img_signature !== null) ? themeConfig.settings.urlStorage + userData.value.supplier.boss.user.user_detail.img_signature : logo_  :
        (userData.value.user_detail.img_signature !== null) ? themeConfig.settings.urlStorage + userData.value.user_detail.img_signature : logo_ 
    signatureCropped.value = 
        role.value === 'User' ?
        (userData.value.supplier.boss.user.user_detail.img_signature !== null) ? await fetchImageAsBlob(themeConfig.settings.urlStorage + userData.value.supplier.boss.user.user_detail.img_signature) : logo_  :
        (userData.value.user_detail.img_signature !== null) ? await fetchImageAsBlob(themeConfig.settings.urlStorage + userData.value.user_detail.img_signature) : logo_
        
    setTimeout(() => {
        emit('window', false)
    }, 500)

    isRequestOngoing.value = false
}

const fetchImageAsBlob = async (url) => {
  const response = await  fetch(themeConfig.settings.urlbase + 'proxy-image?url=' + url);
  const blob = await response.blob();
  return URL.createObjectURL(blob);
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
                advisor.value.message = 'Ett serverfel uppstod. Försök igen.'
                emit('alert', advisor)

                setTimeout(() => {
                    advisor.value.show = false,
                    advisor.value.message = ''
                    emit('alert', advisor)
                }, 5000) 
            })           

    }
}

const resetSignature = () => {
  signatureCropped.value = null
  signatureOld.value = null
}

const onSignatureImageSelected = event => {
  const file = event.target.files[0]

  if (!file) return

  URL.createObjectURL(file)

  resizeImage(file, 1200, 1200, 1) // Reutilizamos tu función resizeImage
    .then(async blob => {
        let r = await blobToBase64(blob)
        signatureCropped.value = 'data:image/jpeg;base64,' + r // Actualizamos la variable de la firma
    })
}


const cropSignatureImage = async () => {
    if (cropperSignature.value) { // Usamos la nueva referencia del cropper
        const result = cropperSignature.value.getResult({
            mime: 'image/png',
            quality: 1,
            fillColor: 'transparent'
        });
        const blob = dataURLtoBlob(result.canvas.toDataURL("image/png"));

        signatureOld.value = blob // Guardamos el blob en la variable de la firma

        let formData = new FormData()

        formData.append('img_signature', signatureOld.value) // La clave debe ser 'img_signature'
        
        isConfirmChangeSignatureVisible.value = false
        isRequestOngoing.value = true

        // IMPORTANTE: Asumo que tendrás una nueva acción en tu store llamada 'updateSignature'
        // Deberás crearla en tu store de Pinia, similar a 'updateLogo'.
        profileStores.updateSignature(formData)
            .then(async response => {    

                window.scrollTo(0, 0)
                
                isRequestOngoing.value = false
                localStorage.setItem('user_data', JSON.stringify(response.user_data))     

                let r = await blobToBase64(blob)
                signature.value = 'data:image/jpeg;base64,' + r // Actualizamos la imagen visible

            }).catch(error => {
                isRequestOngoing.value = false
                console.log('error', error)
                advisor.value.type = 'error'
                advisor.value.show = true
                advisor.value.message = 'Ett serverfel uppstod. Försök igen.'
                emit('alert', advisor)

                setTimeout(() => {
                    advisor.value.show = false,
                    advisor.value.message = ''
                    emit('alert', advisor)
                }, 5000) 
            })           
    }
}

// Función para abrir el diálogo y preparar el lienzo
const openSignaturePadDialog = () => {
  isSignaturePadDialogVisible.value = true;
  nextTick(() => {
    const canvas = signaturePadCanvas.value;
    if (canvas) {
      signaturePadInstance.value = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255, 255, 255, 0)', // Fondo transparente
        penColor: 'rgb(0, 0, 0)',
      });
      const ratio = Math.max(window.devicePixelRatio || 1, 1);
      canvas.width = canvas.offsetWidth * ratio;
      canvas.height = canvas.offsetHeight * ratio;
      canvas.getContext("2d").scale(ratio, ratio);
      signaturePadInstance.value.clear();
    }
  });
};

// Función para limpiar el lienzo
const clearSignaturePad = () => {
  if (signaturePadInstance.value) {
    signaturePadInstance.value.clear();
  }
};

// Función para guardar la firma dibujada
const saveSignatureFromPad = async () => {
  if (signaturePadInstance.value && !signaturePadInstance.value.isEmpty()) {
    // Obtener la firma como imagen PNG con fondo transparente
    const signatureDataUrl = signaturePadInstance.value.toDataURL('image/png');
    
    // Convertir la DataURL a un Blob (archivo en memoria)
    const blob = dataURLtoBlob(signatureDataUrl); // Reutilizamos tu función existente!

    // Crear FormData y añadir el blob como si fuera un archivo subido
    const formData = new FormData();
    formData.append('img_signature', blob, 'signature.png'); // El backend lo recibirá como un archivo

    // Cerramos el diálogo y mostramos el spinner
    isSignaturePadDialogVisible.value = false;
    isRequestOngoing.value = true;

    // Llamamos a la misma acción de Pinia que usa la subida de archivo
    profileStores.updateSignature(formData)
      .then(async response => {    
        window.scrollTo(0, 0);
        localStorage.setItem('user_data', JSON.stringify(response.user_data));
        
        // Actualizamos la imagen visible en la página con la nueva firma
        signature.value = signatureDataUrl;
        
        // Opcional: mostrar un mensaje de éxito
      })
      .catch(error => {
        console.log('error', error);
        advisor.value.type = 'error';
        advisor.value.show = true;
        advisor.value.message = 'Ett serverfel uppstod. Försök igen.';
        emit('alert', advisor);
        setTimeout(() => {
            advisor.value.show = false;
            advisor.value.message = '';
            emit('alert', advisor);
        }, 5000); 
      })
      .finally(() => {
        isRequestOngoing.value = false;
      });
  } else {
    // Si el lienzo está vacío, simplemente cierra el diálogo
    isSignaturePadDialogVisible.value = false;
  }
};

const onCropChange = (coordinates) => {
    // console.log('coordinates', coordinates)
}

const formatOrgNumber = () => {

    let numbers = form.value.organization_number.replace(/\D/g, '')
    if (numbers.length > 4) {
        numbers = numbers.slice(0, -4) + '-' + numbers.slice(-4)
    }
    form.value.organization_number = numbers
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

            profileStores.updateCompany(formData)
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
                    advisor.value.message = 'Ett serverfel uppstod. Försök igen.'
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
    <LoadingOverlay :is-loading="isRequestOngoing" />

    <VRow>
        <VCol cols="12" class="pb-0">
            <VCardText class="px-0">
                <VRow class="px-md-3">
                    <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                        <div 
                            class="d-flex flex-wrap"
                            :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                            :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                        >
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                                <VImg :src="banner" class="banner-img" cover/>

                                <VBtn 
                                    type="button" 
                                    :block="windowWidth < 1024"
                                    class="logo-button btn-light btn-white-logo "
                                    :class="windowWidth < 1024 ? 'w-40' : 'w-auto'"
                                    @click="isConfirmChangeLogoVisible = true"
                                >
                                    <div class="btn-white-logo v-btn__content">
                                        <VIcon icon="custom-pencil" size="24" />
                                        Redigera
                                    </div>
                                </VBtn>
                            </div>
                            <div class="d-flex justify-center" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                                <div class="logo-store">
                                    <VImg 
                                        :src="logo" 
                                        class="logo-store-img logo-store-img--circle shadow-lg" 
                                        contain/>
                                </div>
                            </div>
                            <div class="d-flex justify-center mt-n1" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                                <div class="info-store">
                                    <span class="store-name pb-3">{{ form.company }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <VCol cols="12" :class="windowWidth < 1024 ? '' : 'px-0'">
                            <div class="title-tabs mb-5">
                                Redigera företagsinformation
                            </div>
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
                                            :disabled="role === 'User'"
                                            v-model="form.company"
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
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Adress*" />
                                        <VTextarea
                                            :disabled="role === 'User'"
                                            v-model="form.address"
                                            rows="3"
                                            :rules="[requiredValidator]"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Postnummer*" />
                                        <VTextField
                                            :disabled="role === 'User'"
                                            v-model="form.postal_code"
                                            :rules="[requiredValidator]"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Stad*" />
                                        <VTextField
                                            :disabled="role === 'User'"
                                            v-model="form.street"
                                            :rules="[requiredValidator]"
                                        />
                                    </div>                            
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Telefon*" />
                                        <VTextField
                                            :disabled="role === 'User'"
                                            v-model="form.phone"
                                            :rules="[requiredValidator, phoneValidator]"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Hemsida" />
                                        <VTextField
                                            :disabled="role === 'User'"
                                            v-model="form.link"
                                            :rules="[urlValidator]"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Bank*" />
                                        <VTextField
                                            :disabled="role === 'User'"
                                            v-model="form.bank"
                                            :rules="[requiredValidator]"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Bankgiro" />
                                        <VTextField
                                            :disabled="role === 'User'"
                                            v-model="form.iban"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kontonummer*" />
                                        <VTextField
                                            :disabled="role === 'User'"
                                            v-model="form.account_number"
                                            :rules="[requiredValidator]"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Iban nummer" />
                                        <VTextField
                                            :disabled="role === 'User'"
                                            v-model="form.iban_number"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="BIC" />
                                        <VTextField
                                            :disabled="role === 'User'"
                                            v-model="form.bic"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Plusgiro" />
                                        <VTextField
                                            :disabled="role === 'User'"
                                            v-model="form.plus_spin"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Swish" />
                                        <VTextField
                                            :disabled="role === 'User'"
                                            v-model="form.swish"
                                            :rules="[phoneValidator]"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Vat" />
                                        <VTextField
                                            :disabled="role === 'User'"
                                            v-model="form.vat"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Payout number" />
                                        <VTextField
                                            disabled
                                            v-model="form.payout_number"
                                        />
                                    </div>
                                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Signatur" />
                                        <div class="d-flex align-center gap-4">
                                            <div style="width: 50%;">
                                                <VImg :src="signature" width="100%" height="112.5" aspect-ratio="16/9" class="border rounded" />
                                            </div>
                                            <div class="d-flex flex-column gap-2" style="width: 50%;" v-if="role !== 'User'">
                                                <VBtn 
                                                    class="btn-light w-auto" 
                                                    block
                                                    @click="isConfirmChangeSignatureVisible = true"
                                                >
                                                    <VIcon icon="custom-upload" size="24" />
                                                    Ladda upp fil
                                                </VBtn>
                                                <VBtn 
                                                    class="w-auto" 
                                                    @click="openSignaturePadDialog">
                                                    <VIcon icon="custom-pencil" size="24" />
                                                    Rita signatur
                                                </VBtn>
                                            </div>
                                        </div>
                                    </div>

                                    <VCardText class="p-0 d-flex w-100 my-8" v-if="role !== 'User'">
                                        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'"/>
                                        <div class="d-flex mb-4" :class="windowWidth < 1024 ? 'w-100 gap-2' : 'gap-4'">
                                            <VBtn 
                                                type="submit" 
                                                :block="windowWidth < 1024"
                                                class="btn-gradient"
                                                :class="windowWidth < 1024 ? 'w-40' : 'w-auto'"
                                            >
                                                Nästa
                                            </VBtn>
                                        </div>
                                    </VCardText>
                                </div>
                            </VForm>
                        </VCol>
                    </VCol>
                </VRow>
            </VCardText>
        </VCol>
    </VRow>

     <!-- 👉 Confirm change logo -->
    <VDialog
      v-model="isConfirmChangeLogoVisible"
      persistent
      class="action-dialog" 
    >
        <!-- Dialog close btn -->
        <VBtn
            icon
            class="btn-white close-btn"
            @click="isConfirmChangeLogoVisible = !isConfirmChangeLogoVisible"
            >
            <VIcon size="16" icon="custom-close" />
        </VBtn>
        
        <!-- Dialog Content -->
        <VCard>
            <VCardText class="dialog-title-box mt-2">
                <div class="dialog-title">Byt logotyp</div>
            </VCardText>
        
            <VCardText class="p-0">
                <div 
                    class="ms-6"
                    :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                    :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                >
                    Logotypen du väljer kommer att visas på din faktura och dina kontrakt.
                </div>
            </VCardText>
            <VCardText class="d-flex flex-column gap-2">
                <div 
                    :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                    :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                >
                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px); margin: auto;'">
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

                    </div>
                    <div class="mt-6" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Logotyp" />
                        <div class="d-flex flex-wrap gap-2">
                            <VIcon size="32" icon="custom-camera" />
                            <VFileInput 
                                v-model="filename"
                                class="mb-2"
                                accept="image/png, image/jpeg, image/bmp, image/webp"
                                prepend-icon=""
                                @change="onImageSelected"
                                @click:clear="resetAvatar"
                            />
                        </div>
                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Tillåtna format JPG, GIF, PNG." />
                    </div>
                </div>
            </VCardText>

            <VCardText class="p-0 d-flex">
                <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'"/>
                <div class="d-flex justify-end gap-3 flex-wrap dialog-actions w-100" :class="windowWidth < 1024 ? 'gap-2' : 'gap-4 mb-6 me-6'">
                    <VBtn
                        class="btn-light"
                        :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
                        :block="windowWidth < 1024"
                        @click="isConfirmChangeLogoVisible = false"
                        >
                        Avbryt
                    </VBtn>
                    <VBtn 
                        :block="windowWidth < 1024"
                        class="btn-gradient"
                        :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
                        @click="cropImage"
                    >
                        Spara
                    </VBtn>
                </div>
            </VCardText>
        </VCard>
    </VDialog>

    <!-- 👉 Confirm change Signature -->
    <VDialog
      v-model="isConfirmChangeSignatureVisible"
      persistent
      class="action-dialog" 
    >
        <!-- Dialog close btn -->
        <VBtn
            icon
            class="btn-white close-btn"
            @click="isConfirmChangeSignatureVisible = !isConfirmChangeSignatureVisible"
            >
            <VIcon size="16" icon="custom-close" />
        </VBtn>
        
        <!-- Dialog Content -->
        <VCard>
            <VCardText class="dialog-title-box mt-2">
                <div class="dialog-title">Byt firma</div>
            </VCardText>

            <VCardText class="p-0">
                <div 
                    class="ms-6"
                    :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                    :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                >
                    Firma du väljer kommer att visas på dina kontrakt.
                </div>
            </VCardText>

            <VCardText class="d-flex flex-column gap-2">
                <div 
                    :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'"
                    :style="windowWidth >= 1024 ? 'gap: 24px;' : 'gap: 16px;'"
                >
                    <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                        <Cropper
                            v-if="signatureCropped"
                            :ref="el => cropperSignature = el"
                            class="cropper-container"
                            preview-class="cropper-preview"
                            background-class="cropper-background"
                            :src="signatureCropped"
                            :stencil-props="{
                                aspectRatio: 16/9  // Puedes ajustar el aspect ratio si lo necesitas
                            }"
                        />
                    </div>
                    <div class="mt-6" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Firma" />
                        <div class="d-flex flex-wrap gap-2">
                            <VIcon size="32" icon="custom-camera" />
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

            <VCardText class="p-0 d-flex">
                <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'"/>
                <div class="d-flex justify-end gap-3 flex-wrap dialog-actions w-100" :class="windowWidth < 1024 ? 'gap-2' : 'gap-4 mb-6 me-6'">
                    <VBtn
                        class="btn-light"
                        :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
                        :block="windowWidth < 1024"
                        @click="isConfirmChangeSignatureVisible = false"
                        >
                        Avbryt
                    </VBtn>
                    <VBtn 
                        :block="windowWidth < 1024"
                        class="btn-gradient"
                        :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
                        @click="cropSignatureImage"
                    >
                        Spara
                    </VBtn>
                </div>
            </VCardText>
        </VCard>
    </VDialog>

    <!-- ======================================================= -->
    <!-- DIÁLOGO PARA DIBUJAR LA FIRMA DEL SUPPLIER -->
    <!-- ======================================================= -->
    <VDialog 
        v-model="isSignaturePadDialogVisible" 
        persistent 
        class="action-dialog" 
    >
        <!-- Dialog close btn -->
        <VBtn
            icon
            class="btn-white close-btn"
            @click="isSignaturePadDialogVisible = !isSignaturePadDialogVisible"
            >
            <VIcon size="16" icon="custom-close" />
        </VBtn>

        <!-- Dialog Content -->
        <VCard>
            <VCardText class="dialog-title-box mt-2">
                <div class="dialog-title">Rita din signatur</div>
            </VCardText>

            <VCardText>
                <div class="signature-pad-wrapper">
                    <canvas ref="signaturePadCanvas"></canvas>
                </div>
            </VCardText>

            <VCardText class="p-0 d-flex">
                <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'"/>
                <div class="d-flex justify-end gap-3 flex-wrap dialog-actions w-100" :class="windowWidth < 1024 ? 'gap-2' : 'gap-4 mb-6 me-6'">
                    <VBtn
                        class="btn-light"
                        :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
                        :block="windowWidth < 1024"
                        @click="isSignaturePadDialogVisible = false"
                        >
                        Avbryt
                    </VBtn>
                    <VBtn
                        class="btn-light"
                        :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
                        :block="windowWidth < 1024"
                        @click="clearSignaturePad"
                        >
                        Rensa
                    </VBtn>
                    <VBtn 
                        :block="windowWidth < 1024"
                        class="btn-gradient"
                        :class="windowWidth < 1024 ? 'w-100' : 'w-auto'"
                        @click="saveSignatureFromPad"
                    >
                        Acceptera & Spara
                    </VBtn>
                </div>
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
        border: dashed 1px #57F287
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
        border: solid 1px #f5f5f5 !important;
        background-color: transparent !important;
        color: #f5f5f5 !important;
    }
    .v-btn.btn-white-logo .v-btn__content {
        z-index: 0;
        color: #f5f5f5 !important;
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

    .banner-img {
        width: 100%;
        height: 170px;
        border-radius: 10px !important;
    }

    .info-logo-store {
        display: flex;
        padding-left: 14rem !important;
    }

    .info-store {
        padding-left: 0;
        padding-right: 0;
        padding-top: 0;
        padding-bottom: 0;
    }

    .logo-store {
        margin-top: -12%;
        left: 3%;
        z-index: 9999;
    }
    
    .logo-store-img {
        width: 173.96px;
        height: 173.96px;
        max-width: 173.96px;
        border-radius: 16px;
        background-color: #F5F5F5;
        border-radius: 50% !important;
        object-fit: cover;
        box-shadow: 0 4px 24px 0 rgba(0,0,0,0.18), 0 1.5px 6px 0 rgba(0,0,0,0.15);
    }

    .logo-button {
        top: 3.5%;
        right: 3%;
        z-index: 9999;
        position: absolute;
    }

    .tw-bg-tertiary {
        opacity: 1 !important;
        /*background-color: #0A1B33 !important*/
    }

    .store-name {
        font-size: 24px;
        font-style: normal;
        font-weight: 600;
        line-height: 24px;
        color: rgba(var(--v-theme-on-background), var(--v-high-emphasis-opacity));
    }

    .store-address {
        font-size: 16px;
        font-style: normal;
        font-weight: 600;
        line-height: 16px;
        color: rgba(var(--v-theme-on-background), var(--v-high-emphasis-opacity));
        margin-inline-start: 20px;
    }
    .signature-pad-wrapper {
    border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
    border-radius: 4px;
    background-color: #fff; /* Fondo blanco para el lienzo */
    }

    .signature-pad-wrapper canvas {
    width: 100%;
    height: 200px;
    display: block;
    cursor: crosshair;
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
            margin-top: -28%;
            left:5%;
        }

        .logo-button {
            top: 2.5%;
            right: 3%;
            z-index: 9999;
            position: absolute;
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