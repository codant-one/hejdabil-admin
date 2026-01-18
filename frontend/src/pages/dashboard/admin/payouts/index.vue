<script setup>

import { useDisplay } from "vuetify";
import { usePayoutsStores } from '@/stores/usePayouts'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { avatarText } from '@/@core/utils/formatters'
import { formatDate } from '@/@core/utils/formatters'
import { useAuthStores } from '@/stores/useAuth'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { formatNumber } from '@/@core/utils/formatters'
import AddNewPayoutDialog from './AddNewPayoutDialog.vue'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";

const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");
const sectionEl = ref(null);
const hasLoaded = ref(false);

const authStores = useAuthStores()
const payoutsStores = usePayoutsStores()
const ability = useAppAbility()
const emitter = inject("emitter")

const payouts = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalPayouts = ref(0)
const isRequestOngoing = ref(true)
const isAddNewPayoutDrawerVisible = ref(false)
const isPayoutDetailDialogVisible = ref(false)
const isPayoutDetailMobileDialogVisible = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const isConfirmCancelDialogVisible = ref(false)
const selectedPayout = ref({})
const skapatsDialog = ref(false);
const inteSkapatsDialog = ref(false);
const err = ref(null);
const payoutReceiptRef = ref(null);
const payoutReceiptMobileRef = ref(null);
const showShareOptions = ref(false);

const suppliers = ref([]);
const supplier_id = ref(null);
const state_id = ref(null);
const filtreraMobile = ref(false);
const isFilterDialogVisible = ref(false);

const selectedPayoutForAction = ref({});
const isMobileActionDialogVisible = ref(false);
const userData = ref(null)
const role = ref(null)
const payer_alias = ref(null)
const newlyCreatedPayout = ref(null)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = payouts.value.length 
    ? (currentPage.value - 1) * rowPerPage.value + 1 
    : 0
  const 
  lastIndex = payouts.value.length + (currentPage.value - 1) * rowPerPage.value

  return `${totalPayouts.value} resultat`;
 //return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalPayouts.value } register`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewPayoutDrawerVisible.value)
        selectedPayout.value = {}
})

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {

  if(cleanFilters === true) {
    searchQuery.value = ''
    rowPerPage.value = 10
    currentPage.value = 1
    supplier_id.value = null;
    state_id.value = null;
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    user_id: supplier_id.value,
    state_id: payoutsStores.getStateId ?? state_id.value,
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true
  isFilterDialogVisible.value = false;

  await payoutsStores.fetchPayouts(data)

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value.roles[0].name

  const { user_data, userAbilities } = await authStores.me(userData.value)

  localStorage.setItem('userAbilities', JSON.stringify(userAbilities))

  ability.update(userAbilities)

  localStorage.setItem('user_data', JSON.stringify(user_data))

  if(role.value === 'Supplier') {
    payer_alias.value = user_data.supplier.payout_number
  }

  // Ensure suppliers are loaded once we know the user's role
  await payoutsStores.info();
  if (role.value === 'SuperAdmin' || role.value === 'Administrator') {
    suppliers.value = payoutsStores.suppliers;
  }

  payouts.value = payoutsStores.getPayouts
  totalPages.value = payoutsStores.last_page
  totalPayouts.value = payoutsStores.payoutsTotalCount

  hasLoaded.value = true;
  isRequestOngoing.value = false
}

watchEffect(registerEvents)

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const showDeleteDialog = payoutData => {
  isConfirmDeleteDialogVisible.value = true
  selectedPayout.value = { ...payoutData }
}

const showCancelDialog = payoutData => {
  isConfirmCancelDialogVisible.value = true
  selectedPayout.value = { ...payoutData }
}

const seePayout = (payoutData, isMobile = false) => {
  selectedPayout.value = { ...payoutData }
  showShareOptions.value = false
  if (isMobile) {
    isPayoutDetailMobileDialogVisible.value = true
  } else {
    isPayoutDetailDialogVisible.value = true
  }
}

const closePayoutDetailDialog = () => {
  isPayoutDetailDialogVisible.value = false
  isPayoutDetailMobileDialogVisible.value = false
  showShareOptions.value = false
  // Retrasar el reset para evitar errores mientras el diálogo se cierra
  setTimeout(() => {
    selectedPayout.value = {}
  }, 300)
}

const editPayout = payoutData => {
  selectedPayout.value = { ...payoutData }
  isAddNewPayoutDrawerVisible.value = true
}

const cancelPayout = async () => {
  isConfirmCancelDialogVisible.value = false
  let res = await payoutsStores.cancelPayout(selectedPayout.value.id)
  selectedPayout.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Betalning avbruten!' : res.data.message,
    show: true
  }

  await fetchData()

  setTimeout(() => {
    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000)

  return true
}

const removePayout = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await payoutsStores.deletePayout(selectedPayout.value.id)
  selectedPayout.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Betalning raderad!' : res.data.message,
    show: true
  }

  await fetchData()

  setTimeout(() => {
    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000)

  return true
}

const showError = () => {
  inteSkapatsDialog.value = false;

  advisor.value.show = true;
  advisor.value.type = "error";
  
  if (err.value && !err.value.success) {
    advisor.value.message = err.value.message;
  } else {
    advisor.value.message = "Ett serverfel uppstod. Försök igen.";
  }

  setTimeout(() => {
    advisor.value.show = false;
    advisor.value.type = "";
    advisor.value.message = "";
  }, 3000);

};

const submitForm = async (payoutData) => {
  isRequestOngoing.value = true

  // Check if we're editing (selectedPayout has an id)
  if (selectedPayout.value && selectedPayout.value.id) {
    submitUpdate(payoutData.data, selectedPayout.value.id)
  } else {
    submitCreate(payoutData.data)
  }
}

const submitUpdate = (payoutData, payoutId) => {
  payoutData.payer_alias = payer_alias.value

  payoutsStores.updatePayout(payoutId, payoutData)
    .then((res) => {
        if (res.data.success) {
            skapatsDialog.value = true
            newlyCreatedPayout.value = res.data.data.payout
            fetchData()
        }

        isRequestOngoing.value = false
    })
    .catch((error) => {
      err.value = error
      inteSkapatsDialog.value = true
      isRequestOngoing.value = false

      fetchData()

      setTimeout(() => {
          advisor.value = {
              type: '',
              message: '',
              show: false
          }
      }, 3000)
    })
}

const submitCreate = payoutData => {
  payoutData.payer_alias = payer_alias.value

  payoutsStores.addPayout(payoutData)
    .then((res) => {
        if (res.data.success) {
            skapatsDialog.value = true;
            newlyCreatedPayout.value = res.data.data.payout
            fetchData()
        }

        isRequestOngoing.value = false
    })
    .catch((error) => {
      err.value = error;
      inteSkapatsDialog.value = true;
      isRequestOngoing.value = false

      fetchData()

      setTimeout(() => {
          advisor.value = {
              type: '',
              message: '',
              show: false
          }
      }, 3000)
    })
}

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1 }

  await payoutsStores.fetchPayouts(data)

  let dataArray = [];
      
  payoutsStores.getPayouts.forEach(element => {

    let data = {
      ID: element.id,
      USER: element.user.name + ' ' + (element.user.last_name ?? ''),
      AMOUNT: element.amount,
      PAYEE_ALIAS: element.payee_alias,
      STATE: element.state.name
    }
          
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "payouts", "csv");

  isRequestOngoing.value = false

}

const openPayoutDialog = () => {  
  if (!payer_alias.value) {
    advisor.value = {
      type: 'error',
      message: 'Det går inte att skapa betalning: konfigurerat betalningsnummer saknas',
      show: true
    }
    setTimeout(() => {
      advisor.value = { type: '', message: '', show: false }
    }, 3000)
    return
  }
  
  isAddNewPayoutDrawerVisible.value = true
}

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + '...';
  }
  return text;
};

const formatDateTime = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  return `${year}-${month}-${day} ${hours}:${minutes}`;
};

const updateStateId = (newStateId) => {
  // Si ya está seleccionado, desmarcarlo (poner null)
  if (state_id.value === newStateId) {
    newStateId = null;
  }

  payoutsStores.setStateId(newStateId);
  state_id.value = newStateId;
  filtreraMobile.value = false;
};

const resolveStatus = state_id => {
  if (state_id === 1)
    return { class: 'pending' }
  if (state_id === 2)
    return { class: 'info' }   
  if (state_id === 3)
    return { class: 'error' }
  if (state_id === 4)
    return { class: 'success' }
  if (state_id === 5)
    return { class: 'error' }
  if (state_id === 6)
    return { class: 'error' }   
}

const goToPayouts = () => {

  skapatsDialog.value = false;

  advisor.value = {
    type: 'success',
    message: 'Betalning genomförd!',
    show: true
  }

  setTimeout(() => {
    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000)               

};

const viewReceipt = async () => {
  skapatsDialog.value = false;
  
  // Refresh data to ensure the new payout is in the list with updated state
  await fetchData();

  if (newlyCreatedPayout.value) {
    // Find the updated payout in the newly loaded list
    const updatedPayout = payouts.value.find(p => p.id === newlyCreatedPayout.value.id);
    
    if (updatedPayout) {
      selectedPayout.value = updatedPayout;
    } else {
      // If not found in the current list (due to pagination), use the original value
      selectedPayout.value = newlyCreatedPayout.value;
    }

    isPayoutDetailDialogVisible.value = windowWidth.value >= 1024 ? true : false
    isPayoutDetailMobileDialogVisible.value = windowWidth.value >= 1024 ? false : true
  }
  
  advisor.value = {
    type: 'success',
    message: 'Betalning genomförd!',
    show: true
  }

  setTimeout(() => {
    advisor.value = {
      type: '',
      message: '',
      show: false
    }
  }, 3000);
};

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}

onMounted(async () => {
  resizeSectionToRemainingViewport();
  window.addEventListener("resize", resizeSectionToRemainingViewport);

  state_id.value = payoutsStores.getStateId ?? state_id.value;
  updateStateId(state_id.value);

  await payoutsStores.info();

  if (role.value === "SuperAdmin" || role.value === "Administrator") {
    suppliers.value = payoutsStores.suppliers;
  }

});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
});

const shareReceipt = (payout) => {
  selectedPayout.value = { ...payout }
  showShareOptions.value = true
  // Open the appropriate detail dialog
  if (windowWidth.value >= 1024) {
    isPayoutDetailDialogVisible.value = true
  } else {
    isPayoutDetailMobileDialogVisible.value = true
  }
}

const captureAndShare = async (method) => {
  try {
    // Check if image is already saved
    showShareOptions.value = true
    if (selectedPayout.value.image_url && method !== 'regenerate') {
      // If image already exists, share directly
      if (method === 'whatsapp') {
        shareToWhatsApp()
      } else if (method === 'email') {
        shareToEmail()
      } else if (method === 'download') {
        downloadImage()
      }
      return
    }

    // Show generation message
    isRequestOngoing.value = true
    advisor.value = {
      type: 'info',
      message: 'Skapa betalningskvitto',
      show: true
    }

    // Wait a moment for the DOM to be ready
    await new Promise(resolve => setTimeout(resolve, 200))
    
    // Dynamically import html2canvas
    const html2canvas = (await import('html2canvas')).default
    
    // Determine which container to capture
    let element = null
    
    if (isPayoutDetailDialogVisible.value && payoutReceiptRef.value) {
      element = payoutReceiptRef.value.$el || payoutReceiptRef.value
    } else if (isPayoutDetailMobileDialogVisible.value && payoutReceiptMobileRef.value) {
      element = payoutReceiptMobileRef.value.$el || payoutReceiptMobileRef.value
    }
    
    if (!element || !document.body.contains(element)) {
      element = document.querySelector('.payout-receipt-card')
    }
    
    if (!element || !document.body.contains(element)) {
      console.error('Element to capture not found')
      advisor.value = {
        type: 'error',
        message: 'Fel vid bildtagning',
        show: true
      }
      setTimeout(() => {
        advisor.value = { 
          type: '', 
          message: '', 
          show: false 
        }
        isRequestOngoing.value = false
      }, 3000)
      return
    }

    // Capture the element as an image
    const canvas = await html2canvas(element, {
      backgroundColor: '#ffffff',
      scale: 2,
      logging: false,
      useCORS: true,
      allowTaint: true,
      windowWidth: element.scrollWidth,
      windowHeight: element.scrollHeight
    })

    // Convert canvas to blob
    canvas.toBlob(async (blob) => {
      try {
        // Send to backend to save
        const formData = new FormData()
        formData.append('image', blob, `${selectedPayout.value.reference}.png`)
        formData.append('payout_id', selectedPayout.value.id)

        const response = await payoutsStores.saveReceiptImage(selectedPayout.value.id, formData)
        
        if (response.data.success) {
          // Update the payout with the new image
          selectedPayout.value = response.data.data.payout
          
          advisor.value = {
            type: 'success',
            message: 'Kvitto genererat korrekt',
            show: true
          }
          
          setTimeout(() => {
            advisor.value = { 
              type: '', 
              message: '', 
              show: false 
            }
            isRequestOngoing.value = false
            
            // Now share according to the method
            if (method === 'whatsapp') {
              shareToWhatsApp()
            } else if (method === 'email') {
              shareToEmail()
            } else if (method === 'download') {
              downloadImage()
            }
          }, 3000)
        } else {
          throw new Error('Could not save the image on server')
        }
      } catch (error) {
        console.error('Error saving the image:', error)
        advisor.value = {
          type: 'error',
          message: 'Fel vid sparande av kvittot',
          show: true
        }
        setTimeout(() => {
          advisor.value = { 
            type: '', 
            message: '', 
            show: false 
          }
          isRequestOngoing.value = false
        }, 3000)
      }
    }, 'image/png')
  } catch (error) {
    console.error('Error capturing the image:', error)
    advisor.value = {
      type: 'error',
      message: 'Fel vid generering av kvittot',
      show: true
    }
    setTimeout(() => {
      advisor.value = { type: '', message: '', show: false }
    }, 3000)
  }
}

const shareToWhatsApp = async () => {
  const imageUrl = selectedPayout.value.image_url
  
  // Detect if device is mobile
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
  
  // Try Web Share API first (works on mobile)
  if (isMobile && navigator.share) {
    try {
      // Fetch the image from URL and convert to blob
      const response = await fetch(imageUrl)
      const blob = await response.blob()
      
      // Create file from blob
      const file = new File(
        [blob], 
        `swish-kvitto-${selectedPayout.value.reference}.png`, 
        { type: 'image/png' }
      )
      
      // Prepare share data with image file
      const shareData = {
        title: 'Swish-betalningskvitto',
        text: `Swish-betalningskvitto\nReferens: ${selectedPayout.value.reference}\nMeddelande: ${selectedPayout.value.message}\nBelopp: ${formatNumber(selectedPayout.value.amount)} kr\nDatum: ${formatDateTime(selectedPayout.value.created_at)}`,
        files: [file]
      }
      
      // Try to share with files
      if (navigator.canShare && navigator.canShare(shareData)) {
        await navigator.share(shareData)
        
        advisor.value = {
          type: 'success',
          message: 'Kvitto delat!',
          show: true
        }
        
        setTimeout(() => {
          advisor.value = { type: '', message: '', show: false }
        }, 3000)
        return
      }
    } catch (error) {
      // If user cancelled or error, continue to fallback
      if (error.name === 'AbortError') {
        console.log('User cancelled share')
        return
      }
      console.log('Web Share API failed, using fallback:', error)
    }
  }
  
  // Fallback: Open WhatsApp directly with message
  const message = encodeURIComponent(
    `Swish-betalningskvitto\n` +
    `Referens: ${selectedPayout.value.reference}\n` +
    `Meddelande: ${selectedPayout.value.message}\n` +
    `Belopp: ${formatNumber(selectedPayout.value.amount)} kr\n` +
    `Datum: ${formatDateTime(selectedPayout.value.created_at)}\n\n` +
    `${imageUrl}`
  )
  
  window.open(`https://wa.me/?text=${message}`, '_blank')
  
  advisor.value = {
    type: 'success',
    message: 'Öppnar WhatsApp...',
    show: true
  }

  setTimeout(() => {
    advisor.value = { 
      type: '', 
      message: '', 
      show: false 
    }
  }, 3000)
}

const shareToEmail = () => {
  const imageUrl = selectedPayout.value.image_url
  const subject = encodeURIComponent('Swish-betalningskvitto')
  const body = encodeURIComponent(
    `Swish-betalningskvitto\n` +
    `Referens: ${selectedPayout.value.reference}\n` +
    `Meddelande: ${selectedPayout.value.message}\n` +
    `Belopp: ${formatNumber(selectedPayout.value.amount)} kr\n` +
    `Datum: ${formatDateTime(selectedPayout.value.created_at)}\n\n` +
    `Se kvitto: ${imageUrl}`
  )
  
  // Öppna e-postklient
  window.location.href = `mailto:?subject=${subject}&body=${body}`
}

const downloadImage = () => {
  const imageUrl = selectedPayout.value.image_url
  const link = document.createElement('a')
  link.href = imageUrl
  link.download = `swish-kvitto-${selectedPayout.value.reference}.png`
  link.target = '_blank'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  
  advisor.value = {
    type: 'success',
    message: 'Laddar ner kvitto...',
    show: true
  }

  setTimeout(() => {
    advisor.value = { 
      type: '', 
      message: '',
      show: false  
    }
  }, 3000)
}
</script>

<template>
  <section class="page-section" ref="sectionEl">
    <LoadingOverlay :is-loading="isRequestOngoing" />
    <VSnackbar
      v-model="advisor.show"
      transition="scroll-y-reverse-transition"
      :location="snackbarLocation"
      :color="advisor.type"
      class="snackbar-alert snackbar-dashboard"
    >
      {{ advisor.message }}
    </VSnackbar>

    <VCard class="card-fill">
      <VCardTitle
        class="d-flex gap-6 justify-space-between"
        :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row',
          $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'
        ]"
      >
        <div class="align-center font-blauer">
          <h2>Swish <span v-if="hasLoaded">({{ totalPayouts }})</span></h2>
        </div>

        <div class="d-flex gap-4">
          <VBtn 
            class="btn-light w-auto" 
            block
            @click="downloadCSV">
            <VIcon icon="custom-export" size="24" />
            Exportera
          </VBtn>
          <VBtn
            v-if="$can('create', 'payouts') && role === 'Supplier'"
            class="btn-gradient"
            block
            @click="openPayoutDialog"
          >
            <VIcon icon="custom-plus" size="24" />
            Ny betalning
          </VBtn>
        </div>
      </VCardTitle>

      <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText
        class="d-flex align-center justify-space-between"
        :class="$vuetify.display.mdAndDown ? 'pa-6' : 'pa-4 gap-2'"
      >
        <!-- 👉 Search  -->
        <div class="search">
          <VTextField v-model="searchQuery" placeholder="Sök" clearable />
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

        <div :class="windowWidth < 1024 ? 'd-none' : 'd-flex gap-2'">
          <AppAutocomplete
            v-if="role !== 'Supplier' && hasLoaded"
            prepend-icon="custom-profile"
            v-model="supplier_id"
            placeholder="Leverantörer"
            :items="suppliers"
            :item-title="(item) => item.full_name"
            :item-value="(item) => item.user_id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user selector-truncate"
          />
        </div>

        <VBtn
          v-if="role !== 'Supplier' && hasLoaded"
          class="btn-white-2 px-3"
          @click="isFilterDialogVisible = true"
          :class="windowWidth > 1023 ? 'd-none' : 'd-flex'"
        >
          <VIcon icon="custom-profile" size="24" />
        </VBtn>

        <VBtn
          class="btn-white-2 px-3"
          @click="filtreraMobile = true"
          v-if="$vuetify.display.mdAndDown"
        >
          <VIcon icon="custom-filter" size="24" />
          <span class="d-none d-md-block">Filtrera efter</span>
        </VBtn>
        
        <VMenu v-if="!$vuetify.display.mdAndDown">
          <template #activator="{ props }">
            <VBtn class="btn-white-2 px-2" v-bind="props">
              <VIcon icon="custom-filter" size="24" />
              <span class="d-none d-md-block">Filtrera efter</span>
            </VBtn>
          </template>
          <VList>
            <VListItem @click="updateStateId(4)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 4"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Slutförd</VListItemTitle>
            </VListItem>

            <VListItem @click="updateStateId(1)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 1"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Väntande</VListItemTitle>
            </VListItem>

            <VListItem @click="updateStateId(3)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 3"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Avbruten</VListItemTitle>
            </VListItem>

            <VListItem @click="updateStateId(5)">
              <template #prepend>
                <VListItemAction>
                  <VCheckbox
                    :model-value="state_id === 5"
                    class="ml-3"
                    true-icon="custom-checked-checkbox"
                    false-icon="custom-unchecked-checkbox"
                /></VListItemAction>
              </template>
              <VListItemTitle>Misslyckad</VListItemTitle>
            </VListItem>
          </VList>
        </VMenu>

        <div
          v-if="!$vuetify.display.mdAndDown"
          class="d-flex align-center visa-select"
        >
          <span class="text-no-wrap pr-4">Visa</span>
          <VSelect
            v-model="rowPerPage"
            density="compact"
            variant="outlined"
            :items="[10, 20, 30, 50]"/>
        </div>
      </VCardText>

      <VTable
        v-if="!$vuetify.display.mdAndDown"
        v-show="payouts.length"
        class="pt-2 px-4 pb-6 text-no-wrap"
        style="border-radius: 0 !important"
      >
        <!-- 👉 table head -->
        <thead>
          <tr>
            <th scope="col" class="text-center"> Referens </th>
            <th scope="col" class="text-center"> Datum </th>            
            <th scope="col" class="text-center"> Personnummer </th>
            <th scope="col" class="text-center"> Mobilnummer </th>
            <th scope="col" class="text-center"> Belopp </th>
            <th scope="col" v-if="role !== 'Supplier' && role !== 'User'"> Skapad av </th>
            <th scope="col" class="text-center"> Status </th>
            <th scope="col" v-if="$can('edit', 'payouts') || $can('delete', 'payouts')"></th>
          </tr>
        </thead>
        <!-- 👉 table body -->
        <tbody>
          <tr 
            v-for="payout in payouts"
            :key="payout.id"
            style="height: 3rem;">
            <td class="text-center"> {{ payout.message ?? ''}} </td>
            <td class="text-center"> {{ formatDateTime(payout.created_at) }}</td>
            <td class="text-center"> {{ payout.payee_ssn ?? ''}} </td>
            <td class="text-center"> +{{ payout.payee_alias ?? ''}} </td>
            <td class="text-center"> {{ formatNumber(payout.amount ?? 0) }} kr</td>
            <td style="width: 1%; white-space: nowrap" v-if="role !== 'Supplier' && role !== 'User'">
              <div class="d-flex align-center gap-x-1">
                <VAvatar
                  :variant="payout.user.avatar ? 'outlined' : 'tonal'"
                  size="38"
                >
                  <VImg
                    v-if="payout.user.avatar"
                    style="border-radius: 50%"
                    :src="themeConfig.settings.urlStorage + payout.user.avatar"
                  />
                  <span v-else>{{ avatarText(payout.user.name) }}</span>
                </VAvatar>
                <div class="d-flex flex-column">
                  <span class="font-weight-medium">
                    {{ payout.user.name }} {{ payout.user.last_name ?? "" }}
                  </span>
                  <span class="text-sm text-disabled">
                    <VTooltip location="bottom" v-if="payout.user.email && payout.user.email.length > 20">
                      <template #activator="{ props }">
                        <span v-bind="props">
                          {{ truncateText(payout.user.email, 20) }}
                        </span>
                      </template>
                      <span>{{ payout.user.email }}</span>
                    </VTooltip>
                    <span class="text-sm text-disabled"v-else>{{ payout.user.email }}</span>
                  </span>
                </div>
              </div>
            </td>
            <!-- 😵 Statuses -->
            <td class="text-center text-wrap d-flex justify-center align-center">
              <div
                class="status-chip"
                :class="`status-chip-${resolveStatus(payout.state.id)?.class}`"
              >
                {{ payout.state.name }}
              </div>
            </td>
            <!-- 👉 Actions -->
            <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'payouts') || $can('delete', 'payouts')">      
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem
                    v-if="$can('view','payouts')"
                    @click="seePayout(payout, false)">
                    <template #prepend>
                      <VIcon icon="custom-eye" size="24" />
                    </template>
                    <VListItemTitle>Visa detaljer</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('view','payouts') && payout.state.id === 4"
                    @click="shareReceipt(payout)">
                    <template #prepend>
                      <VIcon icon="custom-forward" size="24" />
                    </template>
                    <VListItemTitle>Skicka betalningsbevis</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('view','payouts') && payout.state.id === 1"
                    @click="editPayout(payout)">
                    <template #prepend>
                      <VIcon icon="custom-check-mark" size="24" />
                    </template>
                    <VListItemTitle>Bekräfta betalning</VListItemTitle>
                  </VListItem>
                  <VListItem
                    v-if="$can('view','payouts') && payout.state.id === 1"
                    @click="showCancelDialog(payout)">
                    <template #prepend>
                      <VIcon icon="custom-unavailable" size="24" />
                    </template>
                    <VListItemTitle>Avbryt</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    v-if="$can('delete','payouts')"
                    @click="showDeleteDialog(payout)"
                    class="d-none">
                    <template #prepend>
                      <VIcon icon="custom-waste" size="24" />
                    </template>
                    <VListItemTitle>Ta bort</VListItemTitle>
                  </VListItem>
                </VList>
              </VMenu>
            </td>
          </tr>
        </tbody>
      </VTable>
    
      <div
        v-if="!isRequestOngoing && hasLoaded && !payouts.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-f-payment"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Inga Swish-transaktioner än</div>
          <div class="empty-state-text">
            Här samlas historiken för alla betalningar som genomförs via Swish. 
            Kom igång med snabba och säkra transaktioner direkt i plattformen.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'payouts') && role === 'Supplier'"
          @click="openPayoutDialog"
        >
          Ny betalning
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>

      <VExpansionPanels
        class="expansion-panels pb-6 px-6"
        v-if="payouts.length && $vuetify.display.mdAndDown"
      >
        <VExpansionPanel v-for="payout in payouts" :key="payout.id">
          <VExpansionPanelTitle
            collapse-icon="custom-chevron-right"
            expand-icon="custom-chevron-down"
          >            
            <div class="order-title-box">
              <span class="title-panel">
                <strong>{{ payout.message ?? '' }}</strong>
              </span>
              <div class="gap-2 title-organization">
                <span>
                  {{ payout.created_at ? formatDate(payout.created_at, { month: '2-digit', day: '2-digit', year: 'numeric' }) : ''}}
                </span>
                <VIcon size="16" icon="custom-clock" />
                <span>
                  {{ payout.created_at ? formatDate(payout.created_at, { hour: '2-digit', minute: '2-digit', hour12: false }) : ''}}
                </span>
              </div>
            </div>
            <span class="text-black w-100 text-end align-center me-4 font-weight-bold" style="font-size: 16px;">
                {{ formatNumber(payout.amount ?? 0) }} kr
            </span>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <div class="mb-6 d-flex justify-between flex-wrap gap-4">
              <div>
                <div class="expansion-panel-item-label">Personnummer:</div>
                <div class="expansion-panel-item-value">
                  {{ payout.payee_ssn ?? "" }}
                </div>
              </div>
              <div>
                <div class="expansion-panel-item-label">Mobilnummer:</div>
                <div class="expansion-panel-item-value">
                  +{{ payout.payee_alias ?? "" }}
                </div>
              </div>
            </div>
            <div class="mb-6">
              <div class="expansion-panel-item-label">Status:</div>
              <div class="expansion-panel-item-value">
                <div
                  class="status-chip"
                  :class="`status-chip-${resolveStatus(payout.state.id)?.class}`"
                >
                  {{ payout.state.name }}
                </div>
              </div>
            </div>
            <div class="mb-4 row-with-buttons">
              <VBtn
                class="btn-light"
                @click="selectedPayoutForAction = payout; isMobileActionDialogVisible = true"
              >
                Åtgärder
              </VBtn>
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>

      <VCardText
        v-if="payouts.length"
        :class="windowWidth < 1024 ? 'd-block' : 'd-flex'"
        class="align-center flex-wrap gap-4 pt-0 px-6"
      >
        <span class="text-pagination-results">
          {{ paginationData }}
        </span>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />
        
        <VPagination
          v-model="currentPage"
          size="small"
          :total-visible="5"
          :length="totalPages"
          next-icon="custom-chevron-right"
          prev-icon="custom-chevron-left"
        />
      </VCardText>
    </VCard>

    <!-- Payout Detail Dialog -->
    <VDialog
      :model-value="isPayoutDetailDialogVisible"
      persistent
      scrollable
      class="action-dialog"
      content-class="scrollable-dialog-content"
    >
      <!-- Dialog close btn -->
      <VBtn
        icon
        class="btn-white close-btn"
        @click="closePayoutDetailDialog"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard ref="payoutReceiptRef" class="payout-receipt-card">
        <VCardText class="dialog-title-box" data-html2canvas-ignore="true">
          <div class="dialog-title">
            {{ showShareOptions ? 'Dela' : 'Detaljer' }}
          </div>
        </VCardText>

        <VCardText v-if="showShareOptions" class="dialog-text px-4 d-flex gap-2">
            <VSpacer />
            <VBtn
              class="btn-light"
              style="width: 48px!important;"
              @click="captureAndShare('whatsapp')"
              data-html2canvas-ignore="true"
            >
              <VIcon icon="mdi-whatsapp" size="20" />
            </VBtn>
            <VBtn
              class="btn-light"
              style="width: 48px!important;"
              @click="captureAndShare('email')"
              data-html2canvas-ignore="true"
            >
              <VIcon icon="mdi-email-outline" size="20" />
            </VBtn>
            <VBtn
              class="btn-light"
              style="width: 48px!important;"
              @click="captureAndShare('download')"
              data-html2canvas-ignore="true"
            >
              <VIcon icon="custom-download" size="20" />
            </VBtn>
        </VCardText>
        <VCardText class="dialog-text pa-4" v-if="selectedPayout.state">
          <div class="bg-alert">
            <div class="d-flex justify-between" data-html2canvas-ignore="true">
              <div
                v-if="!showShareOptions"
                class="status-chip"
                :class="`status-chip-${resolveStatus(selectedPayout.state.id)?.class}`"
              >
                {{ selectedPayout.state.name }}
              </div>

              <div v-if="selectedPayout.payout_state_id === 4" class="d-flex gap-2">
                <VBtn
                  v-if="selectedPayout.payout_state_id === 4 && !showShareOptions"
                  class="btn-light"
                  style="height: 40px !important;"
                  @click="showShareOptions = true"
                >
                  <VIcon icon="custom-forward" size="24" />
                  Dela kvitto
                </VBtn>                
              </div>
            </div>
            <VCardText class="big-icon justify-center d-flex flex-column align-center gap-3">
              <VIcon v-if="selectedPayout.payout_state_id === 4" size="96" icon="custom-f-checkmark" />
              <VIcon v-if="selectedPayout.payout_state_id === 1" size="96" icon="custom-f-info" />
              <VIcon v-if="selectedPayout.payout_state_id === 3 || selectedPayout.payout_state_id === 5" size="96" icon="custom-f-cancel" />
              <span class="text-amount">{{ formatNumber(selectedPayout.amount) }} kr</span>
              <div class="d-flex gap-2 title-organization justify-center align-center">
                  <span class="text-date-swish">
                    {{ selectedPayout.created_at ? formatDate(selectedPayout.created_at, { month: '2-digit', day: '2-digit', year: 'numeric' }) : ''}}
                  </span>
                  <VIcon size="16" icon="custom-clock" />
                  <span class="text-date-swish">
                    {{ selectedPayout.created_at ? formatDate(selectedPayout.created_at, { hour: '2-digit', minute: '2-digit', hour12: false }) : ''}}
                  </span>
              </div>
              <span class="text-reference">{{ selectedPayout.reference }}</span>
            </VCardText>
          </div>      
        </VCardText>

        <VCardText class="dialog-text">
          <span class="mb-2 d-flex justify-between text-neutral-3">
            Mobilnummer: <strong class="text-black">+{{ selectedPayout.payee_alias }}</strong>
          </span>
          <VDivider />
          <span class="mb-2 d-flex justify-between mt-2 text-neutral-3">
            Personnummer: <strong class="text-black">{{ selectedPayout.payee_ssn }}</strong>
          </span>
          <VDivider v-if="selectedPayout.message" class="mb-2"/>
          <span v-if="selectedPayout.message">
            Meddelande: <br> <strong class="text-black">{{ selectedPayout.message }}</strong>
          </span>
          <VDivider v-if="selectedPayout.error_message" class="mb-2"/>
          <span v-if="selectedPayout.error_message">
            Felinformation: <br> <strong class="text-black">{{ selectedPayout.error_message }} ({{ selectedPayout.error_code }})</strong>
          </span>
        </VCardText>

        <VCardText class="dialog-text my-4 pa-4 d-flex justify-center align-center gap-4">
          <img 
            src="@/assets/images/billogg_img.png" 
            alt="Billogg image"
          />
          <VDivider vertical />
          <img 
            src="@/assets/images/swish_img.png" 
            alt="Swish image"
          />
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Payout Detail Dialog mobile -->
    <VDialog
        v-model="isPayoutDetailMobileDialogVisible"
        fullscreen
        persistent
        :scrim="false"
        transition="dialog-bottom-transition"
        class="action-dialog dialog-fullscreen">
       <VCard ref="payoutReceiptMobileRef" class="payout-receipt-card">
        <VCardText 
          class="dialog-title-box px-4 pb-0 flex-row" 
          :style="selectedPayout.payout_state_id !== 5 ? 'height: 30px !important;' : 'height: 50px !important;'"
          data-html2canvas-ignore="true">
          <div class="dialog-title d-flex justify-between w-100">
            <VBtn
              class="btn-light"
              @click="closePayoutDetailDialog"
            >
              <VIcon icon="custom-return" size="24" />
              Gå ut
            </VBtn>
            <div v-if="selectedPayout.payout_state_id === 4" class="d-flex gap-2">
              <VBtn
                v-if="showShareOptions"
                class="btn-light"
                style="width: 48px!important;"
                @click="captureAndShare('whatsapp')"
              >
                <VIcon icon="mdi-whatsapp" size="20" />
              </VBtn>
              <VBtn
                v-if="showShareOptions"
                class="btn-light"
                style="width: 48px!important;"
                @click="captureAndShare('email')"
              >
                <VIcon icon="mdi-email-outline" size="20" />
              </VBtn>
              <VBtn
                v-if="showShareOptions"
                class="btn-light"
                style="width: 48px!important;"
                @click="captureAndShare('download')"
              >
                <VIcon icon="custom-download" size="20" />
              </VBtn>
              <VBtn
                v-if="selectedPayout.payout_state_id === 4 && !showShareOptions"
                class="btn-light"
                @click="showShareOptions = true"
              >
                <VIcon icon="custom-forward" size="24" />
                Dela kvitto
              </VBtn>
            </div>
          </div>
        </VCardText>

        <VCardText class="dialog-text pa-4" v-if="selectedPayout.state">
          <div class="bg-alert">
            <div
              v-if="!showShareOptions"
              class="status-chip"
              :class="`status-chip-${resolveStatus(selectedPayout.state.id)?.class}`"
            >
              {{ selectedPayout.state.name }}
            </div>
            <VCardText class="big-icon justify-center d-flex flex-column align-center gap-3 py-0">
              <VIcon v-if="selectedPayout.payout_state_id === 4" size="96" icon="custom-f-checkmark" />
              <VIcon v-if="selectedPayout.payout_state_id === 1" size="96" icon="custom-f-info" />
              <VIcon v-if="selectedPayout.payout_state_id === 3 || selectedPayout.payout_state_id === 5" size="96" icon="custom-f-cancel" />
              <span class="text-amount">{{ formatNumber(selectedPayout.amount) }} kr</span>
              <div class="d-flex gap-2 title-organization justify-center align-center">
                  <span class="text-date-swish">
                    {{ selectedPayout.created_at ? formatDate(selectedPayout.created_at, { month: '2-digit', day: '2-digit', year: 'numeric' }) : ''}}
                  </span>
                  <VIcon size="16" icon="custom-clock" />
                  <span class="text-date-swish">
                    {{ selectedPayout.created_at ? formatDate(selectedPayout.created_at, { hour: '2-digit', minute: '2-digit', hour12: false }) : ''}}
                  </span>
              </div>
              <span class="text-reference">{{ selectedPayout.reference }}</span>
            </VCardText>
          </div>      
        </VCardText>

        <VCardText class="dialog-text">
          <span class="mb-2 d-flex justify-between text-neutral-3">
            Mobilnummer: <strong class="text-black">+{{ selectedPayout.payee_alias }}</strong>
          </span>
          <VDivider />
          <span class="mb-2 d-flex justify-between mt-2 text-neutral-3">
            Personnummer: <strong class="text-black">{{ selectedPayout.payee_ssn }}</strong>
          </span>
          <VDivider v-if="selectedPayout.message" class="mb-2"/>
          <span v-if="selectedPayout.message">
            Meddelande: <br> <strong class="text-black">{{ selectedPayout.message }}</strong>
          </span>
          <VDivider v-if="selectedPayout.error_message" class="mb-2"/>
          <span v-if="selectedPayout.error_message">
            Felinformation: <br> <strong class="text-black">{{ selectedPayout.error_message }} ({{ selectedPayout.error_code }})</strong>
          </span>
        </VCardText>

        <VCardText class="dialog-text pa-4 d-flex justify-center align-center gap-4" style="height: 32px !important;">
          <img 
            src="@/assets/images/billogg_img.png" 
            alt="Billogg image"
          />
          <VDivider vertical class="my-auto"/>
          <img 
            src="@/assets/images/swish_img.png" 
            alt="Swish image"
          />
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Add New Payout -->
    <AddNewPayoutDialog
      v-if="payer_alias"
      v-model:isDialogOpen="isAddNewPayoutDrawerVisible"
      :payout-data="selectedPayout"
      @payout-data="submitForm"
    />

    <!-- 👉 Confirm Delete -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="action-dialog" >

      <!-- Dialog close btn -->
      <VBtn
      icon
        class="btn-white close-btn"
        @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-filled-waste" class="action-icon" />
          <div class="dialog-title">
            Ta bort betalningar
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Är du säker att du vill ta bort betalningen <strong>{{ selectedPayout.reference }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removePayout">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm Cancel -->
    <VDialog
      v-model="isConfirmCancelDialogVisible"
      persistent
      class="action-dialog" >

      <!-- Dialog close btn -->
      <VBtn
      icon
        class="btn-white close-btn"
        @click="isConfirmCancelDialogVisible = !isConfirmCancelDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-unavailable" class="action-icon" />
          <div class="dialog-title">
            Avbryt betalningar
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Är du säker på att du vill avbryta betalningen <strong>{{ selectedPayout.message }}</strong>?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmCancelDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="cancelPayout">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Mobile Action Dialog -->
    <VDialog
      v-model="isMobileActionDialogVisible"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem
            v-if="$can('view', 'payouts')"
            @click="seePayout(selectedPayoutForAction, true); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-eye" size="24" />
            </template>
            <VListItemTitle>Visa detaljer</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view','payouts') && selectedPayoutForAction.state.id === 4"
            @click="shareReceipt(selectedPayoutForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-forward" size="24" />
            </template>
            <VListItemTitle>Skicka betalningsbevis</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view','payouts') && selectedPayoutForAction.state.id === 1"
            @click="editPayout(selectedPayoutForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-check-mark" size="24" />
            </template>
            <VListItemTitle>Bekräfta betalning</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view','payouts') && selectedPayoutForAction.state.id === 1"
            @click="showCancelDialog(selectedPayoutForAction); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-unavailable" size="24" />
            </template>
            <VListItemTitle>Avbryt</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('delete', 'payouts')"
            class="d-none"
            @click="showDeleteDialog(selectedPayoutForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-waste" size="24" />
            </template>
            <VListItemTitle>Ta bort</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>

    <!-- 👉 Dialogs Section -->
    <VDialog
      v-model="skapatsDialog"
      persistent
      class="action-dialog dialog-big-icon"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="skapatsDialog = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box big-icon justify-center pb-0">
          <VIcon size="72" icon="custom-f-checkmark" />
        </VCardText>
        <VCardText class="dialog-title-box justify-center">
          <div class="dialog-title">Betalningen lyckades!</div>
        </VCardText>
        <VCardText class="dialog-text text-center">
          Din betalning via Swich har genomförts och skickats till mottagaren. Du hittar kvittot i din betalningshistorik.
        </VCardText>

        <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="viewReceipt">
            Visa kvitto
          </VBtn>
          <VBtn class="btn-gradient" @click="goToPayouts"> Klar </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <VDialog
      v-model="inteSkapatsDialog"
      persistent
      class="action-dialog dialog-big-icon"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="inteSkapatsDialog = !inteSkapatsDialog"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>
      <VCard>
        <VCardText class="dialog-title-box big-icon justify-center pb-0">
          <VIcon size="72" icon="custom-f-cancel" />
        </VCardText>
        <VCardText class="dialog-title-box justify-center">
          <div class="dialog-title">Betalningen har inte genomförts!</div>
        </VCardText>
        <VCardText class="dialog-text text-center">
          Din betalning via Swich har inte behandlats korrekt, försök igen.
        </VCardText>

        <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="showError">
            Stäng
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Filter Dialog -->
    <VDialog
      v-model="isFilterDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isFilterDialogVisible = false"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-filter" class="action-icon" />
          <div class="dialog-title">Filtrera efter</div>
        </VCardText>
        
        <VCardText class="pt-0">
          <AppAutocomplete
            v-if="role !== 'Supplier'"
            prepend-icon="custom-profile"
            v-model="supplier_id"
            placeholder="Leverantörer"
            :items="suppliers"
            :item-title="(item) => item.full_name"
            :item-value="(item) => item.user_id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user selector-truncate mb-3"
          />
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-10">
          <VBtn class="btn-light" @click="isFilterDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="isFilterDialogVisible = false">
            Stäng
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Mobile Filter Dialog -->
    <VDialog
      v-model="filtreraMobile"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem @click="updateStateId(4)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="state_id === 4"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Slutförd</VListItemTitle>
          </VListItem>

          <VListItem @click="updateStateId(1)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                 :model-value="state_id === 1"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Väntande</VListItemTitle>
          </VListItem>

          <VListItem @click="updateStateId(3)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="state_id === 3"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Avbruten</VListItemTitle>
          </VListItem>

          <VListItem @click="updateStateId(5)">
            <template #prepend>
              <VListItemAction>
                <VCheckbox
                  :model-value="state_id === 5"
                  true-icon="custom-checked-checkbox"
                  false-icon="custom-unchecked-checkbox"
              /></VListItemAction>
            </template>
            <VListItemTitle>Misslyckad</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>
  </section>
</template>
<style>
  .text-amount {
    font-weight: 600;
    font-size: 24px;
    line-height: 100%;
    letter-spacing: 0;
    color: #5D5D5D;
  }

  .text-date-swish {
    font-weight: 400;
    font-size: 12px;
    line-height: 16px;
    letter-spacing: 0;
    color: #878787;   
  }

  .text-reference {
    font-weight: 700;
    font-size: 14px;
    line-height: 16px;
    letter-spacing: 0;
    color: #878787;   
  }
</style>
<route lang="yaml">
  meta:
    action: view
    subject: payouts
</route>