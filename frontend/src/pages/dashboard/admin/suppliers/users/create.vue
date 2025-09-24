<script setup>

import permissions from './permissions.vue'

import { emailValidator, requiredValidator } from '@/@core/utils/validators'
import { useSuppliersStores } from '@/stores/useSuppliers'

const emit = defineEmits([
  'alert',
  'data'
])

const usersStores = useSuppliersStores()

const refFormCreate = ref()
const isUserPermissionsDialog = ref(false)

const isUserCreateDialog = ref(false)
const isPasswordVisible = ref(false)
const email = ref('')
const name = ref('')
const password = ref('')
const last_name = ref('')
const phone = ref('----')
const address = ref('----')
const assignedPermissions = ref([])
const readonly =  ref(false)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const closeUserCreateDialog  = function(){
  isUserCreateDialog.value = false

  nextTick(() => {
    refFormCreate.value?.reset()
    refFormCreate.value?.resetValidation()
    email.value = ''
    name.value = ''
    password.value = ''
    last_name.value = ''
    phone.value = '----'
    address.value = '----'
    assignedPermissions.value = []
  })
}

const getPermissions = function(permissions){
    assignedPermissions.value = permissions
}

const onSubmitCreate = () => {
  refFormCreate.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      
      let data = {
        name: name.value,
        email: email.value,
        password: password.value,
        last_name: last_name.value,
        permissions: assignedPermissions.value
      }

      usersStores.addUser(data)
        .then(response => {
          closeUserCreateDialog()

          window.scrollTo(0, 0)
          
          advisor.value.show = true
          advisor.value.type = 'success'
          advisor.value.message = 'Skapad av användaren!'
          
          emit('alert', advisor)
          emit('data')

          nextTick(() => {
            refFormCreate.value?.reset()
            refFormCreate.value?.resetValidation()
            email.value = ''
            name.value = ''
            password.value = ''
            last_name.value = ''
            phone.value = '----'
            address.value = '----'
          })

          setTimeout(() => {
            advisor.value.show = false
            advisor.value.type = ''
            advisor.value.message = ''
            emit('alert', advisor)
          }, 5000)
        }).catch(error => {

          closeUserCreateDialog()
          window.scrollTo(0, 0)

          advisor.value.show = true
          advisor.value.type = 'error'
          
          if (error.feedback === 'params_validation_failed') {
            if(error.message.hasOwnProperty('email'))
              advisor.value.message = error.message.email[0]
          } else {
            advisor.value.message = 'Ett fel har inträffat...! (Serverfel)'
          }

          emit('alert', advisor)
          emit('data')

          setTimeout(() => {
            advisor.value.show = false
            advisor.value.type = ''
            advisor.value.message = ''
            emit('alert', advisor)
          }, 5000)   
        })
    }
  })
}

</script>

<template>
  <!-- 👉 CREATE USER -->
  <VDialog
    v-model="isUserCreateDialog"
    max-width="600"
    persistent
    >
    <template #activator="{ props }">
      <VBtn
        v-if="$can('create', 'users')"
        v-bind="props"
        prepend-icon="tabler-plus"
        class="w-100 w-md-auto"
        >
        Skapa användare
      </VBtn>
    </template>

    <DialogCloseBtn @click="closeUserCreateDialog " />

    <VCard title="Skapa användare">
      <VDivider class="mt-4"/>
      <VForm
        ref="refFormCreate"
        @submit.prevent="onSubmitCreate"
        >
        <VCardText>
          <VRow>
            <VCol md="6" cols="12">
              <VTextField
                v-model="name"
                label="Namn"
                :rules="[requiredValidator]"
                />
            </VCol>
            <VCol md="6" cols="12">
              <VTextField
                v-model="last_name"
                label="Efternamn"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" md="12">
              <VTextField
                v-model="email"
                label="E-post"
                type="email"
                :rules="[requiredValidator,emailValidator]"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField
                v-model="password"
                label="Lösenord"
                :type="isPasswordVisible ? 'text' : 'password'"
                :rules="[requiredValidator]"
                :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                @click:append-inner="isPasswordVisible = !isPasswordVisible"
              />
            </VCol>
            <VCol cols="12" md="6" >
              <VTextField
                v-model="phone"
                type="tel"
                label="Telefon"
                placeholder="+(XX) XXXXXXXXX"
                disabled
              />
            </VCol>
            <VCol cols="12" md="12">
              <VTextarea
                v-model="address"
                rows="3"
                label="Adress"
                disabled
              />
            </VCol>

            <VCol
                cols="12"
                class="text-center"
            >
                <VBtn class="w-100 w-md-auto" @click="isUserPermissionsDialog = true">
                    Redigera roll permissions
                </VBtn>
            </VCol>
          </VRow>
          <VCardText class="d-flex justify-end gap-3 flex-wrap pb-0 px-0">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="closeUserCreateDialog"
            >
              Avbryt
            </VBtn>
            <VBtn type="submit">
              Skapa
            </VBtn>
          </VCardText>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>

  <permissions
      v-model:isDrawerOpen="isUserPermissionsDialog"
      :readonly="readonly"
      @permissions="getPermissions"/>
</template>
<route lang="yaml">
  meta:
    action: create
    subject: users
</route>
