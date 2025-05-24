<script setup>

import permissions from './permissions.vue'

import { requiredValidator } from '@/@core/utils/validators'
import { useRolesStores } from '@/stores/useRoles'

const isRolCreateDialog = ref(false)
const isRolSelectedDialog = ref(false)

const emit = defineEmits([
  'alert',
  'data'
])

const rolesStores = useRolesStores()

const refFormCreate = ref()

const name = ref('')
const assignedPermissions = ref([])

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const closeRolCreateDialog = function(){
    isRolCreateDialog.value = false
    
    nextTick(() => {
        refFormCreate.value?.reset()
        refFormCreate.value?.resetValidation()
        name.value = ''
        assignedPermissions.value = []
    })
}

const getPermissions = function(permissions){
    assignedPermissions.value = permissions
}

const onSubmitCreate = () =>{
  refFormCreate.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {

        let data = {
            name: name.value,
            permissions: assignedPermissions.value
        }

        rolesStores.addRole(data)
            .then(response => {
                closeRolCreateDialog()

                window.scrollTo(0, 0)

                advisor.value.show = true
                advisor.value.type = 'success'
                advisor.value.message = 'Roll skapad!'

                emit('alert', advisor)
                emit('data')

                nextTick(() => {
                    refFormCreate.value?.reset()
                    refFormCreate.value?.resetValidation()
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

                closeRolCreateDialog()
                window.scrollTo(0, 0)

                advisor.value.show = true
                advisor.value.type = 'error'

                if (error.feedback === 'params_validation_failed') {
                    if(error.message.hasOwnProperty('name'))
                        advisor.value.message = error.message.name[0]
                    else if(error.message.hasOwnProperty('permissions'))
                        advisor.value.message = error.message.permissions[0]
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
    <!-- 👉 create rol -->
    <VDialog
        v-model="isRolCreateDialog"
        max-width="600"
        persistent
        >
        <!-- Dialog Activator -->
        <template #activator="{ props }">
            <VBtn
              v-if="$can('create','roles')"
              v-bind="props"
              prepend-icon="tabler-plus"
              class="w-100 w-md-auto"
            >
            Skapa roll
            </VBtn>
        </template>

        <!-- Dialog close btn -->
        <DialogCloseBtn @click="closeRolCreateDialog" />

        <!-- Dialog Content -->
        <VCard title="Skapa roll">
            <VDivider class="mt-4"/>
            <VCardText>
                <VForm
                    ref="refFormCreate"
                    @submit.prevent="onSubmitCreate"
                >
                    <VRow>
                        <VCol cols="12">
                            <VTextField
                                v-model="name"
                                label="Namn roll"
                                :rules="[requiredValidator]"
                            />
                        </VCol>
                        <VCol
                            cols="12"
                            class="text-center"
                        >
                            <VBtn
                                class="w-100 w-md-auto"
                                @click="isRolSelectedDialog = true"
                            >
                                Behörighet för roll
                            </VBtn>
                        </VCol>
                    </VRow>
                    <VCardText class="d-flex justify-end gap-3 flex-wrap pb-0 px-0">
                        <VBtn
                            color="secondary"
                            variant="tonal"
                            @click="closeRolCreateDialog"
                        >
                            Avbryt
                        </VBtn>
                        <VBtn type="submit">
                            Skapa
                        </VBtn>
                    </VCardText>
                </VForm>
            </VCardText>
        </VCard>
    </VDialog>

    <permissions 
        v-model:isDrawerOpen="isRolSelectedDialog"
        @permissions="getPermissions"
    />
   
</template>
<route lang="yaml">
    meta:
      action: create
      subject: roles
  </route>
