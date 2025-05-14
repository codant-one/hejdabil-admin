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

watchEffect(fetchData)

async function fetchData() {

    isRequestOngoing.value = true

    let response = await billingsStores.all()
    
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

    var item = {}
    invoices.value.forEach(element => {
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

    invoiceData.value?.push(item)

    isRequestOngoing.value = false
}

const data = (data) => {
  invoice.value = data
  invoiceData.value = data.details
}

const addProduct = value => {
  invoiceData.value?.push(value)
}

const removeProduct = id => {
  seeDialogRemove.value = true
  selectedInvoice.value = { ...invoiceData.value[id] }
}

const deleteProduct = id => {
  if(id > 0) {
    invoiceData.value?.splice(id, 1)

    total.value = 0
    invoiceData.value.forEach(element => {
      let result = (Number(element[2]) * parseFloat(element[3])).toFixed(2); 
      total.value += parseFloat(result);
      element[4] = result; 
    });
  }
}

const editProduct = () => {
  total.value = 0

  invoiceData.value.forEach(element => {
    if(element?.note === undefined) {
      let result = (Number(element[2]) * parseFloat(element[3])).toFixed(2); 
      total.value += parseFloat(result);
      element[4] = result; 
    }
  });
}

const onSubmit = () => {

    validate.value?.validate().then(async ({ valid: isValid }) => {

    if (isValid) {
        
      let formData = new FormData()

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

      invoice.value.details.forEach((element, index) => {
        formData.append(`details[]`, JSON.stringify(element));
      });

      isRequestOngoing.value = true

      billingsStores.addBilling(formData)
          .then((res) => {
              let data = {
                  message: 'Fakturan skapades framgångsrikt',
                  error: false
              }
              
              isRequestOngoing.value = false
              
              router.push({ name : 'dashboard-admin-billings-id', params: { id: res.data.billing.id } })
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
      width="auto"
      persistent>
      <VProgressCircular
        indeterminate
        color="primary"
        class="mb-0"/>
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
            :isCreated="true"
            :isCredit="false"
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
              prepend-icon="tabler-send"
              class="mb-2"
              type="submit"
            >
              Skapa faktura
            </VBtn>

            <!-- 👉 Preview -->
            <VBtn
              block
              color="default"
              variant="tonal"
              class="mb-2"
              :to="{ name: 'dashboard-admin-billings' }"
            >
              Tillbaka
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>

    </VRow>
  </VForm>
</template>

<route lang="yaml">
  meta:
    action: create
    subject: billing
</route>