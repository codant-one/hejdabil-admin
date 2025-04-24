<script setup>

import { avatarText } from '@/@core/utils/formatters'
import { emailValidator, requiredValidator, phoneValidator } from '@/@core/utils/validators'
import { initialAbility } from '@/plugins/casl/ability'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useAuthStores } from '@/stores/useAuth'
import { useProfileStores } from '@/stores/useProfile'

const router = useRouter()
const ability = useAppAbility()
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
const titleApp = ref(import.meta.env.VITE_APP_TITLE)
const isRequestOngoing = ref(false)

const alert = ref({
  message: '',
  show: false,  
  type: '',
})

watchEffect(fetchData)

async function fetchData() {

    let data = JSON.parse(localStorage.getItem('user_data') || 'null')
    
    user_id.value = data.id
    email.value = data.email
    name.value = data.name
    last_name.value = data.last_name
    phone.value = data.user_details?.phone
    address.value = data.user_details?.address

    avatarOld.value = data.user_details?.avatar
}

const logout = () => {

  isRequestOngoing.value = true

  authStores.logout()
    .then(response => {
    // Remove "user_data" from localStorage
    localStorage.removeItem('user_data')

    // Remove "accessToken" from localStorage
    localStorage.removeItem('accessToken')
    
    // Remove "userAbilities" from localStorage
    localStorage.removeItem('userAbilities')

    // Reset ability to initial ability
    ability.update(initialAbility)
    router.push('/login')

  })
}

const resetAvatar = () => {
  avatar.value = null
}

const onSubmit = () =>{
  refVForm.value?.validate().then(({ valid: isValid }) => {

    if (isValid) {

      let formData = new FormData()
      
      formData.append('user_id', user_id.value)
      formData.append('email', email.value)
      formData.append('name', name.value)
      formData.append('last_name', last_name.value)
      formData.append('phone', phone.value)
      formData.append('address', address.value)
      formData.append('image', avatarOld.value)

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
          }, 5000)

          isRequestOngoing.value = false

        }).catch(error => {
          alert.value.type = 'error'
          alert.value.show = true
          alert.value.message = 'Ett fel har inträffat...! (Serverfel)'
          
          isRequestOngoing.value = false
          
          setTimeout(() => {
            alert.value.show = false,
            alert.value.message = ''
          }, 5000) 
        })
    }
  })
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
</script>

<template>
  <VRow class="justify-center m-0">

    <VDialog
      v-model="isRequestOngoing"
      width="300"
      persistent>
          
      <VCard
        color="primary"
        width="300">
            
        <VCardText class="pt-3">
          Lastning
          <VProgressLinear
            indeterminate
            color="white"
            class="mb-0"/>
        </VCardText>
      </VCard>
    </VDialog>

    <VCol
      cols="12"
      md="8"
    >
      <h1 class="text-center">
        Komplett användarprofil
      </h1>
      <VCard
        :title="'Välkommen till' + titleApp "
        class="my-5"
      >
        <VCardText>
          <p>För att du ska kunna använda vår panel på ett normalt sätt behöver vi att du fyller i formuläret med dina uppgifter första gången du loggar in. På så sätt kan du sedan använda alla de funktioner som vi har förberett för dig.</p>
        </VCardText>
      </VCard>
      <VDivider />
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
          <VForm
            ref="refVForm"
            @submit.prevent="onSubmit"
          >
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
                <VCol
                  cols="12" md="12"
                  class="d-flex flex-wrap gap-4 justify-buttons"
                >
                  <VBtn type="submit">
                    Spara ändringar
                  </VBtn>
                  <VBtn @click="logout">
                    Logga ut
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VForm>
        </VCol>
      </VRow>
    </VCol>
  </VRow>
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

<route lang="yaml">
  meta:
    layout: blank
    action: view
    subject: Auth
    parar: true
  </route>
