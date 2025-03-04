<script setup>

import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { useAuthStores } from '@/stores/useAuth'
import { useBillingsStores } from '@/stores/useBillings'
import InvoiceEditable from '@/views/apps/invoice/InvoiceEditable.vue'
import router from '@/router'

const authStores = useAuthStores()
const billingsStores = useBillingsStores()
const ability = useAppAbility()
const emitter = inject("emitter")
const route = useRoute()

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Default Blank Data
const validate = ref()
const invoiceData = ref([])
const band = ref(true)
const total = ref(0)
const isRequestOngoing = ref(true)
const billing = ref([])
const invoice = ref([])
const invoices = ref([])
const suppliers = ref([])
const clients = ref([])
const invoice_id = ref(0)

const userData = ref(null)
const role = ref(null)
const supplier = ref([])

const seeDialogRemove = ref(false)
const selectedInvoice = ref({})

const extractDaysFromNetTermSplit = term => {
    const parts = term.split(/\s+/);
    const daysIndex = parts.findIndex(part => /days?/i.test(part));
    return daysIndex > -1 ? parseInt(parts[daysIndex - 1]) : null;
}

watchEffect(fetchData)

async function fetchData() {

    if(Number(route.params.id)) { 
        isRequestOngoing.value = true

        billing.value = await billingsStores.showBilling(Number(route.params.id))

        invoice.value.id = billing.value.invoice_id
        invoice.value.reference = billing.value.reference
        invoice.value.invoice_date = billing.value.invoice_date
        invoice.value.due_date = billing.value.due_date
        invoice.value.days = extractDaysFromNetTermSplit(billing.value.payment_terms)
        invoice.value.supplier_id = billing.value.supplier_id ?? null
        invoice.value.client_id = billing.value.client_id
        invoice.value.subtotal = billing.value.subtotal 
        invoice.value.total = billing.value.total
        invoice.value.tax = billing.value.tax
        
        invoice.value.details = JSON.parse(billing.value.detail).map((element) => {
            const detailObject = {};
            element.forEach((item) => {
                detailObject[item.id] = item.value;
            });
            return detailObject;
        });


        let response = await billingsStores.all()
        
        console.log(' clients.value',  clients.value)
        clients.value = response.data.data.clients
        suppliers.value = response.data.data.suppliers
        invoices.value = response.data.data.invoices
        invoice_id.value = response.data.data.invoice_id

        userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
        role.value = userData.value.roles[0].name

        if(role.value === 'Supplier') {
            const { user_data, userAbilities } = await authStores.me(userData.value)

            localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

            ability.update(userAbilities)

            localStorage.setItem('user_data', JSON.stringify(user_data))

            supplier.value = user_data.supplier
        }

        JSON.parse(billing.value.detail).forEach(details => {
            var item = {}

            details.forEach(detail => {
                invoices.value.forEach(element => {
                    if(detail.id === element.id) {
                        item[parseInt(element.id)] = 
                            element.type_id === 2 ? 
                            parseInt(detail.value) : 
                            detail.value

                            if(element.id === 4)
                                total.value += Number(detail.value)
                    }
                });
            });

            invoiceData.value?.push(item)
            
        });

        isRequestOngoing.value = false
    }
}

const data = (data) => {
  isRequestOngoing.value = true

  invoice.value = data
  invoiceData.value = data.details

  setTimeout(() => {
    isRequestOngoing.value = false
  }, 500)
  
}

const addProduct = value => {
  invoiceData.value?.push(value)
}

const removeProduct = id => {
  seeDialogRemove.value = true
  selectedInvoice.value = { ...invoiceData.value[id] }
}

const deleteProduct = id => {
  invoiceData.value?.splice(id, 1)

  total.value = 0
  invoiceData.value.forEach(element => {
      total.value += Number(element.total)
  });
}

const editProduct = () => {
  total.value = 0
  invoiceData.value.forEach(element => {

    let result = (Number(element[2]) * parseFloat(element[3])).toFixed(2); 
    total.value += parseFloat(result);
    element[4] = result; 
  });
}

const onSubmit = () => {

    validate.value?.validate().then(async ({ valid: isValid }) => {

    if (isValid) {
        
        let formData = new FormData()

        formData.append('id', Number(route.params.id))
        formData.append('_method', 'PUT')
        formData.append('client_id', invoice.value.client_id)
        formData.append('due_date', invoice.value.due_date)
        formData.append('invoice_id', invoice.value.id)
        formData.append('invoice_date', invoice.value.invoice_date)
        formData.append('subtotal', invoice.value.subtotal)
        formData.append('supplier_id', invoice.value.supplier_id)
        formData.append('tax', invoice.value.tax)
        formData.append('total', invoice.value.total)
        formData.append('reference', invoice.value.reference)
        formData.append('payment_terms', invoice.value.days)

        invoice.value.details.forEach((element) => {
            formData.append(`details[]`, JSON.stringify(element));
        });

        let data = {
                data: formData, 
                id: Number(route.params.id)
        }

        isRequestOngoing.value = true

        billingsStores.updateBilling(data)
            .then((res) => {
                let data = {
                    message: 'Updated Invoice!',
                    error: false
                }
                
                isRequestOngoing.value = false
                
                router.push({ name : 'dashboard-admin-billings'})
                emitter.emit('toast', data)
            })
            .catch((err) => {
                advisor.value.show = true
                advisor.value.type = 'error'
                advisor.value.message = Object.values(err.message).flat().join('<br>')

                setTimeout(() => { 
                    advisor.value.show = false
                    advisor.value.type = ''
                    advisor.value.message = ''
                }, 3000)
            
                isRequestOngoing.value = false
            })
        }
    })
}

</script>

<template>
  <VForm
    ref="validate"
    @submit.prevent="onSubmit"
    >
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
    <VRow v-if="advisor.show">
      <VCol cols="12">
        <VAlert
          v-if="advisor.show"
          :type="advisor.type"
          class="mb-6">       
          {{ advisor.message }}
        </VAlert>
      </VCol>
    </VRow>
    <VRow v-if="band">
      <!-- 👉 InvoiceEditable -->
      <VCol
        cols="12"
        md="10"
      >
        <InvoiceEditable
            v-if="clients.length > 0"
            :data="invoiceData"
            :clients="clients"
            :suppliers="suppliers"
            :invoices="invoices"
            :invoice_id="invoice_id"
            :userData="userData"
            :role="role"
            :supplier="supplier"
            :total="total"
            :billing="billing"
            @push="addProduct"
            @remove="removeProduct"
            @delete="deleteProduct"
            @edit="editProduct"
            @data="data"
        />
        
      </VCol>

      <!-- 👉 Right Column: Invoice Action -->
      <VCol
        cols="12"
        md="2"
      >
        <VCard class="mb-8">
          <VCardText>
            <!-- 👉 Send Invoice -->
            <VBtn
              block
              prepend-icon="mdi-content-save"
              class="mb-2"
              type="submit"
            >
                Save
            </VBtn>

            <!-- 👉 Preview -->
            <VBtn
              block
              color="default"
              variant="tonal"
              class="mb-2"
              :to="{ name: 'dashboard-admin-billings' }"
            >
              Back
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>

    </VRow>
  </VForm>
</template>

<route lang="yaml">
  meta:
    action: edit
    subject: billing
</route>