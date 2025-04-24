<script setup>

import InvoiceProductEdit from "@/components/invoice/InvoiceProductEdit.vue"
import draggable from 'vuedraggable'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { formatNumber } from '@/@core/utils/formatters'
import { requiredValidator } from '@validators'

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
    supplier: {
        type: Object,
        required: true,
    },
    total: {
        type: Number,
        required: true,
    },
    billing: {
        type: Object,
        required: false
    },
    notes: {
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
    'editNote',
    'orderNote'
])

const route = useRoute()

const clients = ref(props.clients)
const client = ref(null)
const suppliers = ref(props.suppliers)
const supplier = ref(props.supplier)
const subtotal = ref(props.total)
const total = ref('0.00')
const taxOptions = ref([0, 12, 20, 25, "Custom"]);
const selectedTax = ref(0);
const isCustomTax = computed(() => selectedTax.value === "Custom");
const notes = ref(props.notes ?? [])

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
    details: props.data,
    notes: []
})

const extractDaysFromNetTermSplit = term => {
    const parts = term.split(/\s+/);
    const daysIndex = parts.findIndex(part => /days?/i.test(part));
    return daysIndex > -1 ? parseInt(parts[daysIndex - 1]) : null;
}

watch(() => props.supplier, (val) => {
    supplier.value = val
})

watch(props.data, val => {
  invoice.value.details = val
})

watch(() => props.total, (val) => {
    subtotal.value = val

    var tax = invoice.value.tax / 100
    total.value = (val * tax) + val

    invoice.value.total = (val * tax) + val
    invoice.value.subtotal = val
})

watch(() => invoice.value.tax, (val) => {
    var tax = val/ 100

    total.value = (subtotal.value * tax) + subtotal.value
    invoice.value.total = (subtotal.value * tax) + subtotal.value
    invoice.value.subtotal = subtotal.value
})

watch(() => invoice.value.days, () => {
    calculateDueDate()
})

watch(() => invoice.value.invoice_date, () => {
    calculateDueDate()
})

watchEffect(fetchData)

async function fetchData() {

    if(props.billing) {
        invoice.value.id = props.billing.invoice_id + (route.path.includes('/duplicate/') ? 1 : 0)
        invoice.value.reference = props.billing.reference
        invoice.value.invoice_date = props.billing.invoice_date
        invoice.value.due_date = props.billing.due_date
        invoice.value.days = extractDaysFromNetTermSplit(props.billing.payment_terms)
        invoice.value.supplier_id = props.billing.supplier_id ?? null
        invoice.value.client_id = props.billing.client_id
        invoice.value.tax = props.billing.tax 

        selectedTax.value = taxOptions.value.includes(props.billing.tax) ? props.billing.tax : "Custom";
        client.value = props.billing.client
        
    } else {

        const invoice_date = new Date();
        const year = invoice_date.getFullYear();
        const month = String(invoice_date.getMonth() + 1).padStart(2, '0');
        const day = String(invoice_date.getDate()).padStart(2, '0');

        invoice.value.invoice_date = `${year}-${month}-${day}`

        if(props.role === 'Supplier' && supplier.value.billings) {
            invoice.value.id = supplier.value.billings.length + 1
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
        supplier.value = selected
        invoice.value.id = supplier.value.billings.length + 1
        
        clients.value = clients.value.filter(item => item.supplier_id === invoice.value.supplier_id)
    } else {
        supplier.value = []
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

    emit('push', item)
}


const onStart = async (e) => {
  // console.log('oldIndex',e.oldIndex)
}

const onEnd = async (e) => {
    const newNotes = notes.value.flatMap(group => 
        group.filter(item => item.id === e.newIndex)
    ).map(element => ({ ...element, id: e.oldIndex }));

    const oldNotes = notes.value.flatMap(group => 
        group.filter(item => item.id === e.oldIndex)
    ).map(element => ({ ...element, id: e.newIndex }));

    notes.value[e.oldIndex] = newNotes
    notes.value[e.newIndex] = oldNotes

    emit('orderNote', notes.value)
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

const editNote = data => {
    notes.value[data.id] = data.notes
    emit('editNote', data)
}
</script>

<template>
    <VCard class="pa-10">
        <VCardText class="d-flex flex-wrap justify-space-between flex-column flex-sm-row print-row rounded" style="background-color:  #F2EFFF;">
            <div class="mt-4 mx-4">
                <div class="d-flex align-center mb-6">
                    <!-- 👉 Logo -->
                    <VNodeRenderer
                        v-if="supplier.length === 0"
                        :nodes="themeConfig.app.logoBlack"
                        class="me-3"
                    />
                    <div v-else>
                        <VImg
                            v-if="supplier.logo" 
                            width="150"
                            :src="themeConfig.settings.urlStorage + supplier.logo"
                        />
                        <VNodeRenderer
                            v-else
                            :nodes="themeConfig.app.logoBlack"
                            class="me-3"
                        />
                    </div>
                </div>
                <!-- 👉 Invoice Id -->
                <h6 class="d-flex align-center font-weight-medium justify-sm-start text-xl mb-1">
                    <span class="me-2 text-start w-35 text-h6">
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
                <div class="d-flex align-center justify-sm-start mb-1 text-right" v-if="client">
                    <span class="me-2 text-start w-35">Kund nr:</span>
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
                <div class="d-flex align-center justify-sm-start mb-1 text-right">
                    <span class="me-2 text-start w-35">
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
                <div class="d-flex align-center justify-sm-start mb-0">
                    <span class="me-2 text-start w-35">
                        Förfallodag:
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
                <div class="d-flex align-center justify-sm-start mb-0 mt-2">
                    <span class="me-2 text-start w-35">
                        Betalningsvillkor:
                    </span>

                    <span style="width: 10.5rem;">
                        <VTextField
                            v-model="invoice.days"
                            type="number"
                            label="Days"
                            :disabled="props.isCredit"
                            :min="0"
                        />
                    </span>
                </div>   
                <p class="mt-5 mb-0 text-sm" v-if="client">Efter förfallodagen debiteras ränta enligt räntelagen.</p>           
            </div>
            <div class="mt-4 ma-sm-4 text-right d-flex flex-column">
                <h1 class="mb-0 text-center faktura">
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
                        label="Reference"
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

        <VCardText class="d-flex flex-wrap justify-space-between flex-column flex-sm-row gap-y-5 gap-4 px-0">
            <div class="my-sm-4">
                <h6 class="text-h6 font-weight-medium" v-if="props.role !== 'Supplier'">
                    Suppliers
                </h6>
                <VAutocomplete
                    v-if="props.role !== 'Supplier'"
                    v-model="invoice.supplier_id"
                    :items="suppliers"
                    :item-title="item => item.full_name"
                    :item-value="item => item.id"
                    :disabled="props.isCredit"
                    placeholder="Suppliers"
                    class="mb-3"
                    style="width: 400px"
                    @update:modelValue="selectSupplier"
                    clearable
                />
                <h6 class="text-h6 font-weight-medium"> Clients </h6>
                <VAutocomplete
                    v-model="invoice.client_id"
                    :items="clients"
                    :item-title="item => item.fullname"
                    :item-value="item => item.id"
                    :disabled="props.isCredit"
                    placeholder="Clients"
                    class="mb-3"
                    style="width: 400px"
                    :rules="[requiredValidator]"
                    @update:modelValue="selectClient"
                    clearable
                />
            </div>
        </VCardText>

        <VDivider />

        <!-- 👉 Add purchased products -->
        <VCardText class="add-products-form px-0">

            <VTable class="invoice-preview-table border mt-5" style="border-radius: 8px !important">
            
        

            <tbody>
              <tr v-for="(row, rowIndex) in invoice.details" :key="'row-' + rowIndex">
                <td v-for="(column, colIndex) in row" :key="'col-' + colIndex" class="py-2" :class="notes.lenght > 0 ? 'vertical-top' : ''">
                  <span :class="column.id === 1 ? 'font-weight-bold': 'vertical-top'">{{ column.value }} </span>                
                  <span v-if="column.id === 1"> 
                    <span v-for="(value, index) in notes[rowIndex]" :key="index">
                      <span class="d-flex flex-column"> 
                        {{value}}
                      </span>
                    </span> 
                  </span>         
                </td>
              </tr>
            </tbody>
          </VTable>

            <draggable
                class="my-4"
                v-model="invoice.details" 
                tag="div" 
                item-key="index" 
                @start="onStart" 
                @end="onEnd">
                <template #item="{ element, index }">
                    <div class="draggable-item py-2 px-2">
                        <InvoiceProductEdit
                            :id="index"
                            :data="element"
                            :invoices="invoices"
                            :notes="(notes && notes[index]) ? notes[index] : []"
                            :isCreated="props.isCreated"
                            @remove-product="removeProduct"
                            @delete-product="deleteProduct"
                            @edit-product="$emit('edit')"
                            @edit-note="editNote"
                        />
                    </div>
                </template>
            </draggable>
            <div class="mt-4">
                <VBtn @click="addItem">
                    Add item
                </VBtn>
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
                            <td class="pe-16"> Tax: </td>
                            <td :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'">
                                <h6 class="text-sm">
                                    <VSelect
                                        v-model="selectedTax"
                                        :items="taxOptions"
                                        label="Tax"
                                        append-icon="tabler-percentage"
                                        @update:modelValue="handleTaxChange"
                                        style="width: 150px;"
                                        />

                                    <VTextField
                                        v-if="isCustomTax"
                                        v-model.number="invoice.tax"
                                        class="mt-2"
                                        type="number"
                                        label="Customized Tax"
                                        :min="0"
                                        :step="0.01"
                                        suffix="%"
                                        style="width: 150px;"
                                    />
                                </h6>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <VDivider class="mt-4 mb-3" />

                <table class="w-100">
                    <tbody>
                        <tr>
                            <td class="pe-16">Total: </td>
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
                        Address
                    </span>
                    <span class="text-footer" v-if="supplier.length === 0">
                        Abrahamsbergsvägen 47 <br>
                        16830 BROMMA <br>
                        Hejdå Bil AB
                    </span>
                    <span v-else class="d-flex flex-column">
                        <span class="text-footer">{{ supplier.address }}</span>
                        <span class="text-footer">{{ supplier.postal_code }}</span>
                        <span class="text-footer">{{ supplier.street }}</span>
                    </span>
                    <span class="me-2 text-h6 mt-2">
                        Bolagets säte
                    </span>
                    <span class="text-footer"> Stockholm, Sweden </span>
                    <span class="me-2 text-h6 mt-2" v-if="supplier.swish">
                        Swish
                    </span>
                    <span class="text-footer" v-if="supplier.swish"> {{ supplier.swish }} </span>
                </VCol>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6">
                        Org.nr.
                    </span>
                    <span class="text-footer" v-if="supplier.length === 0"> 559374-0268 </span>
                    <span class="text-footer" v-else> {{ supplier.organization_number }} </span>
                    <span class="me-2 text-h6 mt-2" v-if="supplier.vat || supplier.length === 0">
                        Momsreg.nr.
                    </span>
                    <span class="text-footer" v-if="supplier.length === 0"> SE559374026801 </span>
                    <span class="text-footer" v-else> {{ supplier.vat }} </span>
                </VCol>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6">
                        Webbplats
                    </span>
                    <span class="text-footer" v-if="supplier.length === 0"> www.hejdabil.se </span>
                    <span class="text-footer" v-else> {{ supplier.link }} </span>
                    <span class="me-2 text-h6 mt-2">
                        Företagets e-post
                    </span>
                    <span class="text-footer" v-if="supplier.length === 0"> info@hejdabil.se </span>
                    <span class="text-footer" v-else> {{ supplier.user.email }} </span>
                </VCol>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6" v-if="supplier.account_number || supplier.length === 0">
                        Kontonummer
                    </span>
                    <span class="text-footer" v-if="supplier.length === 0"> 9960 1821054721 </span>
                    <span class="text-footer" v-else> {{ supplier.account_number }} </span>
                    <span class="me-2 text-h6 mt-2" v-if="supplier.iban || supplier.length === 0">
                        Bankgiro
                    </span>
                    <span class="text-footer" v-if="supplier.length === 0"> 5886-4976 </span>
                    <span class="text-footer" v-else> {{ supplier.iban }} </span>
                </VCol>
            </VRow>
        </VCardText>
    </VCard>
</template>

<style scoped>
    .draggable-item:hover {
        background-color: #e9ecef;
        cursor: move;
        border-radius: 8px;
    }

    .faktura {
        font-size: 32px;
        color: #9966FF;
        border-top: 2px solid #9966FF;
        border-bottom: 2px solid #9966FF;
    }
    
    .w-70 {
        width: 70% !important;
    }

    .text-footer {
        font-size: 0.75rem !important;
    }
</style>
