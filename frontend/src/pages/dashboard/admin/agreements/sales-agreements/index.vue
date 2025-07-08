<script setup>

import router from '@/router'
import { emailValidator, requiredValidator, phoneValidator, urlValidator } from '@/@core/utils/validators'
import { useSuppliersStores } from '@/stores/useSuppliers'

const suppliersStores = useSuppliersStores()

const emitter = inject("emitter")

const isRequestOngoing = ref(false)

const isFormValid = ref(false)
const refForm = ref()
const currentTab = ref(0)
const isMobile = ref(false)

const selected_option = ref(null) 
const selectcar = ref([{ title: 'DD220', value: 1 },{ title: 'SC224', value: 2 }, { title: 'LM110', value: 3 }, { title: 'ND100', value: 4 }])
const registration = ref(null)
const brand = ref(null)
const model = ref(null)
const model_year = ref(0)
const color = ref(null)
const chassis = ref(null)
const miltal = ref(0)
const first_date = ref(null);
const date_sale = ref(null);
const selected_guarantee = ref(null)
const guarantee_items = ref([{title: 'Nej, ingen garanti', value: 1}, {title: 'Trafiksäkerhetsgaranti', value: 2}, 
                             {title: 'Trygghetsgaranti av bilhandlaren', value: 3}, {title: 'Garanti gruppen', value: 4},
                             {title: 'Garanti Partner', value: 5}]);
const selected_guarantee_type = ref(null);
const guarantee_type_items = ref([{title: '1 månads garanti', value: 1}, {title: '1 månad / 100 Mil', value: 2},
                                 {title: '3 månader garanti', value: 3}, {title: '3 månader / 300 Mil', value: 4}])
const selected_company_insurance = ref(null)
const company_insurance_items = ref([{title:'Ingen försäkring', value: 1}, {title:'Kund har egen försäkring', value: 2},
                                  {title:'Länsförsäkringar', value: 3}, {title:'Ica försäkring', value: 4}])
const selected_type_insurance = ref(null);                                 
const type_insurance_items = ref([{title:'Ingen', value: 1}, {title:'Trafikförsäkring', value: 2},
                                  {title:'Halvförsäkring', value: 3}, {title:'Helförsäkring', value: 4}])
const items = ['Försäljning', 'Inbytesfordon', 'Kund', 'Pris']
const Ombudsman = ref(null)
const totalTabs = items.length

//const tab 2
const brand_2 = ref(null)
const model_2 = ref(null)
const model_year_2 = ref(0)
const meter_reading = ref(null)
const card_body = ref(null)
const color_2 = ref(null)
const regnr_2 = ref(null)
const chassis_2 = ref(null)
const first_date_2 = ref(null)
const trade_price = ref(0)
const selected_residual = ref(null)
const residual_debt = ref([ { label: 'Ja', value: 1 },{ label: 'Nej', value: 2 }]);
const residual_debt_price = ref(0)
const selected_vat = ref(null)
const vat_items = ref([{title:'Moms', value: 1}, {title:'VMB', value: 2}])
const remaining_paid_to = ref(null)
const selected_ransom_offer = ref(null)
const ransom_offer_items = ref([{title:'ja', value: 1},{title: 'nej', value: 2}])

//const tab 3
const org_number=ref(null)
const selected_buyer = ref(null)
const buyer_items = ref([{title:'Konsument', value: 1}, {title:'Företag', value:2}])
const name_buyer = ref(null)
const address = ref(null)
const postal_code = ref(null)
const phone = ref(null)
const email_buyer = ref(null)
const selected_legitimation = ref(null)
const legitimation_items = ref([{title:'Pass', value: 1}, {title:'Körkort', value:2}, {title:'Mobilt bank-ID', value:3}]) 

//Const tab 4
const price_2 = ref(0)
const selected_currency = ref(null)
const currency_items = ref([{title:'SEK', value: 1}, {title: 'EUR', value: 2}, {title: 'USD', value: 3}])
const selected_taxes_type = ref(null)
const discount = ref(0)
const registration_fee = ref(0)
const mid_price = ref(0)

const which_vat = computed(()=>{
    const price_1 = Number(price_2.value)||0;
    const discount_ = Number(discount.value)||0;
    const registration_fee_2 = Number(registration_fee.value)|| 0;
    if(selected_taxes_type.value == 1)
    {
        return ((price_1 + registration_fee_2 - discount_) *0.2); 
    }
    else 
    return 0;
})

const price_without_vat = computed(()=>{
    const price_1 = Number(price_2.value)||0;
    const discount_ = Number(discount.value)||0;
    const registration_fee_2 = Number(registration_fee.value)|| 0;
    if(selected_taxes_type.value == 1)
    {
        return price_1 + registration_fee_2 - discount_ - which_vat.value ;
    }
    else
    return price_1 - discount_;
})

const total_price = computed(()=>{
    const price_1 = Number(price_2.value)||0;
    const discount_ = Number(discount.value)||0;
    const registration_fee_2 = Number(registration_fee.value)|| 0;

    return price_1 + registration_fee_2 - discount_;
})

const price_whitout_trade_price = computed(()=>{
    return total_price.value - trade_price.value;
})

const preTab = () => {
  if (currentTab.value !== 1)
    currentTab.value -= 1
}

const nextTab = () => {
  if (currentTab.value !== totalTabs)
    currentTab.value += 1
}

const fair_value = computed(() => {
  const price1 = Number(trade_price.value) || 0;
  const price2 = Number(residual_debt_price.value) || 0;
  
  return price1 - price2;
})

onMounted(async () => {
    checkIfMobile()
   
    window.addEventListener('resize', checkIfMobile);
})

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768;
}

const onSubmit = () => {}

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
                    <div class="d-flex flex-column justify-center">
                        <h6 class="text-md-h4 text-h6 font-weight-medium">
                            Försäljningsavtal 😃🌟
                        </h6>
                    </div>
                    <VSpacer />
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
                            >
                            <VTab
                                v-for="(item, index) in items.length"
                                :key="index"
                            >
                                {{ items[index] }}
                            </VTab> 
                            </VTabs>

                            <VCardText>
                            <VWindow v-model="currentTab">
                                <!--Försäljning-->
                                <VWindowItem
                                class="px-md-14"
                                :value="0"
                                >
                                    <VRow class="mt-5">
                                        <VCol cols="6" md="3">
                                            <label for="">Stockbilar</label>
                                            <VSelect
                                                v-model="selected_option"
                                                placeholder="Välj en bil"
                                                :items="selectcar"
                                                item-title="title"      
                                                item-value="value"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                               />
                                        </VCol>
                                        <VCol cols="6" md="6">
                                            <label for="">Regnr</label>
                                            <VTextField
                                                v-model="registration"
                                                placeholder="Regnr"
                                            />
                                        </VCol>
                                    </VRow>
                                    <VRow class="mt-5">
                                        <VCol cols="12" md="12">
                                            <h3>Fordon</h3>
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <label for="">Märke</label>
                                            <VTextField
                                                v-model="brand"
                                                :rules="[requiredValidator]"
                                            />   
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <label for="">Modell</label>
                                            <VTextField
                                                v-model="model"
                                                :rules="[requiredValidator]"
                                            /> 
                                        </VCol>

                                        <VCol cols="12" md="6">
                                            <label for="">Årsmodell</label>
                                            <VTextField
                                                v-model="model_year"
                                                type="number"
                                                :rules="[requiredValidator]"
                                            />   
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <label for="">Färg</label>
                                            <VTextField
                                                v-model="color"
                                                :rules="[requiredValidator]"
                                            /> 
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <label for="">Chassinummer</label>
                                            <VTextField
                                                v-model="chassis"
                                                :rules="[requiredValidator]"
                                            /> 
                                        </VCol>

                                        <VCol cols="12" md="6">
                                            <label for="">Miltal</label>
                                            <VTextField
                                                v-model="miltal"
                                                type="number"
                                                :rules="[requiredValidator]"
                                            />   
                                        </VCol>

                                        <VCol cols="12" md="6">
                                            <label for="">Första registreringsdatum</label>
                                            <AppDateTimePicker
                                                    :key="JSON.stringify(startDateTimePickerConfig)"
                                                    v-model="first_date"
                                                    density="compact"
                                                    placeholder="YYYY-MM-DD"
                                                    :config="startDateTimePickerConfig"
                                                    clearable
                                                />
                                        </VCol>

                                        <VCol cols="12" md="6">
                                            <label for="">Försäljningsdatum</label>
                                            <AppDateTimePicker
                                                    :key="JSON.stringify(startDateTimePickerConfig)"
                                                    v-model="date_sale"
                                                    density="compact"
                                                    placeholder="YYYY-MM-DD"
                                                    :config="startDateTimePickerConfig"
                                                    clearable
                                                />
                                        </VCol>

                                        <VCol cols="12" md="6">
                                            <label for="">Garanti</label>
                                            <VSelect
                                                v-model="selected_guarantee"
                                                :items="guarantee_items"
                                                placeholder="Vänligen välj"
                                                item-title="title"      
                                                item-value="value"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                               /> 
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <label for="">Typ av garanti</label>
                                            <VSelect
                                                v-model="selected_guarantee_type"
                                                :items="guarantee_type_items"
                                                placeholder="välj"
                                                item-title="title"      
                                                item-value="value"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                            />    
                                        </VCol>

                                        <VCol cols="12" md="6">
                                            <label for="">Försäkringsbolag</label>
                                            <VSelect
                                                v-model="selected_company_insurance"
                                                :items="company_insurance_items"
                                                placeholder="välj"
                                                item-title="title"      
                                                item-value="value"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                            />    
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <label for="">Ombudsnummer</label>
                                            <VTextField
                                                v-model="Ombudsman"
                                            /> 
                                        </VCol>

                                        <VCol cols="12" md="6">
                                            <label for="">Försäkringsbolag</label>
                                            <VSelect
                                                v-model="selected_type_insurance"
                                                :items="type_insurance_items"
                                                placeholder="välj"
                                                item-title="title"      
                                                item-value="value"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                            />    
                                        </VCol>
                                        
                                    </VRow>
                                </VWindowItem>

                                <!------------------------Inbytesfordon------------------------------->
                                <VWindowItem
                                class="px-md-14"
                                :value="1"
                                >
                                    <h4>Inbytesfordon</h4>
                                    <VRow class="mt-5">
                                        <VCol cols="12" md="6">
                                            <label for="">Märke</label>
                                            <VTextField
                                                v-model="brand_2"
                                            /> 
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <label for="">Modell</label>
                                            <VTextField
                                                v-model="model_2"
                                            /> 
                                        </VCol>
                                        <VCol cols="12" md="3">
                                            <label for="">Årsmodell</label>
                                            <VTextField
                                                v-model="model_year_2"
                                                type="number"
                                            /> 
                                        </VCol> 
                                        <VCol cols="12" md="3">
                                            <label for="">Mätarställning</label>
                                            <VTextField
                                                v-model="meter_reading"
                                            /> 
                                        </VCol>
                                        <VCol cols="12" md="3">
                                            <label for="">Kaross</label>
                                            <VTextField
                                                v-model="card_body"
                                            /> 
                                        </VCol>
                                        <VCol cols="12" md="3">
                                            <label for="">Färg</label>
                                            <VTextField
                                                v-model="color_2"
                                            /> 
                                        </VCol>
                                        
                                        <VCol cols="12" md="3">
                                            <label for="">Regnr</label>
                                            <VTextField
                                                v-model="regnr_2"
                                            /> 
                                        </VCol> 
                                        <VCol cols="12" md="3">
                                            <label for="">Chassinummer</label>
                                            <VTextField
                                                v-model="chassis_2"
                                            /> 
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <label for="">Försäljningsdatum</label>
                                            <AppDateTimePicker
                                                    :key="JSON.stringify(startDateTimePickerConfig)"
                                                    v-model="first_date_2"
                                                    density="compact"
                                                    placeholder="YYYY-MM-DD"
                                                    :config="startDateTimePickerConfig"
                                                    clearable
                                                />
                                        </VCol>
                                        <VCol cols="12" md="6">
                                            <label for="">Inbytespris kr</label>
                                            <VTextField
                                                v-model="trade_price"
                                                type="number"
                                            /> 
                                        </VCol>
                                        <VCol cols="6" md="2">
                                            <label for="">Restskuld</label>
                                            <VRadioGroup
                                                v-model="selected_residual"
                                                inline
                                                # :rules="[requiredValidator]"
                                            >
                                                <VRadio
                                                v-for="option in residual_debt"
                                                :key="option.value"
                                                :label="option.label"
                                                :value="option.value"
                                                />
                                            </VRadioGroup>
                                        </VCol>
                                        <VCol cols="6" md="2">
                                            <label for="">Restskuld kr</label>
                                            <VTextField
                                                v-model="residual_debt_price"
                                                type="number"
                                            /> 
                                        </VCol>
                                        
                                        <VCol cols="6" md="2">
                                            <label for="">Verkligt värde kr</label>
                                            <VTextField
                                                :model-value="fair_value"
                                                readonly 
                                            />
                                        </VCol>
                                        <VCol cols="6" md="4">
                                            <label for="">Moms / VMB</label>
                                            <VSelect
                                                v-model="selected_vat"
                                                :items="vat_items"
                                                placeholder="välj"
                                                item-title="title"      
                                                item-value="value"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                            />

                                        </VCol>
                                        <VCol cols="6" md="4">
                                            <label for="">Restskuld betalas till</label>
                                            <VTextField
                                                v-model="remaining_paid_to"
                                            />

                                        </VCol>
                                        <VCol cols="6" md="4">
                                            <label for="">Har lösenoffert beställts</label>
                                            <VSelect
                                                v-model="selected_ransom_offer"
                                                :items="ransom_offer_items"
                                                placeholder="välj"
                                                item-title="title"      
                                                item-value="value"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                            />

                                        </VCol>
                                    </VRow>
                                </VWindowItem>

                                <!--Kund-->
                                <VWindowItem
                                class="px-md-14"
                                :value="2"
                                >

                                    <VRow class="mt-5">
                                       <VCol cols="12" md="6" class="pe-md-32">
                                            <h4>Köpare</h4>
                                            <VRow class="mt-3">
                                                <VCol cols="12" md="12">
                                                    <label for="">Org/personummer</label>
                                                    <VTextField
                                                        v-model="org_number"
                                                    />
                                                </VCol>

                                                <VCol cols="12" md="6">
                                                    <label for="">Köparen är</label>
                                                    <VSelect
                                                        v-model="selected_buyer"
                                                        :items="buyer_items"
                                                        placeholder="välj"
                                                        item-title="title"      
                                                        item-value="value"
                                                        autocomplete="off"
                                                        clearable
                                                        clear-icon="tabler-x"
                                                    />
                                                </VCol>

                                                <VCol cols="12" md="6">
                                                    <label for="">Namn</label>
                                                    <VTextField
                                                        v-model="name_buyer"
                                                        :rules="[requiredValidator]"
                                                    />
                                                </VCol>

                                                <VCol cols="12" md="12">
                                                    <label for="">Adress</label>
                                                    <VTextField
                                                        v-model="address"
                                                    />
                                                </VCol>

                                                <VCol cols="12" md="6">
                                                    <label for="">Postnr. ort</label>
                                                    <VTextField
                                                        v-model="postal_code"
                                                    />
                                                </VCol>

                                                <VCol cols="12" md="6">
                                                    <label for="">Telefon</label>
                                                    <VTextField
                                                        v-model="phone"
                                                    />
                                                </VCol>

                                                <VCol cols="12" md="6">
                                                    <label for="">E-post</label>
                                                    <VTextField
                                                        v-model="email_buyer"
                                                        type="email"
                                                    />
                                                </VCol>
                                                <VCol cols="12" md="6">
                                                    <label for="">Legitimation</label>
                                                    <VSelect
                                                        v-model="selected_legitimation"
                                                        :items="legitimation_items"
                                                        placeholder="välj"
                                                        item-title="title"      
                                                        item-value="value"
                                                        autocomplete="off"
                                                        clearable
                                                        clear-icon="tabler-x"
                                                    />
                                                </VCol>

                                            </VRow>
                                       </VCol>
                                       
                                       <VCol cols="12" md="6" class="ps-md-32">
                                         <h4>Säljare</h4>
                                       </VCol>
                                                                                   
                                    </VRow>
                                    
                                </VWindowItem>

                                <!--Pris-->
                                <VWindowItem
                                class="px-md-14"
                                :value="3"
                                >
                                    <VRow class="mt-5">
                                       <VCol cols="12" md="12">
                                            <h4>Specifikation, pris</h4>
                                       </VCol>
                                       <VCol cols="12" md="6">
                                            <label for="">Pris</label>
                                                <VTextField
                                                    v-model="price_2"
                                                    type="number"
                                                    :rules="[requiredValidator]"
                                                />
                                       </VCol>
                                       <VCol cols="12" md="6">
                                            <label for="">Valuta</label>
                                            <VSelect
                                                v-model="selected_currency"
                                                :items="currency_items"
                                                placeholder="välj"
                                                item-title="title"      
                                                item-value="value"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                                :rule="requiredValidator"
                                            />
                                       </VCol>
                                       
                                       <VCol cols="12" md="6">
                                            <label for="">Moms / VMB / Export</label>
                                            <VSelect
                                                v-model="selected_taxes_type"
                                                :items="vat_items"
                                                placeholder="välj"
                                                item-title="title"      
                                                item-value="value"
                                                autocomplete="off"
                                                clearable
                                                clear-icon="tabler-x"
                                                :rule="requiredValidator"
                                            />
                                       </VCol>
                                       <VCol cols="12" md="6">
                                            <label for="">Varav moms</label>
                                            <VTextField
                                                :model-value="which_vat"
                                                readonly 
                                            />
                                       </VCol>

                                       <VCol cols="12" md="6">
                                            <label for="">Pris ex moms</label>
                                            <VTextField
                                                :model-value="price_without_vat"
                                                readonly 
                                            />
                                       </VCol>

                                       <VCol cols="12" md="6">
                                            <label for="">Rabatt</label>
                                            <VTextField
                                                v-model="discount"
                                                type="number"
                                            />
                                       </VCol>

                                       <VCol cols="12" md="6">
                                            <label for="">Registreringsavgift</label>
                                            <VTextField
                                                v-model="registration_fee"
                                                type="number"
                                            />
                                       </VCol>

                                       <VCol cols="12" md="6">
                                            <label for="">Totalpris</label>
                                            <VTextField
                                                :model-value="total_price"
                                                type="number"
                                                readonly 
                                            />
                                       </VCol>

                                       <VCol cols="12" md="6">
                                            <label for="">Pris på inbytesbil</label>
                                            <VTextField
                                                v-model="trade_price"
                                                type="number"
                                                readonly 
                                            />
                                       </VCol>

                                       <VCol cols="12" md="6">
                                            <label for="">Mellanpris</label>
                                            <VTextField
                                                :model-value="price_whitout_trade_price"
                                                type="number"
                                                readonly 
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
                                    class="mb-3 mb-md-0 me-md-3 w-100 w-md-auto"
                                    @click="currentTab--"
                                    >
                                    Tillbaka
                                </VBtn>
                                <VBtn type="submit" class="w-100 w-md-auto">
                                    {{ (currentTab === 2) ? 'Skicka' : ' Nästa' }}
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
      action: create
      subject: agreements
</route>