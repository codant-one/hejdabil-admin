<script setup>

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
  'close'
])

const email = ref('')
const name = ref('')
const password = ref('')
const last_name = ref('')
const phone = ref('')
const isPhone = ref(false)
const address = ref('')
const isAddress = ref(false)

const assignedRoles = ref([])

watchEffect(() => {
    if (props.isDrawerOpen) {

        if (!(Object.entries(props.user).length === 0) && props.user.constructor === Object) {
            email.value = props.user.email
            password.value = props.user.password
            name.value = props.user.name
            last_name.value = props.user.last_name
            phone.value = props.user.user_detail?.personal_phone ?? '----'
            isPhone.value = (props.user.user_detail?.personal_phone === null) ? true : false
            address.value = props.user.user_detail?.personal_address ?? '----'
            isAddress.value = (props.user.user_detail?.personal_address === null) ? true : false

            assignedRoles.value = props.user.assignedRoles
        }
    }
})

const closeUserDetailDialog = function() {
    emit('update:isDrawerOpen', false)
    emit('close')
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
        <DialogCloseBtn @click="closeUserDetailDialog" />

        <!-- Dialog Content -->
        <VCard title="Användaruppgifter">
            <VDivider class="mt-4"/>
            <VCardText>
                <VRow>
                    <VCol md="6" cols="12">
                        <VTextField
                            v-model="name"
                            label="Namn"
                            readonly
                        />
                    </VCol>
                    <VCol md="6" cols="12">
                        <VTextField
                            v-model="last_name"
                            label="Efternamn"
                            readonly
                        />
                    </VCol>
                    <VCol md="12" cols="12">
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
                    <VCol md="6" cols="12">
                        <VTextField
                            v-model="phone"
                            type="tel"
                            label="Telefon"
                            :readonly="!isPhone"
                            :disabled="isPhone"
                        />
                    </VCol>
                    <VCol cols="12" md="12">
                        <VTextarea
                            v-model="address"
                            rows="3"
                            label="Adress"
                            :readonly="!isAddress"
                            :disabled="isAddress"
                            />
                    </VCol>               
                    <VCol md="12" cols="12">
                        <VCombobox
                            v-model="assignedRoles"
                            chips
                            multiple
                            :items="rolesList"
                            label="Roller som tilldelats användaren"
                            readonly
                        />
                    </VCol>
                </VRow>
            </VCardText>
        </VCard>
    </VDialog>
</template>
