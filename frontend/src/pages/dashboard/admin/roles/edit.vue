<script setup>

import permissions from './permissions.vue'

import { requiredValidator } from '@/@core/utils/validators'
import { useRolesStores } from '@/stores/useRoles'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  role: {
    type: Object,
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

const rolesStores = useRolesStores()

const refFormEdit = ref()

const isRolSelectedDialog = ref(false)
const id = ref('')
const name = ref('')
const assignedPermissions = ref([])

const advisor = ref({
  type: '',
  message: '',
  show: false
})

watchEffect(fetchData)

async function fetchData() {
    if (props.isDrawerOpen) {

        if (!(Object.entries(props.role).length === 0) && props.role.constructor === Object) {
            id.value = props.role.id
            name.value = props.role.name
            assignedPermissions.value = props.role.assignedPermissions
      }
  }
}

const closeRoleEditDialog = function(){
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
            permissions: assignedPermissions.value
        }

        rolesStores.updateRole(data, id.value)
            .then(response => {
                closeRoleEditDialog()

                window.scrollTo(0, 0)
                
                advisor.value.show = true
                advisor.value.type = 'success'
                advisor.value.message = 'Uppdaterad roll!'

                emit('alert', advisor)
                emit('data')

                nextTick(() => {
                    refFormEdit.value?.reset()
                    refFormEdit.value?.resetValidation()
                    name.value = ''
                    assignedPermissions.value = []
                })

                setTimeout(() => {
                    advisor.value.show = false
                    advisor.value.type = ''
                    advisor.value.message = ''
                    emit('alert', advisor)
                }, 5000)

            }).catch(error => {
                closeRoleEditDialog()
                window.scrollTo(0, 0)
                
                advisor.value.show = true
                advisor.value.type = 'error'

                if (error.feedback === 'params_validation_failed') {
                    if(error.message.hasOwnProperty('name'))
                        advisor.value.message = error.message.name[0]
                    else if(error.message.hasOwnProperty('permissions'))
                        advisor.value.message = error.message.permissions[0]
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
    <!-- DIALOG -->
    <VDialog
        :model-value="props.isDrawerOpen"
        max-width="600"
        persistent
        >
        <!-- Dialog close btn -->
        <DialogCloseBtn @click="closeRoleEditDialog" />

        <!-- Dialog Content -->
        <VCard title="Redigera roll">
            <VDivider class="mt-4"/>
            <VForm
                ref="refFormEdit"
                @submit.prevent="onSubmitEdit"
            >
                <VCardText>
                    <VRow>
                        <VCol cols="12">
                            <VTextField
                                v-model="id"
                                label="ID"
                                readonly
                            />
                        </VCol>
                        <VCol cols="12">
                            <VTextField
                                v-model="name"
                                label="Namn"
                                :rules="[requiredValidator]"
                            />
                        </VCol>
                        <VCol
                            cols="12"
                            class="text-center"
                        >
                            <VBtn class="w-100 w-md-auto" @click="isRolSelectedDialog = true">
                                Redigera roll permissions
                            </VBtn>
                        </VCol>
                    </VRow>
                    <VCardText class="d-flex justify-end gap-3 flex-wrap pb-0 px-0">
                        <VBtn
                            color="secondary"
                            variant="tonal"
                            @click="closeRoleEditDialog"
                        >
                            Avbryt
                        </VBtn>
                        <VBtn
                            
                            type="submit"
                        >
                            Redigera
                        </VBtn>
                    </VCardText>
                </VCardText>
            </VForm>
        </VCard>
    </VDialog>

    <permissions 
        v-model:isDrawerOpen="isRolSelectedDialog"
        :role="role"
        @permissions="getPermissions"
    />
</template>
<route lang="yaml">
    meta:
      action: edit
      subject: roles
  </route>
