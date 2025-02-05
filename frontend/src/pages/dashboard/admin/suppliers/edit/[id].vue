<script setup>

import router from '@/router'
import { emailValidator, requiredValidator, phoneValidator, urlValidator } from '@/@core/utils/validators'
import { useSuppliersStores } from '@/stores/useSuppliers'

const suppliersStores = useSuppliersStores()

const emitter = inject("emitter")
const route = useRoute()

const isRequestOngoing = ref(true)

const isFormValid = ref(false)
const refForm = ref()
const currentTab = ref('tab-1')
const isMobile = ref(false)

const supplier = ref(null)
const company = ref('')
const organization_number = ref('')
const link = ref('')
const address = ref('')
const street = ref('')
const postal_code = ref('')
const phone = ref('')
const bank = ref('')
const account_number = ref('')
const name = ref('')
const last_name = ref('')
const email = ref('')

onMounted(async () => {

    checkIfMobile()
   
    window.addEventListener('resize', checkIfMobile);
})

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768;
}

watchEffect(async() => {

    isRequestOngoing.value = true


    if(Number(route.params.id)) {
        supplier.value = await suppliersStores.showSupplier(Number(route.params.id))
       
        //company
        company.value = supplier.value.company
        organization_number.value = supplier.value.organization_number
        link.value = supplier.value.link
        address.value = supplier.value.address
        street.value = supplier.value.street
        postal_code.value = supplier.value.postal_code
        phone.value = supplier.value.phone

        //bank
        bank.value = supplier.value.bank
        account_number.value = supplier.value.account_number

        // contact
        name.value  = supplier.value.user.name
        last_name.value = supplier.value.user.last_name 
        email.value = supplier.value.user.email
    }

    isRequestOngoing.value = false
})

const onSubmit = () => {

    refForm.value?.validate().then(({ valid }) => {
        if (valid && currentTab.value === 0 && refForm.value.items.length < 10) {
            currentTab.value++
        } else if (!valid && currentTab.value === 0 && refForm.value.items.length === 9 || !valid && currentTab.value === 0 && refForm.value.items.length === 13) {
            currentTab.value++
        } else if (!valid && currentTab.value === 1 && refForm.value.items.length > 9) {
            currentTab.value++
        } else if (valid  && currentTab.value < 2 && refForm.value.items.length > 8) {
            currentTab.value++
        } else if (valid && currentTab.value === 2) {
            let formData = new FormData()

            formData.append('id', Number(route.params.id))
            formData.append('_method', 'PUT')

            //company
            formData.append('company', company.value)
            formData.append('organization_number', organization_number.value)
            formData.append('link', link.value)
            formData.append('address', address.value)
            formData.append('street', street.value)
            formData.append('postal_code', postal_code.value)
            formData.append('phone', phone.value)

            //bank
            formData.append('bank', bank.value)
            formData.append('account_number', account_number.value)

            //contact
            formData.append('name', name.value)
            formData.append('last_name', last_name.value)
            formData.append('email', email.value)

            isRequestOngoing.value = true

            let data = {
                data: formData, 
                id: Number(route.params.id)
            }

            suppliersStores.updateSupplier(data)
                .then((res) => {
                    if (res.data.success) {
                        
                        let data = {
                            message: 'Updated Supplier!',
                            error: false
                        }

                        router.push({ name : 'dashboard-admin-suppliers'})
                        emitter.emit('toast', data)
                    }
                    isRequestOngoing.value = false
                })
                .catch((err) => {
                    
                    let data = {
                        message: err,
                        error: true
                    }

                    router.push({ name : 'dashboard-admin-suppliers'})
                    emitter.emit('toast', data)

                    isRequestOngoing.value = false
                })
        }
    })
}

</script>

<template>
    <section>
        <VRow>
            <VDialog
                v-model="isRequestOngoing"
                width="300"
                persistent>
                
                <VCard
                    color="primary"
                    width="300">
                    
                    <VCardText class="pt-3">
                        Loading
                        <VProgressLinear
                        indeterminate
                        color="white"
                        class="mb-0"/>
                    </VCardText>
                </VCard>
            </VDialog>

            <VCol cols="12" md="12">
                <div class="d-flex mt-5 flex-wrap justify-start justify-sm-space-between gap-y-4 gap-x-6">
                    <div class="d-flex flex-column justify-center">
                        <h6 class="text-md-h4 text-h6 font-weight-medium">
                            Edit a supplier 😃🌟
                        </h6>
                        <span>Recharge your company with suppliers 🎉</span>
                    </div>
                    <VSpacer />
                    <div class="d-flex gap-4">
                        <VBtn
                            variant="tonal"
                            color="secondary"
                            class="mb-2"
                            :to="{ name: 'dashboard-admin-suppliers' }"
                            >
                            Back
                        </VBtn>
                    </div>
                </div>
            </VCol>
            <VCol cols="12" md="12">
                <VForm
                    ref="refForm"
                    v-model="isFormValid"
                    @submit.prevent="onSubmit">
                    <VCard flat class="px-2 px-md-12">
                        <VCardText class="px-2 pt-0 px-md-12 pt-md-5">                
                            <VTabs
                                v-model="currentTab"
                                grow
                                stacked
                                disabled>
                                <VTab>
                                    <VIcon icon="mdi-domain" class="mb-0 mb-md-2" />
                                    <span v-if="!isMobile">Company</span>
                                </VTab>
                                <VTab>
                                    <VIcon icon="mdi-bank" class="mb-0 mb-md-2" />
                                    <span v-if="!isMobile">Bank details</span>
                                </VTab>
                                <VTab>
                                    <VIcon icon="mdi-account-tie" class="mb-0 mb-md-2"/>
                                    <span v-if="!isMobile">Contact</span>
                                </VTab>
                            </VTabs>
                            <VCardText class="px-2 px-md-12">
                                <VWindow v-model="currentTab" class="pt-3">
                                    <!-- company -->
                                    <VWindowItem class="px-md-14">
                                        <VRow class="px-md-14">
                                            <VCol cols="12" md="12">
                                                <VTextField
                                                    v-model="company"
                                                    :rules="[requiredValidator]"
                                                    label="Company name"
                                                />
                                            </VCol>   
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="organization_number"
                                                    :rules="[requiredValidator]"
                                                    label="Organization number"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="link"
                                                    :rules="[urlValidator]"
                                                    label="Page"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="12">
                                                <VTextarea
                                                    v-model="address"
                                                    rows="3"
                                                    :rules="[requiredValidator]"
                                                    label="Address"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="street"
                                                    :rules="[requiredValidator]"
                                                    label="Street"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="postal_code"
                                                    :rules="[requiredValidator]"
                                                    label="Postal code"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="phone"
                                                    :rules="[requiredValidator, phoneValidator]"
                                                    label="Phone"
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>
                                    <!-- bank -->
                                    <VWindowItem class="px-md-14">
                                        <VRow class="px-md-14">
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="bank"
                                                    :rules="[requiredValidator]"
                                                    label="Bank name"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="account_number"
                                                    :rules="[requiredValidator]"
                                                    label="Account number"
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>
                                    <!-- contact -->
                                    <VWindowItem class="px-md-14">
                                        <VRow class="px-md-14">
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="name"
                                                    :rules="[requiredValidator]"
                                                    label="Name"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="last_name"
                                                    :rules="[requiredValidator]"
                                                    label="Last name"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="12">
                                                <VTextField
                                                    :rules="[emailValidator, requiredValidator]"
                                                    v-model="email"
                                                    label="E-mail"
                                                    disabled
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>
                                </VWindow>
                            </VCardText>
                        </VCardText>
                    </VCard>
                    <VRow class="mt-5">
                        <!-- 👉 Submit and Cancel -->
                        <VCol cols="12" md="12">
                            <div class="text-center align-center justify-content-center">
                                <VBtn
                                    v-if="currentTab > 0"
                                    variant="tonal"
                                    color="secondary"
                                    class="me-3"
                                    @click="currentTab--"
                                    >
                                    Back
                                </VBtn>
                                <VBtn type="submit">
                                    {{ (currentTab === 2) ? 'Update' : 'Next' }}
                                </VBtn>
                            </div>
                        </VCol>
                    </VRow>
                </VForm>
            </VCol>
        </VRow>
    </section>
</template>

<style scoped>
    .v-btn--disabled {
        opacity: 1 !important;
    }
</style>

<route lang="yaml">
    meta:
      action: edit
      subject: suppliers
</route>