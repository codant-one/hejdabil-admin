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
const isReactivateUserDialog = ref(false)
const isReactivatingUser = ref(false)
const email = ref('')
const name = ref('')
const password = ref('')
const last_name = ref('')
const phone = ref('----')
const address = ref('----')
const assignedPermissions = ref([])
const readonly =  ref(false)
const reactivationSupplierId = ref(null)

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

const closeReactivateUserDialog = function() {
  isReactivateUserDialog.value = false
  reactivationSupplierId.value = null
}

const getEmailValidationMessage = function(error) {
  if (error.feedback !== 'params_validation_failed')
    return ''

  if (error.message?.hasOwnProperty('email') && Array.isArray(error.message.email) && error.message.email.length > 0)
    return error.message.email[0]

  if (typeof error.message === 'string')
    return error.message

  return ''
}

const reactivateUserAccount = async function() {
  if (!reactivationSupplierId.value) {
    closeReactivateUserDialog()
    return
  }

  isReactivatingUser.value = true

  try {
    const response = await usersStores.activateSupplier(reactivationSupplierId.value)

    closeReactivateUserDialog()
    closeUserCreateDialog()

    window.scrollTo(0, 0)

    advisor.value.show = true
    advisor.value.type = 'success'
    advisor.value.message = response.data.message || 'Användaren har återaktiverats!'

    emit('alert', advisor)
    emit('data')

    setTimeout(() => {
      advisor.value.show = false
      advisor.value.type = ''
      advisor.value.message = ''
      emit('alert', advisor)
    }, 5000)

  } catch (error) {
    closeReactivateUserDialog()
    window.scrollTo(0, 0)

    advisor.value.show = true
    advisor.value.type = 'error'
    advisor.value.message = error.message || 'Ett serverfel uppstod. Försök igen.'

    emit('alert', advisor)

    setTimeout(() => {
      advisor.value.show = false
      advisor.value.type = ''
      advisor.value.message = ''
      emit('alert', advisor)
    }, 5000)
  } finally {
    isReactivatingUser.value = false
  }
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
        }).catch(async error => {

          const emailValidationMessage = getEmailValidationMessage(error)

          if (emailValidationMessage === 'En användare med den angivna e-postadressen finns redan.') {
            try {
              const inactiveUser = await usersStores.getInactiveUserByEmail(email.value)

              if (inactiveUser?.supplier_id) {
                reactivationSupplierId.value = inactiveUser.supplier_id
                isReactivateUserDialog.value = true
                return
              }
            } catch (lookupError) {
              reactivationSupplierId.value = null
            }
          }

          closeUserCreateDialog()
          window.scrollTo(0, 0)

          advisor.value.show = true
          advisor.value.type = 'error'

          if (emailValidationMessage) {
            advisor.value.message = emailValidationMessage
          } else {
            advisor.value.message = 'Ett serverfel uppstod. Försök igen.'
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

  <VDialog
    v-model="isReactivateUserDialog"
    max-width="520"
    persistent
  >
    <VCard title="Återaktivera konto">
      <VCardText>
        Denna e-postadress är redan registrerad.<br>
        Användaren är för närvarande inaktiv.<br>
        Vill du återaktivera kontot?
      </VCardText>

      <VCardText class="d-flex justify-end gap-3 flex-wrap pt-0">
        <VBtn
          color="secondary"
          variant="tonal"
          @click="closeReactivateUserDialog"
        >
          Avbryt
        </VBtn>
        <VBtn
          :loading="isReactivatingUser"
          @click="reactivateUserAccount"
        >
          Acceptera
        </VBtn>
      </VCardText>
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
