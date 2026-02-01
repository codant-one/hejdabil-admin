<script setup>

import { useDisplay } from "vuetify";
import { nextTick } from 'vue';
import { requiredValidator, phoneValidator, urlValidator, minLengthDigitsValidator, emailValidator } from '@/@core/utils/validators'
import { useConfigsStores } from '@/stores/useConfigs'
import { themeConfig } from '@themeConfig'
import { Cropper } from 'vue-advanced-cropper'
import banner from '@images/logos/banner2.jpg'
import SignaturePad from 'signature_pad';
import router from '@/router'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";
import 'vue-advanced-cropper/dist/style.css'

const configsStores = useConfigsStores()
const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => (mdAndDown.value ? "" : "top end"));
const sectionEl = ref(null);

const avatar = ref('')
const avatarOld = ref('')
const userData = ref(null)
const role = ref(null)
const isRequestOngoing = ref(false)
const isFormEdited = ref(false);
const dialog = ref(false);
let nextRoute = null;

const company = ref(null)
const refVForm = ref()

const isConfirmChangeLogoVisible = ref(false)
const cropper = ref()

const signatureData = ref(null) // Para guardar los datos de la firma
const isConfirmChangeSignatureVisible = ref(false)
const cropperSignature = ref()
const signature = ref(null)
const signatureCropped = ref(null)
const signatureOld = ref(null)
const signatureFilename = ref([])

const isSignaturePadDialogVisible = ref(false)
const signaturePadCanvas = ref(null)
const signaturePadInstance = ref(null)

const data = ref(null)
const logoData = ref(null)
const logo = ref(null)
const logoCropped = ref(null)
const logoOld = ref(null)
const filename = ref([])

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
    vat: ''
})

const advisor = ref({
  type: '',
  message: '',
  show: false
})

onBeforeMount(() => {
  const userData = JSON.parse(localStorage.getItem('user_data') || 'null')
  const userRole = userData?.roles?.[0]?.name
  
  // Solo SuperAdmin y Administrator pueden acceder
  if (userRole !== 'SuperAdmin' && userRole !== 'Administrator') {
    router.push({ name: 'not-authorized' })
  }
})

onBeforeRouteLeave((to, from, next) => {
  if (isFormEdited.value) {
    dialog.value = true;
    nextRoute = next;
  } else {
    next();
  }
});

watch(form.value, () => {
    showWindow(true)
}, { deep: true });

watchEffect(fetchData)

async function fetchData() { 

    isRequestOngoing.value = true
    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')

    avatarOld.value = userData.value.avatar
    avatar.value = userData.value.avatar
    role.value = userData.value.roles[0].name

    await configsStores.getFeature('company')
    data.value = configsStores.getFeaturedConfig('company')
    
    //company
    form.value.company = data.value.company
    form.value.email = data.value.email     
    form.value.name = data.value.name
    form.value.last_name = data.value.last_name
    form.value.organization_number = data.value.organization_number
    form.value.link = data.value.link
    form.value.address = data.value.address
    form.value.street = data.value.street
    form.value.postal_code = data.value.postal_code
    form.value.phone = data.value.phone

    //bank
    form.value.bank = data.value.bank
    form.value.account_number = data.value.account_number

    form.value.iban = data.value.iban
    form.value.iban_number = data.value.iban_number
    form.value.bic = data.value.bic
    form.value.plus_spin = data.value.plus_spin
    form.value.swish = data.value.swish
    form.value.vat = data.value.vat

    await configsStores.getFeature('logo')
    logoData.value = configsStores.getFeaturedConfig('logo')

    logo.value = (logoData.value && logoData.value.logo) ? themeConfig.settings.urlStorage + logoData.value.logo : null 
    logoCropped.value = (logoData.value && logoData.value.logo) ? await fetchImageAsBlob(themeConfig.settings.urlStorage + logoData.value.logo) : null 
    
    await configsStores.getFeature('signature')
    signatureData.value = configsStores.getFeaturedConfig('signature')

    signature.value = (signatureData.value && signatureData.value.img_signature) 
        ? themeConfig.settings.urlStorage + signatureData.value.img_signature 
        : null 
        
    signatureCropped.value = (signatureData.value && signatureData.value.img_signature) 
        ? await fetchImageAsBlob(themeConfig.settings.urlStorage + signatureData.value.img_signature) 
        : null
    
    setTimeout(() => {
        showWindow(false)
    }, 500)

    isRequestOngoing.value = false

}

const resetSignature = () => {
  signatureCropped.value = null
  signatureOld.value = null
  signatureFilename.value = [] // Limpia también el v-model del input
}

// Se ejecuta cuando el admin selecciona un archivo de imagen para la firma
const onSignatureImageSelected = event => {
  const file = event.target.files[0]
  if (!file) return

  URL.createObjectURL(file)

  // Usamos la función resizeImage que ya tienes en el componente
  resizeImage(file, 1200, 1200, 1)
    .then(async blob => {
        let r = await blobToBase64(blob)
        signatureCropped.value = 'data:image/jpeg;base64,' + r
    })
}

// Se ejecuta al guardar la imagen desde el cropper (después de subir un archivo)
const cropSignatureImage = async () => {
    if (cropperSignature.value) {
        const result = cropperSignature.value.getResult({
            mime: 'image/png',
            quality: 1,
            fillColor: 'transparent'
        });
        
        // Usamos la función dataURLtoBlob que ya tienes para convertir a archivo
        const blob = dataURLtoBlob(result.canvas.toDataURL("image/png"));

        // Preparamos los datos para enviar al backend
        let formData = new FormData()
        formData.append('img_signature', blob, 'signature.png'); // Clave 'img_signature' y nombre de archivo
        
        isConfirmChangeSignatureVisible.value = false
        isRequestOngoing.value = true

        // Creamos el payload que espera la acción del store del admin
        let data = {
            key: 'signature', // Este es el 'slug' que usaremos
            params: formData
        }

        // --- ¡ACCIÓN CLAVE! ---
        // Llamamos a la acción 'postSignature' en el store de CONFIGS
        configsStores.postSignature(data)
            .then(async response => {    
                window.scrollTo(0, 0)
                
                // Actualizamos la imagen visible en la página con la nueva firma
                let r = await blobToBase64(blob)
                signature.value = 'data:image/jpeg;base64,' + r

                advisor.value.type = 'success'
                advisor.value.message = 'Signatur uppdaterad'
                advisor.value.show = true

                setTimeout(() => {
                    advisor.value.show = false
                    advisor.value.message = ''
                }, 3000)

            }).catch(error => {
                console.log('error', error)
                advisor.value.type = 'error'
                advisor.value.show = true
                advisor.value.message = 'Ett fel har inträffat vid uppdatering av signaturen! (Serverfel)'

                setTimeout(() => {
                    advisor.value.show = false,
                    advisor.value.message = ''
                }, 5000) 
            }).finally(() => {
                isRequestOngoing.value = false;
            });           
    }
}


// Abre el diálogo del lienzo para dibujar
const openSignaturePadDialog = () => {
  isSignaturePadDialogVisible.value = true;
  nextTick(() => {
    const canvas = signaturePadCanvas.value;
    if (canvas) {
      signaturePadInstance.value = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255, 255, 255, 0)',
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

// Limpia el contenido del lienzo de dibujo
const clearSignaturePad = () => {
  if (signaturePadInstance.value) {
    signaturePadInstance.value.clear();
  }
};

// Guarda la firma dibujada en el lienzo
const saveSignatureFromPad = async () => {
  if (signaturePadInstance.value && !signaturePadInstance.value.isEmpty()) {
    const signatureDataUrl = signaturePadInstance.value.toDataURL('image/png');
    
    // Convertimos el dibujo en un archivo (Blob)
    const blob = dataURLtoBlob(signatureDataUrl);

    const formData = new FormData();
    formData.append('img_signature', blob, 'signature.png');

    isSignaturePadDialogVisible.value = false;
    isRequestOngoing.value = true;

    // Preparamos el payload para el store del admin
    let data = {
        key: 'signature',
        params: formData
    }
    
    // --- ¡ACCIÓN CLAVE! ---
    // Llamamos a la misma acción 'postSignature' en el store de CONFIGS
    configsStores.postSignature(data)
      .then(async response => {    
        window.scrollTo(0, 0);
        
        // Actualizamos la imagen visible con el dibujo
        signature.value = signatureDataUrl;

        advisor.value.type = 'success'
        advisor.value.message = 'Signatur uppdaterad'
        advisor.value.show = true

        setTimeout(() => {
            advisor.value.show = false
            advisor.value.message = ''
        }, 3000)
        
      })
      .catch(error => {
        console.log('error', error);
        advisor.value.type = 'error';
        advisor.value.show = true;
        advisor.value.message = 'Ett fel har inträffat vid uppdatering av signaturen! (Serverfel)';
        setTimeout(() => {
            advisor.value.show = false;
            advisor.value.message = '';
        }, 5000); 
      })
      .finally(() => {
        isRequestOngoing.value = false;
      });
  } else {
    // Si no se dibujó nada, solo se cierra el diálogo
    isSignaturePadDialogVisible.value = false;
  }
};

const showWindow = function(data) {
  isFormEdited.value = data
}

const confirmLeave = () => {
  dialog.value = false;
  nextRoute();
};

const cancelLeave = () => {
  dialog.value = false;
  nextRoute(false);
};


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

        let data = {
            key: 'logo',
            params: formData
        }

        configsStores.postLogo(data)
            .then(async response => {    

                window.scrollTo(0, 0)
                
                isRequestOngoing.value = false 

                let r = await blobToBase64(blob)
                logo.value = 'data:image/jpeg;base64,' + r

            }).catch(error => {
                isRequestOngoing.value = false
                console.log('error', error)
                advisor.value.type = 'error'
                advisor.value.show = true
                advisor.value.message = 'Ett serverfel uppstod. Försök igen.'

                setTimeout(() => {
                    advisor.value.show = false
                    advisor.value.message = ''
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

            isRequestOngoing.value = true 

            let data = {
                key: 'company',
                params: {
                    value: {
                        logo: logoOld.value,
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
                        vat: form.value.vat
                    }
                }
            }

            configsStores.postFeature(data)
                .then(response => {    

                    window.scrollTo(0, 0)
                    
                    isRequestOngoing.value = false

                    advisor.value.type = 'success'
                    advisor.value.message = 'Uppdaterad företagsinformation'
                    advisor.value.show = true
                    
                    fetchData()

                    setTimeout(() => {
                        advisor.value.show = false
                        advisor.value.message = ''
                    }, 5000)

                }).catch(error => {
                    isRequestOngoing.value = false

                    advisor.value.type = 'error'
                    advisor.value.show = true
                    advisor.value.message = 'Ett serverfel uppstod. Försök igen.'

                    setTimeout(() => {
                        advisor.value.show = false
                        advisor.value.message = ''
                    }, 5000) 
                })
            }
    })
}

const formatOrgNumber = () => {

    let numbers = form.value.organization_number.replace(/\D/g, '')
    if (numbers.length > 4) {
        numbers = numbers.slice(0, -4) + '-' + numbers.slice(-4)
    }
    form.value.organization_number = numbers
}

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

onMounted(() => {
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
});
</script>

<template>
  <section class="page-section" ref="sectionEl">
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

    <VCard
      flat 
      class="card-fill"
      :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row',
          $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'
      ]"
    >
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
                        </div>
                        <div class="d-flex justify-center" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                            <div class="logo-store">
                                  <VBtn 
                                    type="button" 
                                    :block="windowWidth < 1024"
                                    class="logo-button btn-ghost btn-white-logo "
                                    :class="windowWidth < 1024 ? 'w-40' : 'w-auto'"
                                    @click="isConfirmChangeLogoVisible = true"
                                >
                                    <div class="btn-white-logo v-btn__content">
                                        <VIcon icon="custom-pencil" size="24" />
                                    </div>
                                </VBtn>
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
                                    v-model="form.company"
                                    :rules="[requiredValidator]"
                                />
                            </div>
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post*" />
                                <VTextField
                                    v-model="form.email"
                                    :rules="[emailValidator, requiredValidator]"
                                />
                            </div>
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Organisationsnummer*" />
                                <VTextField
                                    v-model="form.organization_number"
                                    :rules="[requiredValidator, minLengthDigitsValidator(10)]"
                                    minLength="11"
                                    maxlength="11"
                                    @input="formatOrgNumber()"
                                />
                            </div>
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
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Adress*" />
                                <VTextField
                                    v-model="form.address"
                                    :rules="[requiredValidator]"
                                />
                            </div>
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Postnummer*" />
                                <VTextField
                                    v-model="form.postal_code"
                                    :rules="[requiredValidator]"
                                />
                            </div>
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Stad*" />
                                <VTextField
                                    v-model="form.street"
                                    :rules="[requiredValidator]"
                                />
                            </div>                            
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Telefon*" />
                                <VTextField
                                    v-model="form.phone"
                                    :rules="[requiredValidator, phoneValidator]"
                                />
                            </div>
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Hemsida" />
                                <VTextField
                                    v-model="form.link"
                                    :rules="[urlValidator]"
                                />
                            </div>
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Bank*" />
                                <VTextField
                                    v-model="form.bank"
                                    :rules="[requiredValidator]"
                                />
                            </div>
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Bankgiro" />
                                <VTextField
                                    v-model="form.iban"
                                />
                            </div>
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kontonummer*" />
                                <VTextField
                                    v-model="form.account_number"
                                    :rules="[requiredValidator]"
                                />
                            </div>
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Iban nummer" />
                                <VTextField
                                    v-model="form.iban_number"
                                />
                            </div>
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="BIC" />
                                <VTextField
                                    v-model="form.bic"
                                />
                            </div>
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Plusgiro" />
                                <VTextField
                                    v-model="form.plus_spin"
                                />
                            </div>
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Swish" />
                                <VTextField
                                    v-model="form.swish"
                                    :rules="[phoneValidator]"
                                />
                            </div>
                            <div :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(50% - 12px);'">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Vat" />
                                <VTextField
                                    v-model="form.vat"
                                />
                            </div>
                             <div class="w-100">
                                <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Signatur" />
                                <div class="d-flex align-center gap-4">
                                    <div style="width: 50%;">
                                        <VImg :src="signature" class="signature-image" />
                                    </div>
                                    <div class="d-flex flex-column gap-2" style="width: 50%;">
                                        <VBtn 
                                            class="btn-light w-auto" 
                                            block
                                            :disabled="role === 'User'"
                                            @click="isConfirmChangeSignatureVisible = true"
                                        >
                                            <VIcon icon="custom-upload" size="24" />
                                            Ladda upp fil
                                        </VBtn>
                                        <VBtn 
                                            :disabled="role === 'User'"
                                            class="btn-ghost w-auto" 
                                            @click="openSignaturePadDialog">
                                            <VIcon icon="custom-pencil" size="24" />
                                            Rita signatur
                                        </VBtn>
                                    </div>
                                </div>
                            </div>

                            <VCardText class="p-0 d-flex w-100">
                                <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'"/>
                                <div class="d-flex" :class="windowWidth < 1024 ? 'w-100 gap-2' : 'gap-4'">
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
            </VRow>
        </VCardText>
    </VCard>

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
            <VCardText class="dialog-title-box">
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
                            :stencil-props="{
                                previewClass: 'cropper-preview-circle'
                            }"
                            @change="onCropChange"
                        />
                    </VCol>
                    <VCol cols="12" md="12" class="pt-0">
                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Logotyp" />
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
                        <VLabel class="mb-1 text-body-2 text-high-emphasis text-sm" text="Tillåtna format JPG, GIF, PNG." />        
                    </VCol>
                </VRow>
            </VCardText>

             <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-0">
                <VBtn
                    class="btn-light"
                    @click="isConfirmChangeLogoVisible = false"
                >
                    Avbryt
                </VBtn>
                <VBtn 
                    class="btn-gradient"
                    @click="cropImage"
                >
                    Spara
                </VBtn>
            </VCardText>
        </VCard>
    </VDialog>

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="dialog"
      persistent
      class="action-dialog" 
    >
        <!-- Dialog close btn -->
        <VBtn
            icon
            class="btn-white close-btn"
            @click="cancelLeave"
            >
            <VIcon size="16" icon="custom-close" />
        </VBtn>
        
        <!-- Dialog Content -->
        <VCard>
            <VCardText class="dialog-title-box">
                <img :src="modalWarningIcon" alt="Warning" class="action-icon" />
                <div class="dialog-title">Avsluta utan att spara</div>
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
                    <div class="mt-4" :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(100% - 12px);'">
                        <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Firma" />
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
                <VBtn
                    class="btn-light"
                    @click="isConfirmChangeSignatureVisible = false"
                    >
                    Avbryt
                </VBtn>
                <VBtn 
                    class="btn-gradient"
                    @click="cropSignatureImage"
                >
                    Spara
                </VBtn>
            </VCardText>
        </VCard>
    </VDialog>

    <!-- ======================================================= -->
    <!-- MODAL PARA DIBUJAR LA FIRMA DEL SUPPLIER -->
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
                <VBtn class="btn-ghost"
                    @click="isSignaturePadDialogVisible = false"
                >
                Avbryt
                </VBtn>
                <VBtn
                    class="btn-light"
                    @click="clearSignaturePad"
                >
                    Rensa
                </VBtn>
                <VBtn 
                    class="btn-gradient"
                    @click="saveSignatureFromPad"
                >
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
        margin-top: -100px;
        z-index: 9999;
        position: relative;
    }
    
    .logo-store-img {
        width: 144px;
        height: 144px;
        max-width: 144px;
        background: linear-gradient(90deg, #57F287 0%, #00EEB0 50%, #00FFFF 100%);
        border-radius: 50% !important;
        object-fit: cover;
        box-shadow: 0px 0px 30px 0px rgba(0, 0, 0, 0.25);
    }

    .logo-store .logo-button {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 10000;
    }

    .tw-bg-tertiary {
        opacity: 1 !important;
        /*background-color: #0A1B33 !important*/
    }

    .store-name {
        font-weight: 700;
        font-size: 32px;
        line-height: 24px;
        letter-spacing: 0;
        color: #454545;
    }

    .store-address {
        font-size: 16px;
        font-style: normal;
        font-weight: 600;
        line-height: 16px;
        color: rgba(var(--v-theme-on-background), var(--v-high-emphasis-opacity));
        margin-inline-start: 20px;
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

    .title-tabs {
        font-weight: 700;
        font-size: 24px;
        line-height: 100%;
        color: #454545;

        @media (max-width: 1023px) {
            font-size: 16px
        }
    }

    @media (max-width: 776px) {

        .title-tabs {
            font-size: 16px;
        }

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
            margin-top: -100px;
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

<route lang="yaml">
  meta:
    action: view
    subject: dashboard
</route>
