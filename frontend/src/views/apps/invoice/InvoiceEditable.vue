<script setup>

import InvoiceProductEdit from "@/components/invoice/InvoiceProductEdit.vue"
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
    }
})

const emit = defineEmits([
    'push',
    'remove',
    'delete',
    'setting',
    'data',
    'edit'
])

const clients = ref(props.clients)
const client = ref(null)
const suppliers = ref(props.suppliers)
const supplier = ref(props.supplier)
const subtotal = ref(props.total)
const total = ref('0.00')

const invoice = ref({
    id: 1,
    days: 1,
    client_id: null,
    supplier_id: null,
    invoice_date: null,
    due_date:null,
    subtotal: 0,
    tax: 0,
    total: 0,
    reference: null,
    details: props.data
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

watchEffect(fetchData)

async function fetchData() {
    if(props.role === 'Supplier')
        invoice.value.id = supplier.value.billings.length + 1
    else
        invoice.value.id = props.invoice_id + 1
}

const startDateTimePickerConfig = computed(() => {

    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(now.getDate() + 1);

    const formatToISO = (date) => date.toISOString().split('T')[0];


    const config = {
        dateFormat: 'Y-m-d',
        position: 'auto right',
        disable: [
        {
            from: formatToISO(tomorrow),
            to: '2099-12-31' // Una fecha futura lejana para bloquear indefinidamente
        }
        ]
    }

    return config
})

const endDateTimePickerConfig = computed(() => {
    const now = new Date();

    const formatToISO = (date) => date.toISOString().split('T')[0];

    return {
        dateFormat: 'Y-m-d',
        position: 'auto right',
        disable: [
            {
                from: '1900-01-01', // Fecha muy antigua para bloquear todo antes de hoy
                to: formatToISO(new Date(now.setDate(now.getDate() - 1))) // Hasta ayer
            }
        ]
    };
});

const selectSupplier = async() => {

    var selected = suppliers.value.filter(element => element.id === invoice.value.supplier_id)[0]

    if(selected) {
        supplier.value = selected
        invoice.value.id = supplier.value.billings.length + 1
    } else {
        supplier.value = []
        invoice.value.id = props.invoice_id + 1
    } 
    
    emit('data', invoice.value)
}

const selectClient = async() => {

    var selected = clients.value.filter(element => element.id === invoice.value.client_id)[0]

    if(selected) {
        client.value = selected 
        invoice.value.reference = client.value.reference
    } else 
        client.value = null
}

const resolvePrice = (data) => {
    const priceMapping = {
        video_id: 'videos',
        title_optimization_id: 'optimizations',
        ia_image_id: 'images',
        redaction_id: 'redactions',
        task_id: 'tasks'
    };

    const key = Object.keys(priceMapping).find(k => data[k] !== null);

    if (key) {
        const priceKey = priceMapping[key];
        total.value += price;
        return price
    }

    return 0;
};

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
                value = 0
            break   
        }
        item[parseInt(element.id)] = value
    });

    emit('push', item)
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
                <p class="mb-0" v-if="client">
                    Client No: {{ client.id }}
                </p>
                <p class="mb-0" v-if="client">
                    Name:  {{ client.fullname }}
                </p>
                <p class="mb-0" v-if="client">
                   E-mail: {{ client.email }}
                </p>
                <p class="mb-0" v-if="client">
                    Organization number: {{ client.organization_number ?? '' }}
                </p>
                <p class="mb-0 mt-2" v-if="client">
                    <VTextField
                        v-model="invoice.reference"
                        label="Reference"
                    />
                </p>    
                <p class="mt-5 mb-0 text-sm" v-if="client">After the due date, interest is charged according to the Interest Act.</p>           
            </div>
            <div class="mt-4 ma-sm-4 text-right">
                <!-- 👉 Invoice Id -->
                <h6 class="d-flex align-center font-weight-medium justify-sm-end text-xl mb-1">
                    <span class="me-2 text-h6">
                        Invoice No:
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

                <!-- 👉 Issue Date -->
                <div class="d-flex align-center justify-sm-end mb-1 text-right">
                    <span class="me-2">
                        Invoice Date:
                    </span>

                    <span style="inline-size: 10.5rem;">
                        <AppDateTimePicker
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
                <div class="d-flex align-center justify-sm-end mb-0">
                    <span class="me-2">
                        Due date:
                    </span>

                    <span style="min-inline-size: 10.5rem;">
                        <AppDateTimePicker
                            :key="JSON.stringify(endDateTimePickerConfig)"
                            v-model="invoice.due_date"
                            density="compact"
                            placeholder="YYYY-MM-DD"
                            :rules="[requiredValidator]"
                            :config="endDateTimePickerConfig"
                            @input="inputData"
                            clearable
                        />
                    </span>
                </div>

                <!-- 👉 Days -->
                <div class="d-flex align-center justify-sm-end mb-0 mt-1">
                    <span class="me-2">
                        Payment Terms:
                    </span>

                    <span style="width: 10.5rem;">
                        <VTextField
                            v-model="invoice.days"
                            type="number"
                            label="Days"
                            :min="1"
                        />
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
                    placeholder="Clients"
                    class="mb-3"
                    style="width: 400px"
                    :rules="[requiredValidator]"
                      @update:modelValue="selectClient"
                    clearable
                />
            </div>
            <div class="mt-4 my-sm-4" style="width: 400px" v-if="client">
                <h6 class="text-h6 font-weight-medium mb-6">
                    Billing Address
                </h6>
                <span class="d-flex flex-column">
                    <span class="font-weight-bold">{{ client.address }}</span>
                    <span>{{ client.street }}</span>
                    <span>{{ client.postal_code }}</span>
                </span>
            </div>
        </VCardText>

        <VDivider />

        <!-- 👉 Add purchased products -->
        <VCardText class="add-products-form px-0">
            <div
                v-for="(product, index) in invoice.details"
                :key="index"
                class="my-4"
            >
                <InvoiceProductEdit
                    :id="index"
                    :data="product"
                    :invoices="invoices"
                    @remove-product="removeProduct"
                    @delete-product="deleteProduct"
                    @edit-product="$emit('edit')"
                />
            </div>
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
                            <td class="pe-16"> Subtotal:</td>
                            <td :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'">
                                <h6 class="text-sm">
                                    {{ formatNumber(subtotal) }} KR
                                </h6>
                            </td>
                        </tr>
                        <tr>
                            <td class="pe-16"> Tax: </td>
                            <td :class="$vuetify.locale.isRtl ? 'text-start' : 'text-end'">
                                <h6 class="text-sm">
                                    <VTextField
                                        v-model="invoice.tax"
                                        type="number"
                                        label="Tax"
                                        :min="0"
                                        :step="0.01"
                                        suffix="%"
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
                                    {{ formatNumber(total) }} KR
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
                    <span  v-if="supplier.length === 0">
                        Hejdå Bil AB
                        Abrahamsbergsvägen 47
                        16830 BROMMA
                    </span>
                    <span v-else class="d-flex flex-column">
                        <span>{{ supplier.address }}</span>
                        <span>{{ supplier.street }}</span>
                        <span>{{ supplier.postal_code }}</span>
                    </span>
                    <span class="me-2 text-h6 mt-2">
                        Registered office of the company
                    </span>
                    <span> Stockholm, Sweden </span>
                    <span class="me-2 text-h6 mt-2">
                        Swish
                    </span>
                    <span> ?? </span>
                </VCol>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6">
                        Org.nr.
                    </span>
                    <span v-if="supplier.length === 0"> 559374-0268 </span>
                    <span v-else> {{ supplier.organization_number }} </span>
                    <span class="me-2 text-h6 mt-2">
                        VAT reg. no.
                    </span>
                    <span v-if="supplier.length === 0"> SE559374026801 ?? </span>
                    <span v-else> ?? </span>
                </VCol>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6">
                        Website
                    </span>
                    <span v-if="supplier.length === 0"> www.hejdabil.se </span>
                    <span v-else> {{ supplier.link }} </span>
                    <span class="me-2 text-h6 mt-2">
                        Company e-mail
                    </span>
                    <span v-if="supplier.length === 0"> info@hejdabil.se </span>
                    <span v-else> {{ supplier.user.email }} </span>
                </VCol>
                <VCol cols="12" md="3" class="d-flex flex-column">
                    <span class="me-2 text-h6">
                        Bank account number
                    </span>
                    <span v-if="supplier.length === 0"> 9960 1821054721 </span>
                    <span v-else> {{ supplier.account_number }} </span>
                    <span class="me-2 text-h6 mt-2">
                        Bankgiro
                    </span>
                    <span v-if="supplier.length === 0"> 5886-4976 </span>
                    <span v-else> ?? </span>
                </VCol>
            </VRow>
        </VCardText>
    </VCard>
</template>

<style scoped>
  .w-70 {
    width: 70% !important;
  }
</style>
