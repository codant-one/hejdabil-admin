<script setup>

import router from '@/router'
import { yearValidator, requiredValidator } from '@/@core/utils/validators'
import { useSuppliersStores } from '@/stores/useSuppliers'
import logoBlack from '@images/logo_black.png'

const suppliersStores = useSuppliersStores()

const emitter = inject("emitter")
const route = useRoute()

const isRequestOngoing = ref(true)
const isConfirmStatusDialogVisible = ref(false)

const isFormValid = ref(false)
const refForm = ref()
const currentTab = ref('tab-1')
const isMobile = ref(false)

const supplier = ref(null)
const company = ref('AA686OW')
const organization_number = ref('')
const link = ref('')
const address = ref('')
const street = ref('')
const postal_code = ref('')
const phone = ref('')
const swish = ref('')
const bank = ref('')
const account_number = ref('')
const name = ref('')
const last_name = ref('')
const email = ref('')

const state_id = ref(null)

const states = ref ([
  { id: 2, name: "Aktiv" },
  { id: 1, name: "Inaktiv" }
])

const startDateTimePickerConfig = computed(() => {

    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(now.getDate() + 1);

    const formatToISO = (date) => date.toISOString().split('T')[0];


    const config = {
        dateFormat: 'Y-m-d',
        position: 'auto right',
        disable: [{
            from: formatToISO(tomorrow),
            to: '2099-12-31' // Una fecha futura lejana para bloquear indefinidamente
        }]
    }

    return config
})

onMounted(async () => {

    checkIfMobile()

    window.addEventListener('resize', checkIfMobile);
})

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768;
}

watchEffect(async() => {

    isRequestOngoing.value = true


    if(Number(route.params.id) && route.name === 'dashboard-admin-suppliers-edit-id') {
        supplier.value = await suppliersStores.showSupplier(Number(route.params.id))
    
        //company
        company.value = supplier.value.company
        organization_number.value = supplier.value.organization_number
        link.value = supplier.value.link
        address.value = supplier.value.address
        street.value = supplier.value.street
        postal_code.value = supplier.value.postal_code
        phone.value = supplier.value.phone
        swish.value = supplier.value.swish

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

const refetchData = hideOverlay => {
  setTimeout(hideOverlay, 3000)
}

const onSubmit = () => {

    refForm.value?.validate().then(({ valid }) => {
        if (valid && currentTab.value === 0 && refForm.value.items.length < 10) {
            currentTab.value++
        } else if ((!valid && currentTab.value === 0 && refForm.value.items.length === 10) || (!valid && currentTab.value === 0 && refForm.value.items.length === 13)) {
            currentTab.value++
        } else if (!valid && currentTab.value === 1 && refForm.value.items.length > 10) {
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
            formData.append('swish', swish.value)

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
                            message: 'Uppdaterad leverantör!',
                            error: false
                        }

                        router.push({ name : 'dashboard-admin-suppliers'})
                        emitter.emit('toast', data)
                    }
                    isRequestOngoing.value = false
                })
                .catch((err) => {
                    
                    let data = {
                        message: err.message,
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
                width="auto"
                persistent>
                <VProgressCircular
                indeterminate
                color="primary"
                class="mb-0"/>
            </VDialog>

            <VCol cols="12" md="12">
                <div class="d-flex mt-5 flex-wrap justify-start justify-sm-space-between gap-y-4 gap-x-6">
                    <div class="d-flex align-center">
                        <img
                            width="50"
                            :src="logoBlack"
                        />
                    </div>
                    <div class="d-flex flex-column justify-center">
                        <h6 class="text-md-h4 text-h6 font-weight-medium">
                            AA686OW
                        </h6>
                        <span class="d-flex align-center">
                            På lager 
                            <VIcon icon="tabler-edit" class="ms-1" @click="isConfirmStatusDialogVisible = true"/>
                        </span>
                    </div>
                    <VSpacer />
                    <div class="d-flex gap-4 w-100 w-md-auto">
                        <VBtn
                            variant="tonal"
                            color="secondary"
                            class="mb-2 w-100 w-md-auto"
                            :to="{ name: 'dashboard-admin-stock' }"
                            >
                            Tillbaka
                        </VBtn>

                        <VBtn class="w-100 w-md-auto">
                            Spara
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
                            <VTabs v-model="currentTab" fixed-tabs>
                                <VTab>Fordon</VTab>
                                <VTab>Prisinformation</VTab>
                                <VTab>Utrustningslista</VTab>
                                <VTab>Information om bilen</VTab>
                                <VTab>Fordonsplanering</VTab>
                                <VTab>Kostnader</VTab>
                                <VTab>Dokument</VTab>
                            </VTabs>
                            <VCardText class="px-2 px-md-12">
                                <VWindow v-model="currentTab" class="pt-3">
                                    <!-- Fordon -->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="company"
                                                    disabled
                                                    label="Reg nr"
                                                />
                                            </VCol>   
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    type="number"
                                                    v-model="organization_number"
                                                    label="Miltal"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VSelect
                                                    v-model="state_id"
                                                    label="Märke"
                                                    :items="states"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"/>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VSelect
                                                    v-model="state_id"
                                                    label="Modell"
                                                    :items="states"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"/>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="postal_code"
                                                    label="Generation"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="street"
                                                    label="Kaross"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="phone"
                                                    :rules="[yearValidator]"
                                                    label="Årsmodell"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <AppDateTimePicker
                                                    :key="JSON.stringify(startDateTimePickerConfig)"
                                                    v-model="phone"
                                                    density="compact"
                                                    :config="startDateTimePickerConfig"
                                                    label="Första registreringsdatum"
                                                    clearable
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <AppDateTimePicker
                                                    :key="JSON.stringify(startDateTimePickerConfig)"
                                                    v-model="phone"
                                                    density="compact"
                                                    :config="startDateTimePickerConfig"
                                                    label="Kontrollbesiktning gäller tom"
                                                    clearable
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="swish"
                                                    label="Färg"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="swish"
                                                    label="Drivmedel"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="swish"
                                                    label="Växellåda"
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>
                                    <!-- bank -->
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    type="number"
                                                    v-model="bank"
                                                    label="Inköpspris"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VSelect
                                                    v-model="state_id"
                                                    label="VMB / Moms"
                                                    :items="states"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"/>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VSelect
                                                    v-model="state_id"
                                                    label="Status"
                                                    :items="states"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"/>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="swish"
                                                    type="number"
                                                    label="Försäljningspris"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="swish"
                                                    type="number"
                                                    label="Lägsta försäljningspris"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <AppDateTimePicker
                                                    :key="JSON.stringify(startDateTimePickerConfig)"
                                                    v-model="phone"
                                                    density="compact"
                                                    :config="startDateTimePickerConfig"
                                                    label="Inköpsdatum"
                                                    clearable
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <AppDateTimePicker
                                                    :key="JSON.stringify(startDateTimePickerConfig)"
                                                    v-model="phone"
                                                    density="compact"
                                                    :config="startDateTimePickerConfig"
                                                    label="Försäljningsdag"
                                                    clearable
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>
                                    <!-- contact -->
                                    <VWindowItem class="px-md-5">
                                        Utrustningslista finns inte tillgänglig
                                        <VRow class="px-md-5" v-if="false">
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="name"
                                                    label="Namn"
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>
                                    <VWindowItem class="px-md-5">
                                        <VRow class="px-md-5">
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="name"
                                                    type="number"
                                                    label="Antal nycklar"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VSelect
                                                    v-model="state_id"
                                                    label="Servicebok finns?"
                                                    :items="states"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"/>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VSelect
                                                    v-model="state_id"
                                                    label="Sommardäck finns?"
                                                    :items="states"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"/>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VSelect
                                                    v-model="state_id"
                                                    label="Vinterdäck finns?"
                                                    :items="states"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"/>
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VTextField
                                                    v-model="name"
                                                    label="Senaste service: Mil/datum"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="6">
                                                <VSelect
                                                    v-model="state_id"
                                                    label="Kamrem bytt?"
                                                    :items="states"
                                                    :item-title="item => item.name"
                                                    :item-value="item => item.id"
                                                    autocomplete="off"
                                                    clearable
                                                    clear-icon="tabler-x"/>
                                            </VCol>
                                            <VCol cols="12" md="12">
                                                <VTextField
                                                    v-model="name"
                                                    label="Kamrem bytt vid Mil/datum"
                                                />
                                            </VCol>
                                            <VCol cols="12" md="12">
                                                <VTextarea
                                                    v-model="name"
                                                    rows="5"
                                                    label="Kommenter"
                                                />
                                            </VCol>
                                        </VRow>
                                    </VWindowItem>
                                    <VWindowItem class="px-md-5">
                                        <div class="d-flex align-end justify-sm-space-between gap-x-6 border-bottom-secondary">
                                            <h6 class="text-md-h4 text-h6 font-weight-medium">
                                                Övrigt
                                            </h6>
                                            <VBtn class="w-100 w-md-auto">
                                                Lägg till en uppgift
                                            </VBtn>
                                        </div>
                                        <div class="d-flex justify-content-center mt-10">
                                            Ingen post hittades
                                        </div>

                                        <AppCardActions
        title="Slots"
        @refresh="refetchData"
      >
        <template #before-actions>
          <VChip
            class="me-3"
            color="primary"
            size="small"
          >
            3 Updates
          </VChip>
        </template>

        <VCardText>
          <p><code>app-card-actions</code> also provides <code>before-actions</code> and <code>after-actions</code> slot</p>
          <span>You can find more details in our documentation</span>
        </VCardText>
      </AppCardActions>
                                    </VWindowItem>
                                    <VWindowItem class="px-md-5">
                                        agregar tablas
                                    </VWindowItem>
                                    <VWindowItem class="px-md-5">
                                        agregar documentso
                                    </VWindowItem>
                                </VWindow>
                            </VCardText>
                        </VCardText>
                    </VCard>
                </VForm>
            </VCol>
        </VRow>

        <!-- 👉 Confirm create -->
        <VDialog
            v-model="isConfirmStatusDialogVisible"
            persistent
            class="v-dialog-sm" >
            <!-- Dialog close btn -->
                
            <DialogCloseBtn @click="isConfirmStatusDialogVisible = !isConfirmStatusDialogVisible" />

            <!-- Dialog Content -->
            <VForm
                ref="refForm"
                @submit.prevent="onSubmit">
                <VCard title="Redigera status">
                    <VDivider />
                    <VCardText>
                        <VSelect
                            v-model="state_id"
                            label="Status"
                            :items="states"
                            :item-title="item => item.name"
                            :item-value="item => item.id"
                            autocomplete="off"
                            :rules="[requiredValidator]"/>
                    </VCardText>

                    <VCardText class="d-flex justify-end gap-3 flex-wrap">
                        <VBtn
                            color="secondary"
                            variant="tonal"
                            @click="isConfirmStatusDialogVisible = false">
                            Avbryt
                        </VBtn>
                        <VBtn type="submit">
                            Uppdatera status
                        </VBtn>
                    </VCardText>
                </VCard>
            </VForm>
        </VDialog>
    </section>
</template>

<style scoped>
    .v-btn--disabled {
        opacity: 1 !important;
    }

    .border-bottom-secondary {
        border-bottom: 1px solid #d9d9d9;
        padding-bottom: 5px;
    }

    .justify-content-center {
        justify-content: center !important;
    }
</style>

<route lang="yaml">
    meta:
    action: edit
    subject: suppliers
</route>