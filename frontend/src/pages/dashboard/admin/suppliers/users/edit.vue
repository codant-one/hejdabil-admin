<script setup>

import permissions from './permissions.vue'

import { requiredValidator, phoneValidator } from '@/@core/utils/validators'
import { useSuppliersStores } from '@/stores/useSuppliers'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  user: {
    type: Object,
    required: true
  },
  readonly: {
    type: Boolean,
    required: true
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'close',
  'alert',
  'data',
  'readonly'
])

const usersStores = useSuppliersStores()

const refFormEdit = ref()
const isUserPermissionsDialog = ref(false)

const id = ref('')
const email = ref('')
const name = ref('')
const last_name = ref('')
const phone = ref('')
const address = ref('')
const assignedPermissions = ref([])
const readonly = ref(false)

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

      assignedPermissions.value = props.user.assignedPermissions
    }
    readonly.value = props.readonly
  }
}

const closeUserEditDialog = function(){
    emit('update:isDrawerOpen', false)
    emit('close')
    emit('readonly')

    nextTick(() => {
        refFormEdit.value?.reset()
        refFormEdit.value?.resetValidation()
        name.value = ''
        assignedPermissions.value = []
    })
}

const getPermissions = function(permissions){
    assignedPermissions.value = permissions
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
          permissions: assignedPermissions.value
        }

        usersStores.updateUser(data, id.value)
            .then(response => {
                closeUserEditDialog()

                window.scrollTo(0, 0)

                advisor.value.show = true
                advisor.value.type = 'success'
                advisor.value.message = 'Användaren uppdaterad!'

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
                  advisor.value.message = 'Ett fel har inträffat...! (Serverfel)'
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
        <VCard title="Redigera användare">
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
                          md="12"
                        >
                          <VTextField
                            v-model="email"
                            label="E-post"
                            disabled
                          />
                        </VCol>
                        <VCol md="6" cols="12">
                          <VTextField
                            v-model="phone"
                            type="password"
                            label="Lösenord"
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
                            @click="closeUserEditDialog"
                        >
                          Avbryt
                        </VBtn>
                        <VBtn type="submit">
                          Redigera
                        </VBtn>
                    </VCardText>
                </VCardText>
            </VForm>
        </VCard>
    </VDialog>

    <permissions
      v-model:isDrawerOpen="isUserPermissionsDialog"
      :user="user"
      :readonly="readonly"
      @permissions="getPermissions"
      @readonly="readonly = false"/>
</template>
<route lang="yaml">
  meta:
    action: edit
    subject: users
</route>
