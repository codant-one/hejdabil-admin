<script setup>

import InvoiceProductEdit from "@/components/invoice/InvoiceProductEdit.vue"
import draggable from 'vuedraggable'
import { themeConfig } from '@themeConfig'
import { formatNumber } from '@/@core/utils/formatters'
import { requiredValidator } from '@validators'
import logoBlack from '@images/logo_black.png'

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
    clients: {
        type: Object,
        required: true,
    },
    suppliers: {
        type: Object,
        required: true,
    },
    invoices: {
        type: Object,
        required: true,
    },
    invoice_id: {
        type: Number,
        required: true,
    },
    role: {
        type: String,
        required: true,
    },
    userData: {
        type: Object,
        required: true,
    },
    company: {
        type: Object,
        required: true,
    },
    total: {
        type: Number,
        required: true,
    },
    amount_discount: {
        type: Number,
        required: true,
    },
    billing: {
        type: Object,
        required: false
    },
    isCreated: {
        type: Boolean,
        required: true
    },
    isCredit: {
        type: Boolean,
        required: true
    }
})

const emit = defineEmits([
    'push',
    'remove',
    'delete',
    'setting',
    'data',
    'edit',
    'discount'
])

const route = useRoute()

const clients = ref(props.clients)
const client = ref(null)
const suppliers = ref(props.suppliers)
const company = ref(props.company)
const subtotal = ref(props.total)
const total = ref('0.00')
const taxOptions = ref([0, 12, 20, 25, "Custom"]);
const selectedTax = ref(0);
const selectedDiscount = ref(0)
const selectedDiscountTemp = ref(0)
const discountOptions = ref([0, 20, 30, 50]);
const discountApplied = ref(false)
const amountDiscount = ref(props.amount_discount)
const isCustomTax = computed(() => selectedTax.value === "Custom");
const isMobile = ref(false)

const isConfirmDiscountVisible = ref(false)
const isAlertDiscountVisible = ref(false)

const invoice = ref({
    id: 1,
    days: 1,
    client_id: null,
    supplier_id: null,
    invoice_date: null,
    due_date: null,
    subtotal: 0,
    tax: 0,
    total: 0,
    reference: null,
    details: structuredClone(toRaw(props.data))
})

const extractDaysFromNetTermSplit = term => {
    const parts = term.split(/\s+/);
    const daysIndex = parts.findIndex(part => /dagar?/i.test(part));
    return daysIndex > -1 ? parseInt(parts[daysIndex - 1]) : null;
}

watch(() => props.company, (val) => {
    company.value = val

    if(props.billing) {
        invoice.value.id = route.path.includes('/duplicate/') ? (company.value.billings.length + 1) : props.billing.invoice_id
    } else if(props.role === 'Supplier' && company.value.billings) {
        invoice.value.id = company.value.billings.length + 1
    } else if(props.role === 'User' && company.value.billings) {
        invoice.value.id = company.value.billings.length + 1
    } else
        invoice.value.id = props.invoice_id + 1
})

watch(props.data, val => {
  invoice.value.details = { ...val }
})

watch(() => props.amount_discount, (val) => {
    amountDiscount.value = val

    var tax = invoice.value.tax / 100

    total.value = ((subtotal.value * tax) + subtotal.value - amountDiscount.value).toFixed(2)
    invoice.value.total = ((subtotal.value * tax) + subtotal.value - amountDiscount.value).toFixed(2)
})

watch(() => props.total, (val) => {
    subtotal.value = val

    var tax = invoice.value.tax / 100
    total.value = ((val * tax) + val - amountDiscount.value).toFixed(2)

    invoice.value.total = ((val * tax) + val - amountDiscount.value).toFixed(2)
    invoice.value.subtotal = val
})

watch(() => invoice.value.tax, (val) => {
    var tax = val/ 100

    total.value = ((subtotal.value * tax) + subtotal.value - amountDiscount.value).toFixed(2)
    invoice.value.total = ((subtotal.value * tax) + subtotal.value - amountDiscount.value).toFixed(2)
    invoice.value.subtotal = (subtotal.value).toFixed(2)
})

watch(() => invoice.value.days, () => {
    calculateDueDate()
})

watch(() => invoice.value.invoice_date, () => {
    calculateDueDate()
})

onMounted(() => {
    checkIfMobile()
   
    window.addEventListener('resize', checkIfMobile);

    fetchData()
})

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768;
}

async function fetchData() {

    if(props.billing) {
        invoice.value.id = route.path.includes('/duplicate/') ? (company.value?.billings?.length + 1) : props.billing.invoice_id
        invoice.value.reference = props.billing.reference
        invoice.value.invoice_date = props.billing.invoice_date
        invoice.value.due_date = props.billing.due_date
        invoice.value.days = extractDaysFromNetTermSplit(props.billing.payment_terms)
        invoice.value.supplier_id = props.billing.supplier_id ?? null
        invoice.value.client_id = props.billing.client_id
        invoice.value.tax = props.billing.tax 

        selectedTax.value = taxOptions.value.includes(props.billing.tax) ? props.billing.tax : "Custom";
        client.value = props.billing.client

        selectedDiscount.value = props.billing.discount
        selectedDiscountTemp.value = props.billing.discount
        
    } else {

        const invoice_date = new Date();
        const year = invoice_date.getFullYear();
        const month = String(invoice_date.getMonth() + 1).padStart(2, '0');
        const day = String(invoice_date.getDate()).padStart(2, '0');

        invoice.value.invoice_date = `${year}-${month}-${day}`

        if(props.role === 'Supplier' && company.value.billings) {
            invoice.value.id = company.value.billings.length + 1
        } else
            invoice.value.id = props.invoice_id + 1
    }
}

const handleTaxChange = () => {
  if (!isCustomTax.value)
    invoice.value.tax = selectedTax.value
  else
    invoice.value.tax = 0
};

const calculateDueDate = () => {
    if (invoice.value.invoice_date && invoice.value.days) {
        const invoiceDateUTC = new Date(`${invoice.value.invoice_date}T00:00:00Z`);

        const daysToAdd = parseInt(invoice.value.days);
        const dueDateUTC = new Date(invoiceDateUTC);
        dueDateUTC.setUTCDate(invoiceDateUTC.getUTCDate() + daysToAdd);

        const year = dueDateUTC.getUTCFullYear();
        const month = String(dueDateUTC.getUTCMonth() + 1).padStart(2, '0');
        const day = String(dueDateUTC.getUTCDate()).padStart(2, '0');
        const formattedDueDate = `${year}-${month}-${day}`;

        invoice.value.due_date = formattedDueDate;

        emit('data', invoice.value)
    }
}

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

const selectSupplier = async() => {
    var selected = suppliers.value.filter(element => element.id === invoice.value.supplier_id)[0]

    if(selected) {
        company.value = selected.user.user_detail
        company.value.email = selected.user.email
        company.value.billings = selected.billings
        invoice.value.id = selected.billings.length + 1
        clients.value = props.clients.filter(item => item.supplier_id === invoice.value.supplier_id)
    } else {
        company.value = props.company
        invoice.value.id = props.invoice_id + 1
        clients.value = props.clients
    }
    
    invoice.value.client_id = null 

    emit('data', invoice.value)
}

const selectClient = async() => {

    var selected = clients.value.filter(element => element.id === invoice.value.client_id)[0]

    if(selected) {
        client.value = selected 
        invoice.value.reference = client.value.reference
    } else 
        client.value = null

    emit('data', invoice.value)
}

// 👉 Add item function
const addItem = () => {
    var item = {}

    props.invoices.forEach(element => {
        var value = ''
        switch(element.type_id) {
            case 1: 
                value = ''
            break
            case 2:
                value = 1
            break
            case 3:
                value = '0.00'
            break   
        }
        item[parseInt(element.id)] = value
    });

    item[5] = 0
    item[6] = false

    emit('push', item)
}

const addNote = () => {
    emit('push', {note: ''})
}

const editNote = () => {
    emit('edit')
}

const removeNote = (id) => {
  emit('delete', id)
}

const onStart = async (e) => {
  // console.log('oldIndex',e.oldIndex)
}

const onEnd = async (e) => {
    emit('data', invoice.value)
}

const removeProduct = id => {
    emit('remove', id)
}

const deleteProduct = id => {
    emit('delete', id)
}

const inputData = () => {
    emit('data', invoice.value)
}

const discount = () => {
    isConfirmDiscountVisible.value = true
}

const cancelDiscount = () => {
    isConfirmDiscountVisible.value = false
}

const saveDiscount = () => {
    discountApplied.value = false;

    invoice.value.details.forEach(element => {
        if(element?.note === undefined) {
            if(element[5] > 0)
                discountApplied.value = true
        }
    });

    if(discountApplied.value) {
        isAlertDiscountVisible.value = true
        selectedDiscountTemp.value = 0
    } else {
        selectedDiscount.value =  selectedDiscountTemp.value
        emit('discount', selectedDiscount.value)
    }

    isConfirmDiscountVisible.value = false
}

const handleBlur = (element) => {
    const defaults = {
        2: 1,
        3: '0.00',
        5: 0
    };

    Object.entries(defaults).forEach(([index, defaultValue]) => {
        const i = parseInt(index);
        if (element[i] === '' || element[i] === null || isNaN(Number(element[i]))) {
        element[i] = defaultValue;
        }
    });
};

</script>

<template>
    <VCard class="pa-5 pa-md-10" v-if="invoice.details.length > 0">
        <VCardText class="d-block d-md-flex flex-wrap justify-space-between flex-column flex-sm-row print-row rounded" style="background-color:  #F2EFFF;">
            <div class="mt-4 px-4 w-100 w-md-50">
                <div class="d-flex align-center mb-6">
                    <!-- 👉 Logo -->
                    <img
                        v-if="company.logo" 
                        :width="isMobile ? '200' : '200'"
                        :src="themeConfig.settings.urlStorage + company.logo"
                    />
                    <img
                        v-else
                        :width="isMobile ? '200' : '200'"
                        :src="logoBlack"
                        class="me-3"
                    />
                </div>
                <!-- 👉 Invoice Id -->
                <h6 class="d-block d-md-flex align-center font-weight-medium justify-sm-start text-xl mb-1">
                    <span class="me-2 text-start w-40 text-h6">
                         Faktura nr:
                    </span>
                    <span>
                        <VTextField
                            v-model="invoice.id"
                            disabled
                            prefix="#"
                            density="compact"
                            style="inline-size: 10.5rem;"
                        />
                    </span>
                </h6>
                <div class="d-block d-md-flex align-center justify-sm-start mb-1 text-right" v-if="client">
                    <span class="me-2 text-start w-40">Kund nr:</span>
                    <span>
                        <VTextField
                            v-model="client.order_id"
                            disabled
                            prefix="#"
                            density="compact"
                            style="inline-size: 10.5rem;"
                        />
                    </span>
                </div>
                <!-- 👉 Issue Date -->
                <div class="d-block d-md-flex align-center justify-sm-start mb-1 md:text-right">
                    <span class="me-2 text-start w-40">
                        Fakturadatum:
                    </span>

                    <span style="inline-size: 10.5rem;">
                        <VTextField
                            v-if="props.isCredit"
                            v-model="invoice.invoice_date"
                            disabled
                            density="compact"
                            style="inline-size: 10.5rem;"
                        />
                        <AppDateTimePicker
                            v-else
                            :key="JSON.stringify(startDateTimePickerConfig)"
                            v-model="invoice.invoice_date"
                            density="compact"
                            placeholder="YYYY-MM-DD"
                            :rules="[requiredValidator]"
                            :config="startDateTimePickerConfig"
                            @input="inputData"
                            clearable
                        />
                    </span>
                </div>

                <!-- 👉 Due Date -->
                <div class="d-block d-md-flex  align-center justify-sm-start mb-0">
                    <span class="me-2 text-start w-40">
                        Förfallodatum:
                    </span>

                    <span style="min-inline-size: 10.5rem;">
                        <VTextField
                            v-if="props.isCredit"
                            v-model="invoice.due_date"
                            disabled
                            density="compact"
                            style="inline-size: 10.5rem;"
                        />
                        <AppDateTimePicker
                            v-else
                            v-model="invoice.due_date"
                            density="compact"
                            placeholder="YYYY-MM-DD"
                            readonly
                        />
                    </span>
                </div>

                <!-- 👉 Days -->
                <div class="d-block d-md-flex align-center justify-sm-start mb-0 mt-2">
                    <span class="me-2 text-start w-40">
                        Betalningsvillkor:
                    </span>

                    <span style="width: 10.5rem;">
                        <VTextField
                            v-model="invoice.days"
                            type="number"
                            label="Dagar"
                            :disabled="props.isCredit"
                            :min="0"
                        />
                    </span>
                </div>   
                <p class="mt-5 mb-0 text-sm" v-if="client">Efter förfallodagen debiteras ränta enligt räntelagen.</p>           
            </div>
            <div class="pa-sm-4 text-right d-flex flex-column w-100 w-md-50">
                <h1 class="mb-0 text-center faktura mt-5 mt-md-0">
                    {{ 
                        invoice.state_id === 9 ? 
                        'KREDIT FAKTURA' : 
                        (
                            parseInt(invoice.days) === 0 ?
                            'KONTANT FAKTURA' :
                            'FAKTURA' 
                        )
                    }}
                </h1>
                <h3 class="mb-0 mt-2" v-if="client">
                    {{ client.fullname }}
                </h3>
                <p class="mb-0 mt-2" v-if="client" style="min-width: 250px;">
                    <VTextField
                        v-model="invoice.reference"
                        label="Vår referens"
                        :disabled="props.isCredit"
                        @input="$emit('data', invoice)"
                    />
                </p> 
                <div class="d-flex flex-column align-center justify-sm-end mb-0 mt-auto" v-if="client">
                    <span class="text-h6 font-weight-medium w-100 my-3">
                        Faktureringsadress
                    </span>
                    <span class="d-flex flex-column w-100">
                        <span>{{ client.address }}</span>
                        <span>{{ client.postal_code }}</span>
                        <span>{{ client.street }}</span>
                    </span>
                </div>
            </div>
        </VCardText>

        <VCardText class="d-flex flex-wrap justify-space-between flex-column flex-sm-row gap-y-5 gap-4 p-0 w-100 w-md-auto">
            <div class="my-sm-4 w-100">
                <h6 class="text-h6 font-weight-medium" v-if="props.role === 'SuperAdmin' || props.role === 'Administrator'">
                    Leverantörer
                </h6>
                <VAutocomplete
                    v-if="props.role === 'SuperAdmin' || props.role === 'Administrator'"
                    v-model="invoice.supplier_id"
                    :items="suppliers"
                    :item-title="item => item.full_name"
                    :item-value="item => item.id"
                    :disabled="props.isCredit"
                    placeholder="Leverantörer"
                    class="mb-3 w-100 w-md-50"
                    @update:modelValue="selectSupplier"
                    clearable
                />
                <h6 class="text-h6 font-weight-medium"> Kunder </h6>
                <VAutocomplete
                    v-model="invoice.client_id"
                    :items="clients"
                    :item-title="item => item.fullname"
                    :item-value="item => item.id"
                    :disabled="props.isCredit"
                    placeholder="Kunder"
                    class="mb-3 w-100 w-md-50"
                    :rules="[requiredValidator]"
                    @update:modelValue="selectClient"
                    clearable
                />
            </div>
        </VCardText>

        <VDivider />

        <!-- <InvoiceProductEdit
            v-else
            :id="index"
            :data="element"
            :invoices="invoices"
            :isCreated="props.isCreated"
            @remove-product="removeProduct"
            @delete-product="deleteProduct"
            @edit-product="editx"

            componente dentro del draggable
        /> -->

        <!-- 👉 Add purchased products -->
        <VCardText class="add-products-form pt-0 px-0">
            <draggable
                class="my-4"
                v-model="invoice.details" 
                tag="div" 
                item-key="index" 
                @start="onStart" 
                @end="onEnd">
                <template #item="{ element, index }">
                    <div class="draggable-item py-2 px-0 px-md-2 d-flex">
                        <div class="d-flex w-100" v-if="element?.note !== undefined">
                            <VTextarea 
                                v-model="element.note" 
                                label="Notera" 
                                placeholder="Notera" 
                                rows="2" 
                                class="mt-1"
                                @input="editNote"/>
                            <VBtn
                                size="x-small"
                                icon="tabler-x"
                                variant="text"
                                @click="removeNote(index)">
                            </VBtn>
                        </div>
                        <template v-else >
                            <div class="d-flex flex-column justify-space-between p-0" v-if="selectedDiscount > 0">
                                 <VCheckbox
                                    v-if="selectedDiscount > 0"
                                    class="pe-2"
                                    v-model="element[6]"
                                    color="primary"
                                    @update:modelValue="$emit('edit')"
                                />
                            </div>
                            <div class="w-100">
                                <div class="add-products-header d-none d-md-flex px-5 w-100">
                                    <table class="w-100">
                                        <thead>
                                            <tr>
                                                <template v-for="(invoice, index) in invoices" :key="invoice.id">
                                                    <td :style="`width: ${invoice.type_id === 1 ? '40' : '15' }%;`">
                                                        <span class="text-base font-weight-bold">
                                                        {{ invoice.name }}
                                                        </span>
                                                    </td>
                                                </template>
                                                <td style="width: 15%">
                                                    <span class="text-base font-weight-bold">
                                                        Rabbat
                                                    </span>
                                                </td>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>                            
                                <VCard
                                    flat
                                    border
                                    class="d-flex flex-row"
                                    style="box-shadow: none !important; border-radius: 12px !important;"
                                >
                                    <!-- 👉 Left Form -->
                                    <div class="pa-2 pa-md-5 flex-grow-1">
                                        <table class="w-100">
                                            <thead>
                                                <tr>
                                                    <template v-for="(invoice, index) in invoices" :key="invoice.id">
                                                        <td :style="`width: ${invoice.type_id === 1 ? '40' : '15' }%;`" class="pe-2" style="vertical-align: top;">
                                                            <VTextarea
                                                                v-if="invoice.type_id === 1"
                                                                v-model="element[invoice.id]"
                                                                :label="invoice.description"
                                                                :placeholder="invoice.description"
                                                                rows="3"
                                                                :readonly="element.disabled"
                                                                :rules="[requiredValidator]"
                                                            />
                                                            <VTextField
                                                                v-if="invoice.type_id === 2"
                                                                v-model="element[invoice.id]"
                                                                type="number"
                                                                :label="invoice.name"
                                                                :placeholder="invoice.name"
                                                                :min="1"
                                                                :readonly="element.disabled"
                                                                :rules="[requiredValidator]"
                                                                @input="$emit('edit')"
                                                                @blur="() => handleBlur(element)"
                                                            />
                                                            <VTextField
                                                                v-if="invoice.type_id === 3"
                                                                v-model="element[invoice.id]"
                                                                type="number"
                                                                :label="invoice.name"
                                                                :placeholder="invoice.name"
                                                                :min="0"
                                                                :step="0.01"
                                                                :readonly="element.disabled"
                                                                @input="$emit('edit')"
                                                                :rules="[requiredValidator]"
                                                                :disabled="invoice.name === 'Belopp'"
                                                                @blur="() => handleBlur(element)"
                                                            />
                                                        </td>
                                                    </template>
                                                     <td style="width: 15%; vertical-align: top;" class="pe-2">
                                                        <VTextField
                                                            :disabled="selectedDiscount > 0"
                                                            v-model="element[5]"
                                                            append-icon="tabler-percentage"
                                                            type="number"
                                                            :label="invoice.name"
                                                            :placeholder="invoice.name"
                                                            :min="0"
                                                            :readonly="element.disabled"
                                                            @input="$emit('edit')"
                                                            @blur="() => handleBlur(element)"
                                                        />
                                                    </td>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                    <!-- 👉 Item Actions -->
                                    
                                </VCard>
                            </div>
                            <div class="d-flex flex-column justify-space-between p-0">
                                <VBtn 
                                    :disabled="index === 0 ? true : false"
                                    size="x-small"
                                    icon="tabler-x"
                                    variant="text"
                                    @click="deleteProduct(index)">
                                </VBtn>
                            </div>
                        </template>
                    </div>                    
                </template>
            </draggable>
            <div class="mt-4">
                    <VMenu>
                    <template #activator="{ props }">
                        <VBtn v-bind="props" class="me-3">
                            Lägg till
                        </VBtn>
                    </template>
                    <VList>
                      <VListItem @click="addItem">
                        <template #prepend>
                          <VIcon icon="mdi-plus" />
                        </template>
                        <VListItemTitle>Ny produktrad</VListItemTitle>
                      </VListItem>
                      <VListItem @click="addNote">
                        <template #prepend>
                          <VIcon icon="mdi-plus" />
                        </template>
                        <VListItemTitle>Ny textrad</VListItemTitle>
                      </VListItem>
                      <VListItem @click="discount">
                        <template #prepend>
                          <VIcon icon="mdi-plus" />
                        </template>
                        <VListItemTitle>Skatteavdrag för ROT / RUT</VListItemTitle>
                      </VListItem>
                    </VList>
                  </VMenu>
            </div>
        </VCardText>

        <VDivider />

        <!-- 👉 Total Amount -->
        <VCardText class="d-flex justify-space-between flex-wrap flex-column flex-sm-row px-0">
            <VSpacer />
            <div class="my-4">
                <table class="w-100">
                    <tbody>
                        <tr>
                            <td class="pe-16"> Netto:</td>
                            <td :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'">
                                <h6 class="text-sm">
                                    {{ formatNumber(subtotal) }} kr
                                </h6>
                            </td>
                        </tr>
                        <tr>
                            <td class="pe-16"> Moms: </td>
                            <td :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'">
                                <h6 class="text-sm">
                                    <VSelect
                                        v-model="selectedTax"
                                        :items="taxOptions"
                                        label="Moms"
                                        append-icon="tabler-percentage"
                                        @update:modelValue="handleTaxChange"
                                        style="width: 150px;"
                                        />

                                    <VTextField
                                        v-if="isCustomTax"
                                        v-model.number="invoice.tax"
                                        class="mt-2"
                                        type="number"
                                        label="Customized Moms"
                                        :min="0"
                                        :step="0.01"
                                        suffix="%"
                                        style="width: 150px;"
                                    />
                                </h6>
                            </td>
                        </tr>
                        <tr v-if="selectedDiscount > 0">
                            <td class="pe-16">
                                Preliminär skattereduktion {{ selectedDiscount }}% av {{ formatNumber(subtotal) }} kr:
                            </td>
                            <td :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'">
                                <h6 class="text-sm">
                                    - {{ formatNumber(amountDiscount) }} kr
                                </h6>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <VDivider class="mt-4 mb-3" />

                <table class="w-100">
                    <tbody>
                        <tr>
                            <td class="pe-16">Summa: </td>
                            <td :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'">
                                <h6 class="text-sm">
                                    {{ formatNumber(total) }} kr
                                </h6>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCardText>

        <VDivider />

        <VCardText class="mb-sm-4 px-0">
            <VRow>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6">
                        Adress
                    </span>
                    <span class="d-flex flex-column">
                        <span class="text-footer">{{ company.address }}</span>
                        <span class="text-footer">{{ company.postal_code }}</span>
                        <span class="text-footer">{{ company.street }}</span>
                        <span class="text-footer">{{ company.phone }}</span>
                    </span>
                    <span class="me-2 text-h6 mt-2">
                        Bolagets säte
                    </span>
                    <span class="text-footer"> Stockholm, Sweden </span>
                    <span class="me-2 text-h6 mt-2" v-if="company.swish">
                        Swish
                    </span>
                    <span class="text-footer" v-if="company.swish"> {{ company.swish }} </span>
                </VCol>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6">
                        Org.nr.
                    </span>
                    <span class="text-footer"> {{ company.organization_number }} </span>
                    <span class="me-2 text-h6 mt-2" v-if="company.vat">
                        Vat
                    </span>
                    <span class="text-footer"> {{ company.vat }} </span>
                    <span class="me-2 text-h6 mt-2" v-if="company.bic">
                        BIC
                    </span>
                    <span class="text-footer" v-if="company.bic"> {{ company.bic }} </span>

                    <span class="me-2 text-h6 mt-2" v-if="company.plus_spin">
                        Plusgiro
                    </span>
                    <span class="text-footer" v-if="company.plus_spin"> {{ company.plus_spin }} </span>
                </VCol>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6" v-if="company.link">
                        Webbplats
                    </span>
                    <span class="text-footer" v-if="company.link"> {{ company.link }} </span>
                    <span class="me-2 text-h6 mt-2">
                        Företagets e-post
                    </span>
                    <span class="text-footer"> {{ company.email }} </span>
                </VCol>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6" v-if="company.bank">
                      Bank
                    </span>
                    <span class="text-footer" v-if="company.bank"> {{ company.bank }} </span>

                    <span class="me-2 text-h6 mt-2" v-if="company.iban">
                        Bankgiro
                    </span>
                    <span class="text-footer"> {{ company.iban }} </span>

                    <span class="me-2 text-h6 mt-2" v-if="company.account_number">
                        Kontonummer
                    </span>
                    <span class="text-footer" v-if="company.account_number"> {{ company.account_number }} </span>
                    
                    <span class="me-2 text-h6 mt-2" v-if="company.iban_number">
                      Iban nummer
                    </span>
                    <span class="text-footer" v-if="company.iban_number"> {{ company.iban_number }} </span>

                </VCol>
            </VRow>
        </VCardText>
    </VCard>

    <!-- 👉 Confirm send -->
    <VDialog
      v-model="isConfirmDiscountVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="cancelDiscount" />

      <!-- Dialog Content -->
      <VCard title="Tillämpa skatteavdrag">
        <VDivider class="mt-4"/>
        <VCardText class="d-flex justify-content-between">
            Välj preliminär skatteavdrag
        
            <VSpacer />

            <VSelect
                v-model="selectedDiscountTemp"
                :items="discountOptions"
                label="Skattereduktion"
                append-icon="tabler-percentage"
            />

        </VCardText>
     

        <VCardText class="d-flex justify-end gap-3 flex-wrap">
          <VBtn
            color="secondary"
            variant="tonal"
            @click="cancelDiscount">
              Avbryt
          </VBtn>
          <VBtn @click="saveDiscount">
              Spara
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm discount -->
    <VDialog
      v-model="isAlertDiscountVisible"
      persistent
      class="v-dialog-sm" >
      <!-- Dialog close btn -->
        
      <DialogCloseBtn @click="isAlertDiscountVisible = false" />

      <!-- Dialog Content -->
      <VCard title="Apply discount">
        <VDivider class="mt-4"/>
        <VCardText class="d-flex justify-content-between">
            You cannot apply two discounts to one invoice.
        </VCardText>
        <VCardText class="d-flex justify-end gap-3 flex-wrap">
            <VBtn @click="isAlertDiscountVisible = false">
                OK
            </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

</template>

<style scoped>
    .draggable-item:hover {
        background-color: #e9ecef;
        cursor: move;
        border-radius: 8px;
    }

    .faktura {
        font-size: 32px;
        color: #57F287;
        border-top: 2px solid #57F287;
        border-bottom: 2px solid #57F287;
    }
    
    .w-70 {
        width: 70% !important;
    }

    .text-footer {
        font-size: 0.75rem !important;
    }

    @media (max-width: 767px) {
        .faktura {
            font-size: 16px;
        }
    }
</style>
