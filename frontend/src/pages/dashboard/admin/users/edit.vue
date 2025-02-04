<script setup>

import { requiredValidator, phoneValidator } from '@/@core/utils/validators'
import { useUsersStores } from '@/stores/useUsers'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  user: {
    type: Object,
    required: true
  },
  rolesList: {
    type: Object,
    required: true
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'close',
  'alert',
  'data'
])

const usersStores = useUsersStores()

const refFormEdit = ref()

const id = ref('')
const email = ref('')
const name = ref('')
const last_name = ref('')
const phone = ref('')
const address = ref('')
const document = ref('')
const assignedRoles = ref([])

const advisor = ref({
  type: '',
  message: '',
  show: false
})

onMounted(async () => {
  fetchData()
})

watchEffect(fetchData)

async function fetchData() {
  if (props.isDrawerOpen) {
    if (!(Object.entries(props.user).length === 0) && props.user.constructor === Object) {
      id.value = props.user.id
      email.value = props.user.email
      name.value = props.user.name
      last_name.value = props.user.last_name
      phone.value = props.user.user_detail?.phone
      address.value = props.user.user_detail?.address
      document.value = props.user.user_detail?.document

      assignedRoles.value = props.user.assignedRoles

    }
  }
}

const closeUserEditDialog = function(){
    emit('update:isDrawerOpen', false)
    emit('close')
}

const onSubmitEdit = () =>{
  refFormEdit.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {

        let data = {
          name: name.value,
          email: email.value,
          last_name: last_name.value,
          phone: phone.value,
          address: address.value,
          document: document.value,
          roles: assignedRoles.value
        }

        usersStores.updateUser(data, id.value)
            .then(response => {
                closeUserEditDialog()

                window.scrollTo(0, 0)

                advisor.value.show = true
                advisor.value.type = 'success'
                advisor.value.message = 'User updated!'

                emit('alert', advisor)
                emit('data')
                emit('close')

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
    <!-- DIALOG-->
    <VDialog
        :model-value="props.isDrawerOpen"
        max-width="600"
        persistent
        >
        <!-- Dialog close btn -->
        <DialogCloseBtn @click="closeUserEditDialog" />

        <!-- Dialog Content -->
        <VCard title="Edit user">
          <VDivider class="mt-4"/>
            <VForm
                ref="refFormEdit"
                @submit.prevent="onSubmitEdit">
                <VCardText>
                    <VRow>
                        <VCol
                          md="6"
                          cols="12"
                        >
                          <VTextField
                            v-model="name"
                            label="Name"
                            :rules="[requiredValidator]"
                          />
                        </VCol>
                        <VCol
                          md="6"
                          cols="12"
                        >
                          <VTextField
                            v-model="last_name"
                            label="Last name"
                            :rules="[requiredValidator]"
                          />
                        </VCol>
                        <VCol
                          cols="12"
                          md="12"
                        >
                          <VTextField
                            v-model="email"
                            label="E-mail"
                            disabled
                          />
                        </VCol>
                        <VCol md="6" cols="12">
                          <VTextField
                            v-model="phone"
                            type="password"
                            label="Password"
                            disabled
                          />
                        </VCol> 
                        <VCol
                          md="6"
                          cols="12"
                        >
                          <VTextField
                            v-model="phone"
                            type="tel"
                            label="Phone"
                            placeholder="+(XX) XXXXXXXXX"
                            :rules="[phoneValidator, requiredValidator]"
                          />
                        </VCol>
                        <VCol
                          cols="12"
                          md="12"
                        >
                          <VTextField
                            v-model="document"
                            type="tel"
                            label="Document"
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
                            label="Address"
                            :rules="[requiredValidator]"
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
                            @click="closeUserEditDialog"
                        >
                            Cancel
                        </VBtn>
                        <VBtn type="submit">
                            Edit
                        </VBtn>
                    </VCardText>
                </VCardText>
            </VForm>
        </VCard>
    </VDialog>
</template>
<route lang="yaml">
  meta:
    action: edit
    subject: users
</route>
