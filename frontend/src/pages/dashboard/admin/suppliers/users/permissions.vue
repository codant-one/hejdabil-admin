<script setup>

import { useSuppliersStores } from '@/stores/useSuppliers'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  user: {
    type: Object,
    required: false
  },
  permissions: {
    type: Object,
    required: false
  }
})

const emit = defineEmits([
  'update:isDrawerOpen',
  'close',
  'alert',
  'data'
])

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const usersStores = useSuppliersStores()
const assignedPermissions =  ref([])
const refFormPermissions = ref()
const id = ref('')

onMounted(async () => {
  fetchData()
})

watchEffect(fetchData)

async function fetchData() {
    if (props.isDrawerOpen && props.user) {

        if (!(Object.entries(props.user).length === 0) && props.user.constructor === Object) {

            assignedPermissions.value = props.permissions
            id.value = props.user.id

            
        }
    }
}

const closeModal = function(){
    emit('update:isDrawerOpen', false)
    //emit('permissions', assignedPermissions.value)
    emit('close')
}

const onSubmitPermissions = () =>{
  refFormPermissions.value?.validate().then(({ valid: isValid }) => {
    if (isValid) {

        let data = {
          permissions: assignedPermissions.value
        }

        usersStores.updatePermissions(data, id.value)
            .then(response => {
                closeModal()

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
                closeModal()
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
    <!-- DIALOG -->
    <VDialog
        :model-value="props.isDrawerOpen"
        persistent
        max-width="1100"
        >
        <DialogCloseBtn @click="closeModal" />

        <VCard title="Behörigheter">
            <VDivider class="mt-4"/>

            <VForm
                ref="refFormPermissions"
                @submit.prevent="onSubmitPermissions">

                <VCardText class="py-0">
                    <VCardTitle>
                        Moduler  
                    </VCardTitle>
                    <VCardText class="py-0">
                        <div class="ml-5">
                            <VLabel style="font-weight: bold;">
                                Kunder
                            </VLabel>
                            <div class="demo-space-x ml-5">
                                <VCheckbox
                                    v-model="assignedPermissions"
                                    label="view clients"
                                    value="view clients"
                                />
                                <VCheckbox
                                    v-model="assignedPermissions"
                                    label="create clients"
                                    value="create clients"
                                />
                                <VCheckbox
                                    v-model="assignedPermissions"
                                    label="edit clients"
                                    value="edit clients"
                                />
                                <VCheckbox
                                    v-model="assignedPermissions"
                                    label="delete clients"
                                    value="delete clients"
                                />
                            </div>
                            <VLabel style="font-weight: bold;">
                                Fakturor
                            </VLabel>
                            <div class="demo-space-x ml-5">
                                <VCheckbox
                                    v-model="assignedPermissions"
                                    label="view billing"
                                    value="view billing"
                                />
                                <VCheckbox
                                    v-model="assignedPermissions"
                                    label="create billing"
                                    value="create billing"
                                />
                                <VCheckbox
                                    v-model="assignedPermissions"
                                    label="edit billing"
                                    value="edit billing"
                                />
                                <VCheckbox
                                    v-model="assignedPermissions"
                                    label="delete billing"
                                    value="delete billing"
                                />
                            </div>
                            <VLabel style="font-weight: bold;">
                                Mina fordon
                            </VLabel>
                            <VCardText class="pt-2 pb-0">
                                <div class="ml-5">
                                    <VLabel style="font-weight: bold;">
                                        Lagerfordon
                                    </VLabel>
                                    <div class="demo-space-x ml-5">
                                        <VCheckbox
                                            v-model="assignedPermissions"
                                            label="view stock"
                                            value="view stock"
                                        />
                                        <VCheckbox
                                            v-model="assignedPermissions"
                                            label="create stock"
                                            value="create stock"
                                        />
                                        <VCheckbox
                                            v-model="assignedPermissions"
                                            label="edit stock"
                                            value="edit stock"
                                        />
                                        <VCheckbox
                                            v-model="assignedPermissions"
                                            label="delete stock"
                                            value="delete stock"
                                        />
                                    </div>
                                    <VLabel style="font-weight: bold;">
                                        Sålda
                                    </VLabel>
                                    <div class="demo-space-x ml-5">
                                        <VCheckbox
                                            v-model="assignedPermissions"
                                            label="view sold"
                                            value="view sold"
                                        />
                                        <VCheckbox
                                            v-model="assignedPermissions"
                                            label="delete sold"
                                            value="delete sold"
                                        />
                                    </div>
                                    <VLabel style="font-weight: bold;">
                                        Egen värdering
                                    </VLabel>
                                    <div class="demo-space-x ml-5">
                                        <VCheckbox
                                            v-model="assignedPermissions"
                                            label="view notes"
                                            value="view notes"
                                        />
                                        <VCheckbox
                                            v-model="assignedPermissions"
                                            label="create notes"
                                            value="create notes"
                                        />
                                        <VCheckbox
                                            v-model="assignedPermissions"
                                            label="edit notes"
                                            value="edit notes"
                                        />
                                        <VCheckbox
                                            v-model="assignedPermissions"
                                            label="delete notes"
                                            value="delete notes"
                                        />
                                    </div>
                                </div>
                            </VCardText>
                        </div>
                    </VCardText>
                </VCardText>
                <VCardText class="d-flex flex-wrap gap-2">
                    <VSpacer />
                    <VBtn class="w-100 w-md-auto" type="submit">
                        Acceptera
                    </VBtn>
                </VCardText>

            </VForm>
        </VCard>
    </VDialog>
</template>

<route lang="yaml">
  meta:
    action: permissions
    subject: users
</route>
