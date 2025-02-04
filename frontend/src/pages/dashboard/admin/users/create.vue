<script setup>

import { emailValidator, requiredValidator } from '@/@core/utils/validators'
import { useUsersStores } from '@/stores/useUsers'

const props = defineProps({
  rolesList: {
    type: Object,
    required: true
  }
})

const emit = defineEmits([
  'close',
  'alert',
  'data'
])

const usersStores = useUsersStores()

const refFormCreate = ref()

const isUserCreateDialog = ref(false)
const isPasswordVisible = ref(false)
const email = ref('')
const name = ref('')
const password = ref('')
const last_name = ref('')
const phone = ref('----')
const address = ref('----')
const assignedRoles = ref([])

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
    assignedRoles.value = []
  })
}

const onSubmitCreate = () => {
  refFormCreate.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {
      
      let data = {
        name: name.value,
        email: email.value,
        password: password.value,
        last_name: last_name.value,
        roles: assignedRoles.value
      }

      usersStores.addUser(data)
        .then(response => {
          closeUserCreateDialog()

          window.scrollTo(0, 0)
          
          advisor.value.show = true
          advisor.value.type = 'success'
          advisor.value.message = 'User Created!'
          
          emit('alert', advisor)
          emit('data')
          emit('close')

          nextTick(() => {
            refFormCreate.value?.reset()
            refFormCreate.value?.resetValidation()
            email.value = ''
            name.value = ''
            password.value = ''
            last_name.value = ''
            phone.value = '----'
            address.value = '----'
            assignedRoles.value = []
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
            advisor.value.message = 'An error has occurred...! (Server Error)'
          }

          emit('alert', advisor)
          emit('data')
          emit('close')

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
        >
        Create users
      </VBtn>
    </template>

    <DialogCloseBtn @click="closeUserCreateDialog " />

    <VCard title="Create user">
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
                label="Name"
                :rules="[requiredValidator]"
                />
            </VCol>
            <VCol md="6" cols="12">
              <VTextField
                v-model="last_name"
                label="Last name"
                :rules="[requiredValidator]"
              />
            </VCol>
            <VCol cols="12" md="12">
              <VTextField
                v-model="email"
                label="E-mail"
                type="email"
                :rules="[requiredValidator,emailValidator]"
              />
            </VCol>
            <VCol cols="12" md="6">
              <VTextField
                v-model="password"
                label="Password"
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
                label="Phone"
                placeholder="+(XX) XXXXXXXXX"
                disabled
              />
            </VCol>
            <VCol cols="12" md="12">
              <VTextarea
                v-model="address"
                rows="3"
                label="Address"
                disabled
              />
            </VCol>
            <VCol cols="12">
              <VCombobox
                v-model="assignedRoles"
                chips
                clearable
                multiple
                closable-chips
                clear-icon="tabler-circle-x"
                :items="rolesList"
                label="Roles assigned to the user"
                :rules="[requiredValidator]"
                />
            </VCol>
          </VRow>
          <VCardText class="d-flex justify-end gap-3 flex-wrap pb-0 px-0">
            <VBtn
              color="secondary"
              variant="tonal"
              @click="closeUserCreateDialog"
            >
              Cancel
            </VBtn>
            <VBtn type="submit">
              Create
            </VBtn>
          </VCardText>
        </VCardText>
      </VForm>
    </VCard>
  </VDialog>
</template>
<route lang="yaml">
  meta:
    action: create
    subject: users
</route>
