<script setup>

import { requiredValidator } from '@/@core/utils/validators'
import { useUsersStores } from '@/stores/useUsers'

const props = defineProps({
  isDrawerOpen: {
    type: Boolean,
    required: true
  },
  user: {
    type: Object,
    required: true
  }

})

const usersStores = useUsersStores()

const emit = defineEmits([
  'update:isDrawerOpen',
  'alert',
  'data'
])

const refForm = ref()

const id = ref('')
const email = ref('')
const password = ref('')
const isPasswordVisible = ref(false)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

watchEffect(() => {
    if (props.isDrawerOpen) {

        if (!(Object.entries(props.user).length === 0) && props.user.constructor === Object) {
            id.value = props.user.id
            email.value = props.user.email
        }
    }
})

const closeUserPasswordDialog = function(){
    emit('update:isDrawerOpen', false)

    nextTick(() => {
        refForm.value?.reset()
        refForm.value?.resetValidation()
        password.value = ''
    })
}

const editUserPassword = function(){

    refForm.value?.validate().then(({ valid: isValid }) => {
        if (isValid) {
            let data = {
                password: password.value
            }
            //UPDATE USER PASSWORD
            usersStores.updatePasswordUser(data, id.value)
                .then(response => {
                    window.scrollTo(0, 0)

                    advisor.value.show = true
                    advisor.value.type = 'success'
                    advisor.value.message = 'Password changed'
                    
                    emit('update:isDrawerOpen', false)
                    emit('alert', advisor)
                    emit('data')

                    nextTick(() => {
                        refForm.value?.reset()
                        refForm.value?.resetValidation()
                        password.value = ''
                    })

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
      v-model="props.isDrawerOpen"
      max-width="600"
      persistent
    >
        <!-- Dialog close btn -->
        <DialogCloseBtn @click="closeUserPasswordDialog" />

        <!-- Dialog Content -->
        <VCard title="Edit user password">
            <VDivider class="mt-4"/>
            <VForm
                ref="refForm"
                @submit.prevent="editUserPassword"
                >
                <VCardText>
                    <VRow>
                        <VCol cols="12">
                            <VTextField
                                v-model="email"
                                label="E-mail"
                                readonly
                            />
                        </VCol>
                        <VCol cols="12" >
                            <VTextField
                                v-model="password"
                                label="New password"
                                :type="isPasswordVisible ? 'text' : 'password'"
                                :rules="[requiredValidator]"
                                :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                                @click:append-inner="isPasswordVisible = !isPasswordVisible"
                            />
                        </VCol>
                    </VRow>
                    <VCardText class="d-flex justify-end gap-3 flex-wrap pb-0 px-0">
                        <VBtn
                            color="secondary"
                            variant="tonal"
                            @click="closeUserPasswordDialog"
                        >
                            Cancel
                        </VBtn>
                        <VBtn type="submit">
                            Edit password
                        </VBtn>
                    </VCardText>
                </VCardText>
            </VForm>
        </VCard>
    </VDialog>
</template>
