<script setup>

import { avatarText } from '@/@core/utils/formatters'
import { emailValidator, requiredValidator, phoneValidator,urlValidator, minLengthDigitsValidator } from '@/@core/utils/validators'
import { useAuthStores } from '@/stores/useAuth'
import { useProfileStores } from '@/stores/useProfile'
import { Cropper } from 'vue-advanced-cropper'
import { themeConfig } from '@themeConfig'
import logo_ from '@images/logos/favicon@2x.png';
import banner from '@images/logos/banner.jpeg'
import 'vue-advanced-cropper/dist/style.css'
import SignaturePad from 'signature_pad';
import { nextTick } from 'vue';

const authStores = useAuthStores()
const profileStores = useProfileStores()

const refVForm = ref()
const user_id = ref('')
const email = ref('')
const name = ref('')
const last_name = ref('')
const phone = ref('')
const address = ref('')
const avatar = ref('')

const avatarOld = ref('')
const isRequestOngoing = ref(false)

const currentTab = ref(0)
const controlledTab = computed({
    get: () => currentTab.value,
    set: async (nextTab) => {
        // Always allow going back to Tab 0 without validation and clear any alert
        if (nextTab === 0) {
            currentTab.value = 0
            if (alert.value.show) {
                alert.value.show = false
                alert.value.message = ''
            }
            return
        }
        // Always allow navigating back or staying on the same tab
        if (nextTab <= currentTab.value) {
            currentTab.value = nextTab
            return
        }

        // If attempting to go to Tab 1, validate only Tab 0 fields first
        if (nextTab === 1) {
            const isTab0Valid = await validateTab0()
            if (isTab0Valid) {
                currentTab.value = nextTab
            } else {
    
                alert.value.type = 'error'
                alert.value.message = 'Komplettera fälten på första fliken innan du fortsätter.'
                alert.value.show = true
                await refVForm.value?.validate()
                // Auto-hide after a short delay
                setTimeout(() => {
                    alert.value.show = false
                    alert.value.message = ''
                }, 1000)
            }
            return
        }

        // Default: allow
        currentTab.value = nextTab
    },
})

// Validate only the fields belonging to Tab 0
const validateTab0 = async () => {
    const results = []

    results.push(requiredValidator(name.value))
    results.push(requiredValidator(last_name.value))
    results.push(requiredValidator(email.value))
    results.push(emailValidator(email.value))
    results.push(requiredValidator(phone.value))
    results.push(phoneValidator(phone.value))
    results.push(requiredValidator(address.value))

    // All validators from Vuetify return true or a string message
    const isValid = results.every(r => r === true)
    return isValid
}

// Validate only the fields belonging to Tab 1 (respecting disabled fields by role)
const validateTab1 = async () => {
    const results = []

    const isUser = role.value === 'User'
    const orgDisabled = role.value === 'Supplier' || role.value === 'User'

    if (!isUser) results.push(requiredValidator(form.value.company))

    if (!orgDisabled) {
        results.push(requiredValidator(form.value.organization_number))
        results.push(minLengthDigitsValidator(10)(form.value.organization_number))
    }

    if (!isUser) results.push(requiredValidator(form.value.address))

    if (!isUser) results.push(requiredValidator(form.value.postal_code))

    if (!isUser) results.push(requiredValidator(form.value.street))

    if (!isUser) {
        results.push(requiredValidator(form.value.phone))
        results.push(phoneValidator(form.value.phone))
    }

    if (form.value.link) results.push(urlValidator(form.value.link))

    if (!isUser) results.push(requiredValidator(form.value.bank))

    if (!isUser) results.push(requiredValidator(form.value.account_number))

    if (form.value.swish) results.push(phoneValidator(form.value.swish))


    const isValid = results.every(r => r === true)
    return isValid
}

const data = ref(null)
const userData = ref(null)
const role = ref(null)

const isConfirmChangeLogoVisible = ref(false)
const cropper = ref()

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
    vat: ''
})

const tabs = [
  {
    icon: 'tabler-lock',
    title: 'Grundläggande information',
  },
  {
    icon: 'tabler-building-store',
    title: 'Företagsinformation',
  },
]

const alert = ref({
  message: '',
  show: false,  
  type: '',
})

watchEffect(fetchData)

async function fetchData() {

    isRequestOngoing.value = true

    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    data.value = await authStores.company()
    role.value = userData.value.roles[0].name

    user_id.value = userData.value.id
    email.value = userData.value.email
    name.value = userData.value. name
    last_name.value = userData.value.last_name
    phone.value = userData.value.user_detail ? userData.value.user_detail.personal_phone : ""
    address.value = userData.value.user_detail ? userData.value.user_detail.personal_address : ""

     //company
    form.value.company = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.company : (userData.value.user_detail ? userData.value.user_detail.company : "")
    form.value.organization_number = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.organization_number : (userData.value.user_detail ? userData.value.user_detail.organization_number : "")
    form.value.link = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.link : (userData.value.user_detail ? userData.value.user_detail.link : "")
    form.value.address = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.address : (userData.value.user_detail ? userData.value.user_detail.address : "")
    form.value.street = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.street : (userData.value.user_detail ? userData.value.user_detail.street : "")
    form.value.postal_code = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.postal_code :  (userData.value.user_detail ? userData.value.user_detail.postal_code : "")
    form.value.phone = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.phone : (userData.value.user_detail ? userData.value.user_detail.phone : "")

    //bank
    form.value.bank = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.bank : (userData.value.user_detail ? userData.value.user_detail.bank : "")
    form.value.account_number = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.account_number : (userData.value.user_detail ? userData.value.user_detail.account_number : "")

    form.value.iban = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.iban : (userData.value.user_detail ? userData.value.user_detail.iban : "")
    form.value.iban_number = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.iban_number : (userData.value.user_detail ? userData.value.user_detail.iban_number : "")
    form.value.bic = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.bic : (userData.value.user_detail ? userData.value.user_detail.bic : "")
    form.value.plus_spin = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.plus_spin : (userData.value.user_detail ? userData.value.user_detail.plus_spin : "")
    form.value.swish = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.swish : (userData.value.user_detail ? userData.value.user_detail.swish : "")
    form.value.vat = role.value === 'User' ? userData.value.supplier.boss.user.user_detail.vat : (userData.value.user_detail ? userData.value.user_detail.vat : "")

    logo.value = 
        role.value === 'User' ?
        (userData.value.supplier.boss.user.user_detail.logo !== null) ? themeConfig.settings.urlStorage + userData.value.supplier.boss.user.user_detail.logo : logo_  :
        (userData.value.user_detail && userData.value.user_detail.logo !== null) ? themeConfig.settings.urlStorage + userData.value.user_detail.logo : logo_ 
    logoCropped.value = 
        role.value === 'User' ?
        (userData.value.supplier.boss.user.user_detail.logo !== null) ? await fetchImageAsBlob(themeConfig.settings.urlStorage + userData.value.supplier.boss.user.user_detail.logo) : logo_  :
        (userData.value.user_detail && userData.value.user_detail.logo !== null) ? await fetchImageAsBlob(themeConfig.settings.urlStorage + userData.value.user_detail.logo) : logo_ 

    signature.value = 
        role.value === 'User' ?
        (userData.value.supplier.boss.user.user_detail.img_signature !== null) ? themeConfig.settings.urlStorage + userData.value.supplier.boss.user.user_detail.img_signature : logo_  :
        (userData.value.user_detail &&  userData.value.user_detail.img_signature !== null) ? themeConfig.settings.urlStorage + userData.value.user_detail.img_signature : logo_ 
    signatureCropped.value = 
        role.value === 'User' ?
        (userData.value.supplier.boss.user.user_detail.img_signature !== null) ? await fetchImageAsBlob(themeConfig.settings.urlStorage + userData.value.supplier.boss.user.user_detail.img_signature) : logo_  :
        (userData.value.user_detail && userData.value.user_detail.img_signature !== null) ? await fetchImageAsBlob(themeConfig.settings.urlStorage + userData.value.user_detail.img_signature) : logo_
        
    avatarOld.value = userData.value.avatar
    avatar.value = userData.value.avatar

    isRequestOngoing.value = false

}

const fetchImageAsBlob = async (url) => {
  const response = await  fetch(themeConfig.settings.urlbase + 'proxy-image?url=' + url);
  const blob = await response.blob();
  return URL.createObjectURL(blob);
}

const formatOrgNumber = () => {

  let numbers = form.value.organization_number.replace(/\D/g, '')
  if (numbers.length > 4) {
      numbers = numbers.slice(0, -4) + '-' + numbers.slice(-4)
  }
  form.value.organization_number = numbers
}

const resetLogo = () => {
  logoCropped.value = null
  logoOld.value = null
}

const resetAvatar = () => {
  avatar.value = null
}

const handleSubmit = () => {
  return (role.value === 'Supplier' || role.value === 'User')
    ? onSubmit()
    : submitCompleteProfile()
}

const submitCompleteProfile = async () => {

  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      let formData = new FormData()
            
      formData.append('user_id', user_id.value)
      formData.append('email', email.value)
      formData.append('name', name.value)
      formData.append('last_name', last_name.value)
      formData.append('personal_phone', phone.value)
      formData.append('personal_address', address.value)
      formData.append('image', avatarOld.value)

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

      profileStores.updateData(formData)
        .then(response => {    

          window.scrollTo(0, 0)
          
          alert.value.type = 'success'
          alert.value.message = 'Personlig information uppdaterad. Sidan kommer automatiskt att laddas om för att observera ändringarna...!'
          alert.value.show = true
          
          localStorage.setItem('user_data', JSON.stringify(response.user_data))
          
          setTimeout(() => {
            alert.value.show = false,
            alert.value.message = ''
            location.reload()
          }, 1000)

          isRequestOngoing.value = false

        }).catch(error => {
          alert.value.type = 'error'
          alert.value.show = true
          alert.value.message = 'Ett fel har inträffat...! (Serverfel)'
          
          isRequestOngoing.value = false
          
          setTimeout(() => {
            alert.value.show = false,
            alert.value.message = ''
          }, 1000) 
        })

      }
    })
} 

const onSubmit = async () =>{
  if (currentTab.value === 0) {
    const isTab0Valid = await validateTab0()
    if (isTab0Valid) {
      currentTab.value = 1
    } else {
      alert.value.type = 'error'
      alert.value.message = 'Komplettera fälten på första fliken innan du fortsätter.'
      alert.value.show = true
      await refVForm.value?.validate()
      setTimeout(() => {
        alert.value.show = false
        alert.value.message = ''
      }, 1000)
    }
    return
  }

  if (currentTab.value === 1) {
    const [isTab0Valid, isTab1Valid] = await Promise.all([
      validateTab0(),
      validateTab1(),
    ])

    if (isTab0Valid && isTab1Valid) {
      await submitCompleteProfile()
    } else {
      alert.value.type = 'error'
      alert.value.message = !isTab0Valid
        ? 'Komplettera fälten på första fliken innan du fortsätter.'
        : 'Komplettera fälten på andra fliken innan du fortsätter.'
      alert.value.show = true
      setTimeout(() => {
        alert.value.show = false
        alert.value.message = ''
      }, 1000)
    }
  }
}

const onImageSelected = event => {
  const file = event.target.files[0]

  if (!file) return
  // avatarOld.value = file

  URL.createObjectURL(file)

  resizeImage(file, 400, 400, 0.9)
    .then(async blob => {
      avatarOld.value = blob
      let r = await blobToBase64(blob)
      avatar.value = 'data:image/jpeg;base64,' + r
    })
}

const onLogoSelected = event => {
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

const onCropChange = (coordinates) => {
    // console.log('coordinates', coordinates)
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
        advisor.value.message = 'Ett fel har inträffat...! (Serverfel)';
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

const clearSignaturePad = () => {
  if (signaturePadInstance.value) {
    signaturePadInstance.value.clear();
  }
};

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
</script>

<template>
  <VRow class="justify-center m-0">

    <VDialog
      v-model="isRequestOngoing"
      width="auto"
      persistent>
      <VProgressCircular
        indeterminate
        color="primary"
        class="mb-0"/>
    </VDialog>

    <VCol
      cols="12"
      md="8"
    >
      <h1 class="text-center mb-5">
        Komplett användarprofil
      </h1>

      <VRow>
        <VCol 
          v-if="alert.show" 
          cols="12" 
        >
          <VAlert
            v-if="alert.show"
            :type="alert.type"
          >
            {{ alert.message }}
          </VAlert>
        </VCol>
        <VCol cols="12">
          <VTabs
            v-if="role === 'Supplier' || role === 'User'"
            v-model="controlledTab"
            grow
          >
            <VTab
              v-for="tab in tabs"
              :key="tab.icon"
            >
              <VIcon
                :size="18"
                :icon="tab.icon"
                class="me-1"
              />
              <span>{{ tab.title }}</span>
            </VTab>
          </VTabs>
          <VForm
            ref="refVForm"
            @submit.prevent="handleSubmit"
          >
          <template v-if="role === 'Supplier' || role === 'User'">
            <VWindow
              v-model="controlledTab"
              class="disable-tab-transition mt-3"
              :touch="false"
            >
              <VWindowItem>
                <VCardText class="d-block d-md-flex">
                  <VAvatar
                    rounded
                    size="100"
                    class="me-6"
                    :color="avatar ? 'default' : 'primary'"
                    variant="tonal"
                  >
                    <VImg
                      v-if="avatar"
                      style="border-radius: 6px;"
                      :src="avatar"
                    />
                    <span
                      v-else
                      class="text-5xl font-weight-semibold"
                    >
                      {{ avatarText(name) }}
                    </span>
                  </VAvatar>

                  <!-- 👉 Upload Photo -->
                  <div class="d-flex flex-column justify-center gap-4 mt-4">
                    <div class="d-flex flex-wrap gap-2">
                      <VFileInput                          
                        label="Avatar"
                        accept="image/png, image/jpeg, image/bmp"
                        placeholder="Avatar"
                        prepend-icon="tabler-camera"
                        @change="onImageSelected"
                        @click:clear="resetAvatar"
                      />
                    </div>
                    <p class="text-body-1 mb-0">
                      Tillåtna format JPG, GIF, PNG.
                    </p>
                  </div>
                </VCardText>

                <VDivider />

                <VCardText class="pt-2 mt-6">
                  <VRow>
                    <VCol
                      md="6"
                      cols="12"
                    >
                      <VTextField
                        v-model="name"
                        label="Namn"
                        :rules="[requiredValidator]"
                      />
                    </VCol>
                    <VCol
                      md="6"
                      cols="12"
                    >
                      <VTextField
                        v-model="last_name"
                        label="Efternamn"
                        :rules="[requiredValidator]"
                      />
                    </VCol>
                    <VCol
                      cols="12"
                      md="6"
                    >
                      <VTextField
                        v-model="email"
                        label="E-post"
                        type="email"
                        :rules="[requiredValidator, emailValidator]"
                        disabled
                      />
                    </VCol>
                    <VCol
                      cols="12"
                      md="6"
                    >
                      <VTextField
                        v-model="phone"
                        type="tel"
                        label="Telefon"
                        placeholder="+(XX) XXXXXXXXX"
                        :rules="[phoneValidator, requiredValidator]"
                      />
                    </VCol>
                    <VCol
                      cols="12"
                      md="12"
                    >
                      <VTextarea
                        v-model="address"
                        rows="3"
                        label="Adress"
                        :rules="[requiredValidator]"
                      />
                    </VCol>
                    <!-- 👉 Form Actions -->
                  
                  </VRow>
                </VCardText>
              </VWindowItem>
              <VWindowItem>
                <VCard class="mx-5">
                  <VCardText class="p-0">
                      <VImg :src="banner" class="banner-img" cover/>
                  </VCardText>

                  <VCardText class="tw-bg-tertiary p-0">
                      <VRow no-gutters>
                          <VCol cols="12" md="12" class="d-flex col-logo">
                              <div class="logo-store">
                                  <VImg v-if="role === 'User'" :src="logo" class="logo-store-img" contain/>
                                  <VBadge 
                                      v-else
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
                <VCardText class="pt-2 mt-6">
                  <VRow>
                    <VCol cols="12" md="8">
                        <VTextField
                            :disabled="role === 'User'"
                            v-model="form.company"
                            label="Företagsnamn"
                            :rules="[requiredValidator]"
                        />
                    </VCol>
                    <VCol cols="12" md="4">
                        <VTextField
                            v-model="form.organization_number"
                            label="Organisationsnummer"
                            :disabled="role === 'Supplier' || role === 'User'"
                            :rules="[requiredValidator, minLengthDigitsValidator(10)]"
                            minLength="11"
                            maxlength="11"
                            @input="formatOrgNumber()"
                        />
                    </VCol>
                    <VCol cols="12" md="12">
                        <VTextarea
                            :disabled="role === 'User'"
                            v-model="form.address"
                            rows="3"
                            :rules="[requiredValidator]"
                            label="Adress"
                        />
                    </VCol>
                    <VCol cols="12" md="6">
                        <VTextField
                            :disabled="role === 'User'"
                            v-model="form.postal_code"
                            :rules="[requiredValidator]"
                            label="Postnummer"
                        />
                    </VCol>
                    <VCol cols="12" md="6">
                        <VTextField
                            :disabled="role === 'User'"
                            v-model="form.street"
                            :rules="[requiredValidator]"
                            label="Stad"
                        />
                    </VCol>                            
                    <VCol cols="12" md="6">
                        <VTextField
                            :disabled="role === 'User'"
                            v-model="form.phone"
                            :rules="[requiredValidator, phoneValidator]"
                            label="Telefon"
                        />
                    </VCol>
                    <VCol cols="12" md="6">
                        <VTextField
                            :disabled="role === 'User'"
                            v-model="form.link"
                            :rules="[urlValidator]"
                            label="Hemsida"
                        />
                    </VCol>
                    <VCol cols="12" md="6">
                        <VTextField
                            :disabled="role === 'User'"
                            v-model="form.bank"
                            :rules="[requiredValidator]"
                            label="Bank"
                        />
                    </VCol>
                    <VCol cols="12" md="6">
                        <VTextField
                            :disabled="role === 'User'"
                            v-model="form.iban"
                            label="Bankgiro"
                        />
                    </VCol>
                    <VCol cols="12" md="6">
                        <VTextField
                            :disabled="role === 'User'"
                            v-model="form.account_number"
                            :rules="[requiredValidator]"
                            label="Kontonummer"
                        />
                    </VCol>
                    <VCol cols="12" md="6">
                        <VTextField
                            :disabled="role === 'User'"
                            v-model="form.iban_number"
                            label="Iban nummer"
                        />
                    </VCol>
                    <VCol cols="12" md="6">
                        <VTextField
                            :disabled="role === 'User'"
                            v-model="form.bic"
                            label="BIC"
                        />
                    </VCol>
                    <VCol cols="12" md="6">
                        <VTextField
                            :disabled="role === 'User'"
                            v-model="form.plus_spin"
                            label="Plusgiro"
                        />
                    </VCol>
                    <VCol cols="12" md="6">
                        <VTextField
                            :disabled="role === 'User'"
                            v-model="form.swish"
                            label="Swish"
                            :rules="[phoneValidator]"
                        />
                    </VCol>
                    <VCol cols="12" md="6">
                        <VTextField
                            :disabled="role === 'User'"
                            v-model="form.vat"
                            label="Vat"
                        />
                    </VCol>
                    <VCol cols="12" md="6" v-if="role !== 'User'">
                      <VLabel class="mb-2">Signatur</VLabel>
                      <div class="d-flex align-center gap-4">
                          <VImg :src="signature" width="200" height="112.5" aspect-ratio="16/9" class="border rounded" />
                          <div class="d-flex flex-column gap-2">
                              <VBtn 
                                  color="primary"
                                  @click="isConfirmChangeSignatureVisible = true">
                                  <VIcon icon="tabler-upload" class="mr-2" />
                                  Ladda upp fil
                              </VBtn>
                              <VBtn 
                                  color="secondary"
                                  variant="tonal"
                                  @click="openSignaturePadDialog">
                                  <VIcon icon="tabler-pencil" class="mr-2" />
                                  Rita signatur
                              </VBtn>
                          </div>
                      </div>
                    </VCol>
                </VRow>
                </VCardText>
              </VWindowItem>
            </VWindow>
            <VCardText>
              <VRow>
                <VCol
                  cols="12" md="12"
                  class="d-flex flex-wrap gap-4 justify-buttons"
                >
                <VBtn type="submit" class="w-100 w-md-auto">
                  {{ (currentTab === 1) ? 'Kom igång' : ' Nästa' }}
                </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </template>
          <template v-else>
            <VCardText class="d-block d-md-flex">
              <VAvatar
                rounded
                size="100"
                class="me-6"
                :color="avatar ? 'default' : 'primary'"
                variant="tonal"
              >
                <VImg
                  v-if="avatar"
                  style="border-radius: 6px;"
                  :src="avatar"
                />
                <span
                  v-else
                  class="text-5xl font-weight-semibold"
                >
                  {{ avatarText(name) }}
                </span>
              </VAvatar>

              <!-- 👉 Upload Photo -->
              <div class="d-flex flex-column justify-center gap-4 mt-4">
                <div class="d-flex flex-wrap gap-2">
                  <VFileInput                          
                    label="Avatar"
                    accept="image/png, image/jpeg, image/bmp"
                    placeholder="Avatar"
                    prepend-icon="tabler-camera"
                    @change="onImageSelected"
                    @click:clear="resetAvatar"
                  />
                </div>
                <p class="text-body-1 mb-0">
                  Tillåtna format JPG, GIF, PNG.
                </p>
              </div>
            </VCardText>

            <VDivider />

            <VCardText class="pt-2 mt-6">
              <VRow>
                <VCol
                  md="6"
                  cols="12"
                >
                  <VTextField
                    v-model="name"
                    label="Namn"
                    :rules="[requiredValidator]"
                  />
                </VCol>
                <VCol
                  md="6"
                  cols="12"
                >
                  <VTextField
                    v-model="last_name"
                    label="Efternamn"
                    :rules="[requiredValidator]"
                  />
                </VCol>
                <VCol
                  cols="12"
                  md="6"
                >
                  <VTextField
                    v-model="email"
                    label="E-post"
                    type="email"
                    :rules="[requiredValidator, emailValidator]"
                    disabled
                  />
                </VCol>
                <VCol
                  cols="12"
                  md="6"
                >
                  <VTextField
                    v-model="phone"
                    type="tel"
                    label="Telefon"
                    placeholder="+(XX) XXXXXXXXX"
                    :rules="[phoneValidator, requiredValidator]"
                  />
                </VCol>
                <VCol
                  cols="12"
                  md="12"
                >
                  <VTextarea
                    v-model="address"
                    rows="3"
                    label="Adress"
                    :rules="[requiredValidator]"
                  />
                </VCol>
                <!-- 👉 Form Actions -->
              
              </VRow>
            </VCardText>

            <VCardText>
              <VRow>
                <VCol
                  cols="12" md="12"
                  class="d-flex flex-wrap gap-4 justify-buttons"
                >
                <VBtn type="submit" class="w-100 w-md-auto">
                   Kom igång
                </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </template>
          </VForm>
        </VCol>
      </VRow>
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
                      @change="onLogoSelected"
                      @click:clear="resetLogo"
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

  <!-- 👉 Confirm change Signature -->
  <VDialog
    v-model="isConfirmChangeSignatureVisible"
    persistent
    class="v-dialog-sm" >
    <!-- Dialog close btn -->
      
    <DialogCloseBtn @click="isConfirmChangeSignatureVisible = !isConfirmChangeSignatureVisible" />

    <!-- Dialog Content -->
    <VCard title="Byt firma">
      <VDivider class="mt-4"/>
      <VCardText>
        Firma du väljer kommer att visas på dina kontrakt.
      </VCardText>
      <VCardText class="d-flex flex-column gap-2">
            <VRow>
              <VCol cols="12" md="12">
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
              </VCol>
              <VCol cols="12" md="12">
                    <VFileInput 
                      v-model="signatureFilename"
                      label="Firma"
                      class="mb-2"
                      accept="image/png, image/jpeg, image/bmp, image/webp"
                      prepend-icon="tabler-camera"
                      @change="onSignatureImageSelected"
                      @click:clear="resetSignature"
                  />
              </VCol>
          </VRow>
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap">
        <VBtn
          color="secondary"
          variant="tonal"
          @click="isConfirmChangeSignatureVisible = false">
            Avbryt 
        </VBtn>
        <VBtn @click="cropSignatureImage"> 
            Spara
        </VBtn>
      </VCardText>
    </VCard>
  </VDialog>

  <!-- ======================================================= -->
  <!-- DIÁLOGO PARA DIBUJAR LA FIRMA DEL SUPPLIER -->
  <!-- ======================================================= -->
  <VDialog v-model="isSignaturePadDialogVisible" persistent max-width="500">
    <VCard>
        <VCardTitle>Rita din signatur</VCardTitle>
        <VCardText>
        <div class="signature-pad-wrapper">
            <canvas ref="signaturePadCanvas"></canvas>
        </div>
        </VCardText>
        <VCardActions>
        <VSpacer />
        <VBtn color="secondary" variant="tonal" @click="isSignaturePadDialogVisible = false">Avbryt</VBtn>
        <VBtn @click="clearSignaturePad">Rensa</VBtn>
        <VBtn color="primary" @click="saveSignatureFromPad">Acceptera & Spara</VBtn>
        </VCardActions>
    </VCard>
  </VDialog>
</template>

<style lang="scss">
  .m-0 {
    margin: 0;
  }

  .justify-buttons {
    justify-content: right !important;

    @media (max-width: 767px) {
      justify-content: center !important;
    }
  }
</style>

<style scoped>

    :deep(.vue-simple-handler) {
        background: #9966FF !important;
    }
    :deep(.cropper-preview-circle) {
        border: dashed 1px #9966FF
    }
    :deep(.cropper-background),
    :deep(.vue-advanced-cropper__foreground) {
        background-color: transparent !important;
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

<route lang="yaml">
  meta:
    layout: blank
    action: view
    subject: Auth
    parar: true
</route>
