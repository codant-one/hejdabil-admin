<script setup>

import { avatarText } from '@/@core/utils/formatters'
import { emailValidator, requiredValidator, phoneValidator } from '@/@core/utils/validators'
import { useProfileStores } from '@/stores/useProfile'

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  avatarOld: {
    type: [String, Object],
    required: true
  },
  avatar: {
    type: String,
    required: true
  }
})

const emit = defineEmits([
  'onImageSelected',
])

const profileStores = useProfileStores()

const refVForm = ref()
const isUserEditDialog = ref(false)
const isRequestOngoing = ref(false)

const userData = ref(props.user)
const user_id = ref('')
const email = ref('')
const name = ref('')
const last_name = ref('')
const phone = ref('')
const address = ref('')
const avatar = ref(props.avatar)

const avatarOld = ref(props.avatarOld)
const roles = ref('')

const alert = ref({
    message: '',
    show: false,  
    type: ''
})

watch(() =>  
  props.avatar, (avatar_) => {
    avatar.value = avatar_
  });

watch(() => 
  props.avatarOld, (avatarOld_) => {
    avatarOld.value = avatarOld_
  });

watchEffect(fetchData)

async function fetchData() { 

  user_id.value = userData.value.id
  email.value = userData.value.email
  name.value = userData.value.name
  last_name.value = userData.value.last_name ?? ''
  phone.value = userData.value.user_details?.phone
  address.value = userData.value.user_details?.address
}

const resetAvatar = () => {
  avatar.value = null
}

const onSubmit = () => {
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
                    
          fetchData()

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
                    
          setTimeout(() => {
            alert.value.show = false,
            alert.value.message = ''
          }, 5000) 

          isRequestOngoing.value = false
        })
      }
            
    })
}

const deleteAvatar = ()=>{
    avatarOld.value = null
    resetAvatar()
}

const showUserEditDialog = u =>{
  isUserEditDialog.value = true
}

const closeUserEditDialog = ()=>{
  isUserEditDialog.value = false
  fetchData()
}
</script>

<template>
  <section>
    <VRow>
      <VDialog
        v-model="isRequestOngoing"
        width="auto"
        persistent>
        <VProgressCircular
          indeterminate
          color="primary"
          class="mb-0"/>
      </VDialog>

      <VCol cols="12">
        <VCard>
          <VCardText class="text-center pt-6">
            <VAvatar
              rounded
              :size="120"
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
            <h6 class="text-h6 mt-4">
              {{ name.toUpperCase() }} {{ last_name.toUpperCase() }}
            </h6>

            <!-- 👉 Role chip -->
            <VChip
              v-for="rol in roles"
              :key="rol"
              label
              size="small"
              class="text-capitalize mt-4 mr-1"
            >
              {{ rol.name }}
            </VChip>
          </VCardText>

          <VDivider />

          <!-- 👉 Details -->
          <VCardText>
            <p class="text-sm text-uppercase text-disabled">
              Detaljer
            </p>

            <VList class="card-list mt-2">
              <VListItem>
                <VListItemTitle>
                  <h6 class="text-base font-weight-semibold">
                    Namn:
                    <span class="text-body-2">
                      {{ name }}
                    </span>
                  </h6>
                </VListItemTitle>
                <VListItemTitle>
                  <h6 class="text-base font-weight-semibold">
                    Efternamn:
                    <span class="text-body-2">
                      {{ last_name }}
                    </span>
                  </h6>
                </VListItemTitle>
                <VListItemTitle>
                  <h6 class="text-base font-weight-semibold">
                    E-post:
                    <span class="text-body-2">
                      {{ email }}
                    </span>
                  </h6>
                </VListItemTitle>
                <VListItemTitle>
                  <h6 class="text-base font-weight-semibold">
                    Telefon:
                    <span class="text-body-2">
                      {{ phone }}
                    </span>
                  </h6>
                </VListItemTitle>
                <VListItemTitle>
                  <h6 class="text-base font-weight-semibold">
                    Adress:
                    <span class="text-body-2">
                      {{ address }}
                    </span>
                  </h6>
                </VListItemTitle>
              </VListItem>
            </VList>
          </VCardText>

          <!-- 👉 Edit and Suspend button -->
          <VCardText class="d-flex justify-center">
            <VBtn
              variant="elevated"
              class="me-3"
              @click="showUserEditDialog()"
            >
              Redigera
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>

      <!-- DIALOG Edit personal information -->
      <VDialog
        v-model="isUserEditDialog"
        max-width="800"
        persistent
      >
        <!-- Dialog close btn -->
        <DialogCloseBtn @click="closeUserEditDialog" />

        <!-- Dialog Content -->
        <VCard title="Redigera personlig information">    
          <VDivider class="mt-4"/>    
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
          <VForm
            ref="refVForm"
            @submit.prevent="onSubmit"
          >
            <VCardText class="d-flex">
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
              <div class="d-flex flex-column justify-center gap-4">
                <div class="d-flex flex-wrap gap-2">
                  <VFileInput                          
                    label="Avatar"
                    accept="image/png, image/jpeg, image/bmp"
                    placeholder="Avatar"
                    prepend-icon="tabler-camera"
                    @change="$emit('onImageSelected', $event)"
                    @click:clear="resetAvatar"
                  />
                </div>
                <p class="text-body-1 mb-0">
                  Tillåtna format JPG, GIF, PNG.
                </p>
                <VBtn 
                  color="secondary"
                  variant="tonal"
                  @click="deleteAvatar"
                >
                  Ta bort avatar
                </VBtn>
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
                    label="Telefon"
                    placeholder="+(XX) XXXXXXXXX"
                    :rules="[requiredValidator, phoneValidator]"
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
                  cols="12"
                  class="d-flex flex-wrap gap-4 justify-center"
                >
                  <VBtn type="submit">
                    Spara ändringar
                  </VBtn>
                </VCol>
              </VRow>
            </VCardText>
          </VForm>
        </VCard>      
      </VDialog> 
    </VRow>
  </section>
</template>

<style lang="scss">
  .v-list-item-title {
    white-space: normal;
  }
</style>
