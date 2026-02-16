<script setup>

import { useAgreementsStores } from '@/stores/useAgreements'
import { useBillingsStores } from "@/stores/useBillings";
import { formatNumber } from "@/@core/utils/formatters";
import { themeConfig } from "@themeConfig";
import router from "@/router";
import VuePdfEmbed from 'vue-pdf-embed'

const props = defineProps({
  client_id: {
    type: Number,
    required: true,
  },
});

const emit = defineEmits(["alert", "loading"]);

const agreementsStores = useAgreementsStores()
const billingsStores = useBillingsStores();

const { width: windowWidth } = useWindowSize();

const billings = ref([]);
const searchQuery = ref("");
const rowPerPage = ref(10);
const currentPage = ref(1);
const currentPageAgreements = ref(1);
const totalPages = ref(1);
const totalPagesAgreements = ref(1);
const totalBillings = ref(0);
const isConfirmStateDialogVisible = ref(false);
const isConfirmSendMailVisible = ref(false);
const isConfirmKreditera = ref(false)
const isConfirmSendMailReminder = ref(false);
const emailDefault = ref(true);
const selectedTags = ref([]);
const existingTags = ref([]);
const selectedBilling = ref({});
const isValid = ref(false);
const userData = ref(null);
const role = ref(null);
const tabBilling = ref("fakturor");

const selectedBillingForAction = ref({});
const isMobileActionDialogVisible = ref(false);

const selectedAgreementForAction = ref({});
const isMobileActionDialogVisibleAgreement = ref(false);

const agreements = ref([])
const totalAgreements = ref(0)

const advisor = ref({
  type: "",
  message: "",
  show: false,
});

const paginationData = computed(() => {
  const firstIndex = billings.value.length
    ? (currentPage.value - 1) * rowPerPage.value + 1
    : 0;
  const lastIndex =
    billings.value.length + (currentPage.value - 1) * rowPerPage.value;
  return `${totalBillings.value} resultat`;
  // return `Visar ${firstIndex} till ${lastIndex} av ${totalBillings.value} register`;
});

// 👉 Computing pagination data
const paginationData2 = computed(() => {
  const firstIndex = agreements.value.length ? (currentPageAgreements.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = agreements.value.length + (currentPageAgreements.value - 1) * rowPerPage.value

  return `${totalAgreements.value} resultat`
})

// Computed para detectar si hay agreements activos esperando interacción
const hasActiveAgreements = computed(() => {
  return agreements.value.some(agr => 
    ['sent', 'delivered', 'reviewed'].includes(agr.token?.signature_status)
  )
})

// Verificación silenciosa de cambios sin activar spinner
const checkForUpdates = async () => {
  try {
    let data = {
      search: searchQuery.value,
      orderByField: "id",
      orderBy: "desc",
      limit: rowPerPage.value,
      page: currentPageAgreements.value,
      client_id: props.client_id,
    }

    // Hacer la petición silenciosa (sin cambiar loading)
    await agreementsStores.fetchAgreements(data)
    
    const newAgreements = agreementsStores.getAgreements
    
    // Solo comparar si hay cambios, NO actualizar aquí
    const hasChanges = JSON.stringify(agreements.value) !== JSON.stringify(newAgreements)
    
    return hasChanges
  } catch (error) {
    console.error('Error checking for updates:', error)
    return false
  }
}

// Limpiar búsqueda al cambiar de pestaña
watch(tabBilling, () => {
  searchQuery.value = "";
  currentPage.value = 1;
  currentPageAgreements.value = 1;
});

watchEffect(fetchData);

async function fetchData() {
  emit("loading", true);

  // Obtener datos de usuario
  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value.roles[0].name

  // Solo buscar en la pestaña activa
  if (tabBilling.value === "fakturor") {
    let data = {
      search: searchQuery.value,
      orderByField: "id",
      orderBy: "desc",
      limit: rowPerPage.value,
      page: currentPage.value,
      client_id: props.client_id,
    };

    await billingsStores.fetchBillings(data);
    billings.value = billingsStores.getBillings;
    totalPages.value = billingsStores.last_page;
    totalBillings.value = billingsStores.billingsTotalCount;
  } else if (tabBilling.value === "avtal") {
    let data = {
      search: searchQuery.value,
      orderByField: "id",
      orderBy: "desc",
      limit: rowPerPage.value,
      page: currentPageAgreements.value,
      client_id: props.client_id,
    };

    await agreementsStores.fetchAgreements(data);
    agreements.value = agreementsStores.getAgreements
    totalPagesAgreements.value = agreementsStores.last_page
    totalAgreements.value = agreementsStores.agreementsTotalCount
  }
  
  emit("loading", false);
}

const updateBilling = (billingData) => {
  isConfirmStateDialogVisible.value = true;
  selectedBilling.value = { ...billingData };
};

const showBilling = (billingData) => {
  router.push({
    name: "dashboard-admin-billings-id",
    params: { id: billingData.id },
  });
};

const editBilling = (billingData) => {
  router.push({
    name: "dashboard-admin-billings-edit-id",
    params: { id: billingData.id },
  });
};

const openLink = function (billingData) {
  window.open(themeConfig.settings.urlStorage + billingData.file);
};

const updateState = async () => {
  isConfirmStateDialogVisible.value = false;

  emit("loading", true);

  let res = await billingsStores.updateState(selectedBilling.value.id);

  emit("loading", false);
  selectedBilling.value = {};

  advisor.value = {
    type: res.data.success ? "success" : "error",
    message: res.data.success ? "Fakturan uppdaterad!" : res.data.message,
    show: true,
  };

  emit("alert", advisor);

  setTimeout(() => {
    advisor.value = {
      type: "",
      message: "",
      show: false,
    };

    emit("alert", advisor);
  }, 3000);

  await fetchData();

  return true;
};

const printInvoice = async (billing) => {
  try {
    const response = await fetch(
      themeConfig.settings.urlbase +
        "proxy-image?url=" +
        themeConfig.settings.urlStorage +
        billing.file
    );
    const blob = await response.blob();

    const blobUrl = URL.createObjectURL(blob);

    const iframe = document.createElement("iframe");
    iframe.style.display = "none";
    iframe.src = blobUrl;

    iframe.onload = () => {
      iframe.contentWindow.print();
    };

    document.body.appendChild(iframe);
  } catch (error) {
    console.error("Error:", error);
  }
};

const duplicate = (billing) => {
  router.push({
    name: "dashboard-admin-billings-duplicate-id",
    params: { id: billing.id },
  });
};

const credit = (billingData) => {
  isConfirmKreditera.value = true;
  selectedBilling.value = { ...billingData };
};

const reminder = async () => {
  emit("loading", true);
  isConfirmSendMailReminder.value = false;

  billingsStores
    .reminder(Number(selectedBilling.value.id))
    .then((res) => {
      emit("loading", false);
      selectedBilling.value = {};

      advisor.value = {
        type: res.data.success ? "success" : "error",
        message: res.data.success
          ? "Påminnelse skickad framgångsrikt"
          : res.data.message,
        show: true,
      };

      emit("alert", advisor);

      setTimeout(() => {
        advisor.value = {
          type: "",
          message: "",
          show: false,
        };
        emit("alert", advisor);
      }, 3000);
    })
    .catch((err) => {
      advisor.value = {
        type: "error",
        message: err.message,
        show: true,
      };
      emit("alert", advisor);

      setTimeout(() => {
        advisor.value = {
          type: "",
          message: "",
          show: false,
        };
        emit("alert", advisor);
      }, 3000);

      emit("loading", false);
    });
};

const sendReminder = (billingData) => {
  isConfirmSendMailReminder.value = true;
  selectedBilling.value = { ...billingData };
};

const kreditera = () => {
  emit("loading", true);
  isConfirmKreditera.value = false;

  billingsStores.credit(Number(selectedBilling.value.id))
      .then((res) => {
          let data = {
              message: 'Framgångsrik kredit',
              error: false
          }
          
          emit("loading", false);
          
          router.push({ name : 'dashboard-admin-billings-id', params: { id: res.data.data.billing.id } })
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
      
          emit("loading", false);
      })
}


const send = (billingData) => {
  // Si es un agreement (tiene agreement_type_id), manejar como agreement
  if (billingData.agreement_type_id) {
    isConfirmSendMailVisible.value = true
    selectedAgreement.value = { ...billingData }
  } else {
    // Es un billing
    isConfirmSendMailVisible.value = true
    selectedBilling.value = { ...billingData }
  }
};

const addTag = (event) => {
  const newTag = event.target.value.trim();
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (newTag && emailRegex.test(newTag)) {
    // no hago nada, sino invalido
  } else {
    isValid.value = true;
    selectedTags.value.pop();
  }
};

const sendMails = async () => {
  if (!isValid.value) {
    isConfirmSendMailVisible.value = false;
    emit("loading", true);

    // Determinar si es billing o agreement
    let data, res;
    
    if (selectedAgreement.value.id) {
      // Es un agreement
      data = {
        id: selectedAgreement.value.id,
        emailDefault: emailDefault.value,
        emails: selectedTags.value,
      };
      res = await agreementsStores.sendMails(data);
    } else {
      // Es un billing
      data = {
        id: selectedBilling.value.id,
        emailDefault: emailDefault.value,
        emails: selectedTags.value,
      };
      res = await billingsStores.sendMails(data);
    }

    emit("loading", false);

    advisor.value = {
      type: res.data.success ? "success" : "error",
      message: res.data.success ? (selectedAgreement.value.id ? "Avtalan är skickad!" : "Fakturan är skickad!") : res.data.message,
      show: true,
    };

    emit("alert", advisor);

    setTimeout(() => {
      selectedTags.value = [];
      existingTags.value = [];
      emailDefault.value = true;
      selectedAgreement.value = {};

      advisor.value = {
        type: "",
        message: "",
        show: false,
      };

      emit("alert", advisor);
    }, 3000);

    await fetchData();

    return true;
  }
};

const resolveStatus = state_id => {
  if (state_id === 4)
    return { class: 'pending' }
  if (state_id === 7)
    return { class: 'success' }   
  if (state_id === 8)
    return { class: 'error' }
  if (state_id === 9)
    return { class: 'error' }
}

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + '...';
  }
  return text;
};

const resolveStatusAgreement = state => {
  if (state === 'created')
    return { 
      name: 'Skapad',
      class: 'info',
      icon: 'custom-star'
    }
  if (state === 'sent')
    return { 
      name: 'Skickad',
      class: 'success',
      icon: 'custom-forward'
    }
  if (state === 'signed')
    return { 
      name: 'Signerad',
      class: 'success',
      icon: 'custom-signature'
    }
  if (state === 'pending')
    return { 
      name: 'Skapad',
      class: 'info',
      icon: 'custom-star'
    }
  if (state === 'delivered')
    return { 
      name: 'Levererad',
      class: 'success',
      icon: 'custom-check-mark-2'
    }
  if (state === 'reviewed')
    return { 
      name: 'Granskad',
      class: 'info',
      icon: 'custom-eye'
    }
  if (state === 'delivery_issues')
    return { 
      name: 'Leveransproblem',
      class: 'pending',
      icon: 'custom-risk'
    }
  if (state === 'failed')
    return { 
      name: 'Misslyckades',
      class: 'error',
      icon: 'custom-close'
    }
}

// ========================================
// 👉 Agreement Actions
// ========================================

const isTrackerDialogVisible = ref(false)
const trackerAgreement = ref(null)
const isTrackerPreviewVisible = ref(false)
const trackerPreviewPdfSource = ref(null)
const trackerPreviewError = ref('')

const isSignatureDialogVisible = ref(false)
const signatureEmail = ref('')
const refSignatureForm = ref()
const isStaticSignatureFlow = ref(false)
const selectedAgreement = ref({})
const isConfirmDeleteDialogVisible = ref(false)

const goToTracker = (agreementData) => {
  openTracker(agreementData)
}

const openTracker = async (agreement) => {
  await fetchData()
  trackerAgreement.value = agreement
  isTrackerDialogVisible.value = true

  try {
    const response = await agreementsStores.showAgreement(agreement.id)
    trackerAgreement.value = response
  } catch (e) {
    console.error('Failed to refresh agreement details', e)
  }
}

const openTrackerPreview = async () => {
  if (!trackerAgreement.value) return
  isTrackerPreviewVisible.value = true
  emit("loading", true)
  trackerPreviewError.value = ''
  try {
    const response = await agreementsStores.getAdminPreviewPdf(trackerAgreement.value.id)
    trackerPreviewPdfSource.value = URL.createObjectURL(response.data)
  } catch (e) {
    trackerPreviewError.value = 'Kunde inte ladda PDF-förhandsvisning.'
  } finally {
    emit("loading", false)
  }
}

watch(isTrackerPreviewVisible, val => {
  if (!val && trackerPreviewPdfSource.value) {
    URL.revokeObjectURL(trackerPreviewPdfSource.value)
    trackerPreviewPdfSource.value = null
  }
})

const openStaticSignatureDialog = (agreementData) => {
  selectedAgreement.value = { ...agreementData }
  isStaticSignatureFlow.value = true
  
  // Intentar obtener el email de diferentes fuentes según el tipo de agreement
  let email = ''
  if (agreementData.agreement_client?.email) {
    email = agreementData.agreement_client.email
  } else if (agreementData.offer?.client?.email) {
    email = agreementData.offer.client.email
  } else if (agreementData.commission?.client?.email) {
    email = agreementData.commission.client.email
  } else if (agreementData.vehicle_client?.client?.email) {
    email = agreementData.vehicle_client.client.email
  }
  
  signatureEmail.value = email
  isSignatureDialogVisible.value = true
}

const submitStaticSignatureRequest = async () => {
  const { valid } = await refSignatureForm.value?.validate()
  if (!valid) return

  isSignatureDialogVisible.value = false
  emit("loading", true)

  try {
    const payload = {
      agreementId: selectedAgreement.value.id,
      email: signatureEmail.value
    }

    const response = await agreementsStores.requestStaticSignature(payload)

    advisor.value = {
      type: 'success',
      message: response.data.message || 'Signeringsförfrågan har skickats!',
      show: true,
    }
    
    emit("alert", advisor)
    
    await fetchData()

  } catch (error) {
    advisor.value = {
      type: 'error',
      message: error.response?.data?.message || 'Ett fel uppstod när begäran skickades.',
      show: true,
    }
    
    emit("alert", advisor)
  } finally {
    emit("loading", false)
    signatureEmail.value = ''
    setTimeout(() => {
      advisor.value = { show: false }
      emit("alert", advisor)
    }, 3000)
  }
}

const download = async(agreement) => {
  try {
    const response = await fetch(themeConfig.settings.urlbase + 'proxy-image?url=' + themeConfig.settings.urlStorage + agreement.file)
    const blob = await response.blob()
    
    const blobUrl = URL.createObjectURL(blob)

    const a = document.createElement('a')
    a.href = blobUrl
    a.download = agreement.file.replace('pdfs/', '')
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)

  } catch (error) {
    console.error('Error:', error)
  }
}

const editAgreement = agreementData => {
  var route = ''

  switch(agreementData.agreement_type_id) {
    case 1: 
      route = 'dashboard-admin-agreements-sales-edit-id'
    break
    case 2:
      route = 'dashboard-admin-agreements-purchase-edit-id'
    break
    case 3:
      route = 'dashboard-admin-agreements-mediation-edit-id'
    break   
    case 4:
      route = 'dashboard-admin-agreements-business-edit-id'
    break 
  }

  router.push({ name : route , params: { id: agreementData.id } })
}

const showDeleteDialog = agreementData => {
  isConfirmDeleteDialogVisible.value = true
  selectedAgreement.value = { ...agreementData }
}

const deleteAgreement = async () => {
  isConfirmDeleteDialogVisible.value = false
  emit("loading", true)

  try {
    await agreementsStores.deleteAgreement(selectedAgreement.value.id)
    
    advisor.value = {
      type: 'success',
      message: 'Avtalet har tagits bort!',
      show: true,
    }
    
    emit("alert", advisor)
    
    await fetchData()
  } catch (error) {
    advisor.value = {
      type: 'error',
      message: 'Ett fel uppstod när avtalet skulle tas bort.',
      show: true,
    }
    
    emit("alert", advisor)
  } finally {
    emit("loading", false)
    selectedAgreement.value = {}
    setTimeout(() => {
      advisor.value = { show: false }
      emit("alert", advisor)
    }, 3000)
  }
}

const trackerEvents = computed(() => {
  if (!trackerAgreement.value) return []

  const items = []
  let tokens = []
  if (Array.isArray(trackerAgreement.value.token)) {
    tokens = trackerAgreement.value.token
  } else if (trackerAgreement.value.token) {
    tokens = [trackerAgreement.value.token]
  }
  
  const latestToken = tokens.length > 0
    ? [...tokens].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0]
    : null

  // Si tenemos historial de token, usar esos registros
  if (latestToken && latestToken.histories && latestToken.histories.length > 0) {
    const history = [...latestToken.histories].sort((a, b) => new Date(a.id) - new Date(b.id))
    
    // Check if there's a 'signed' event in the history
    const hasSignedEvent = history.some(event => event.event_type === 'signed')
    
    history.forEach(event => {
      const eventConfig = getEventConfig(event.event_type, event)
      if (eventConfig) {
        items.push({
          key: event.event_type,
          title: eventConfig.title,
          meta: new Date(event.created_at).toLocaleString('en-GB', { 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit', 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit', 
            hour12: false 
          }),
          text: event.description || eventConfig.text,
          color: eventConfig.color,
          bgClass: eventConfig.bgClass,
          icon: eventConfig.icon,
          showFile: event.event_type === 'signed' || (event.event_type === 'created' && !hasSignedEvent),
          ipAddress: event.ip_address,
          userAgent: event.user_agent
        })
      }
    })
  }
  
  // Assign sides strictly alternating
  return items.map((item, index) => ({
    ...item,
    side: index % 2 === 0 ? 'right' : 'left'
  }))
})

const getEventConfig = (eventType, event) => {
  const configs = {
    'created': {
      title: 'Avtal skapad',
      text: trackerAgreement.value?.title || 'Avtal',
      color: '#00EEB0',
      bgClass: 'status-info',
      icon: 'custom-star'
    },
    'sent': {
      title: 'Signeringsförfrågan skickad',
      text: `Skickad till ${event.metadata?.email || 'mottagare'}`,
      color: '#1890FF',
      bgClass: 'status-success',
      icon: 'custom-forward'
    },
    'delivered': {
      title: 'E-post levererad',
      text: 'E-postmeddelandet har levererats framgångsrikt',
      color: '#52C41A',
      bgClass: 'status-success',
      icon: 'custom-check-mark-2'
    },
    'delivery_issues': {
      title: 'Leveransproblem',
      text: 'Det uppstod problem med e-postleveransen',
      color: '#FAAD14',
      bgClass: 'status-warning',
      icon: 'custom-risk'
    },
    'reviewed': {
      title: 'Avtal granskat',
      text: 'Kunden har öppnat och granskat avtalet',
      color: '#1890FF',
      bgClass: 'status-info',
      icon: 'custom-eye'
    },
    'signed': {
      title: 'Avtal signerat',
      text: 'Signeringen är slutförd',
      color: '#52C41A',
      bgClass: 'status-success',
      icon: 'custom-signature'
    },
    'failed': {
      title: 'Signeringen misslyckades',
      text: 'Ett fel inträffade under signeringsprocessen',
      color: '#FF4D4F',
      bgClass: 'status-error',
      icon: 'custom-close'
    }
  }
  return configs[eventType] || null
}

onMounted(() => {
  // Polling inteligente: solo activo cuando hay agreements esperando firma
  let pollingInterval = null
  
  const startPolling = () => {
    if (pollingInterval) return // Ya está corriendo
    
    pollingInterval = setInterval(async () => {
      // Solo hacer polling si:
      // 1. El tracker está visible O
      // 2. Hay agreements activos esperando interacción
      if (isTrackerDialogVisible.value && trackerAgreement.value?.id) {
        try {
          const response = await agreementsStores.showAgreement(trackerAgreement.value.id)
          const currentHistoryLength = trackerAgreement.value?.token?.histories?.length || 0
          const newHistoryLength = response?.token?.histories?.length || 0
          
          if (newHistoryLength > currentHistoryLength) {
            trackerAgreement.value = response
            // Llamar a fetchData con spinner ya que sabemos que hay cambios
            await fetchData()
          }
        } catch (e) {
          console.error('Failed to poll tracker updates:', e)
        }
      } else if (hasActiveAgreements.value) {
        // Verificar cambios sin spinner
        const hasChanges = await checkForUpdates()
        // Si hay cambios, llamar a fetchData para actualización completa
        if (hasChanges) {
          await fetchData()
        }
      }
    }, 5000) // Poll every 5 seconds
    
    window._customerBillingPollingInterval = pollingInterval
  }
  
  const stopPolling = () => {
    if (pollingInterval) {
      clearInterval(pollingInterval)
      pollingInterval = null
      window._customerBillingPollingInterval = null
    }
  }
  
  // Iniciar polling si hay agreements activos
  if (hasActiveAgreements.value) {
    startPolling()
  }
  
  // Watch para iniciar/detener polling según haya agreements activos
  watch(hasActiveAgreements, (hasActive) => {
    if (hasActive) {
      startPolling()
    } else {
      stopPolling()
    }
  })
  
  // Detener polling cuando la pestaña está oculta, reanudar cuando vuelve
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      stopPolling()
    } else {
      // Reiniciar polling si es necesario
      if (hasActiveAgreements.value) {
        startPolling()
      }
    }
  })
})

onBeforeUnmount(() => {
  // Limpiar el polling interval
  if (window._customerBillingPollingInterval) {
    clearInterval(window._customerBillingPollingInterval)
    window._customerBillingPollingInterval = null
  }
})

</script>

<template>
  <section class="billing-panel border rounded-lg pa-4 h-100">
    <VCard>
      <VCardText class="d-flex flex-column pa-0 gap-6">
        <div class="d-flex" :class="windowWidth < 1024 ? 'flex-column gap-2' : 'flex-row'">

          <div class="search" :class="windowWidth < 1024 ? 'd-flex' : 'd-none'">
            <VTextField v-model="searchQuery" placeholder="Sök" clearable />
          </div>

          <VTabs v-model="tabBilling" class="billing-tabs" :show-arrows="false">
            <VTab value="fakturor">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
              >
                <mask id="path-1-inside-1_901_411" fill="white">
                  <path
                    d="M17.76 7.68H12V1.92C12 1.65504 12.2146 1.44 12.48 1.44C12.7454 1.44 12.96 1.65504 12.96 1.92V6.72H17.76C18.0254 6.72 18.24 6.93504 18.24 7.2C18.24 7.46496 18.0254 7.68 17.76 7.68Z"
                  />
                </mask>
                <path
                  d="M17.76 7.68H12V1.92C12 1.65504 12.2146 1.44 12.48 1.44C12.7454 1.44 12.96 1.65504 12.96 1.92V6.72H17.76C18.0254 6.72 18.24 6.93504 18.24 7.2C18.24 7.46496 18.0254 7.68 17.76 7.68Z"
                  fill="#454545"
                />
                <path
                  d="M12 7.68H11V8.68H12V7.68ZM12.96 6.72H11.96V7.72H12.96V6.72ZM17.76 7.68V6.68H12V7.68V8.68H17.76V7.68ZM12 7.68H13V1.92H12H11V7.68H12ZM12 1.92H13C13 2.20668 12.7675 2.44 12.48 2.44V1.44V0.440002C11.6616 0.440002 11 1.10341 11 1.92H12ZM12.48 1.44V2.44C12.1925 2.44 11.96 2.20668 11.96 1.92H12.96H13.96C13.96 1.10341 13.2984 0.440002 12.48 0.440002V1.44ZM12.96 1.92H11.96V6.72H12.96H13.96V1.92H12.96ZM12.96 6.72V7.72H17.76V6.72V5.72H12.96V6.72ZM17.76 6.72V7.72C17.4725 7.72 17.24 7.48668 17.24 7.2H18.24H19.24C19.24 6.38341 18.5784 5.72 17.76 5.72V6.72ZM18.24 7.2H17.24C17.24 6.91333 17.4725 6.68 17.76 6.68V7.68V8.68C18.5784 8.68 19.24 8.0166 19.24 7.2H18.24Z"
                  fill="#454545"
                  mask="url(#path-1-inside-1_901_411)"
                />
                <mask id="path-3-inside-2_901_411" fill="white">
                  <path
                    d="M5.28027 10.56H12.0387L11.1939 11.52H5.28027V10.56Z"
                  />
                </mask>
                <path
                  d="M5.28027 10.56H12.0387L11.1939 11.52H5.28027V10.56Z"
                  fill="#454545"
                />
                <path
                  d="M5.28027 10.56V9.56H4.28027V10.56H5.28027ZM12.0387 10.56L12.7894 11.2206L14.2507 9.56H12.0387V10.56ZM11.1939 11.52V12.52H11.6459L11.9446 12.1806L11.1939 11.52ZM5.28027 11.52H4.28027V12.52H5.28027V11.52ZM5.28027 10.56V11.56H12.0387V10.56V9.56H5.28027V10.56ZM12.0387 10.56L11.288 9.89937L10.4432 10.8594L11.1939 11.52L11.9446 12.1806L12.7894 11.2206L12.0387 10.56ZM11.1939 11.52V10.52H5.28027V11.52V12.52H11.1939V11.52ZM5.28027 11.52H6.28027V10.56H5.28027H4.28027V11.52H5.28027Z"
                  fill="#454545"
                  mask="url(#path-3-inside-2_901_411)"
                />
                <mask id="path-5-inside-3_901_411" fill="white">
                  <path
                    d="M9.11067 16.32C9.18747 16.4736 9.28347 16.6128 9.39387 16.7472C9.29787 16.9248 9.21147 17.1024 9.15387 17.28H5.28027V16.32H9.11067Z"
                  />
                </mask>
                <path
                  d="M9.11067 16.32C9.18747 16.4736 9.28347 16.6128 9.39387 16.7472C9.29787 16.9248 9.21147 17.1024 9.15387 17.28H5.28027V16.32H9.11067Z"
                  fill="#454545"
                />
                <path
                  d="M9.11067 16.32L10.0051 15.8728L9.72871 15.32H9.11067V16.32ZM9.39387 16.7472L10.2736 17.2227L10.5931 16.6317L10.1666 16.1125L9.39387 16.7472ZM9.15387 17.28V18.28H9.88083L10.1051 17.5885L9.15387 17.28ZM5.28027 17.28H4.28027V18.28H5.28027V17.28ZM5.28027 16.32V15.32H4.28027V16.32H5.28027ZM9.11067 16.32L8.21625 16.7672C8.33451 17.0037 8.47625 17.2055 8.62115 17.3819L9.39387 16.7472L10.1666 16.1125C10.0907 16.0201 10.0404 15.9435 10.0051 15.8728L9.11067 16.32ZM9.39387 16.7472L8.51417 16.2717C8.40831 16.4675 8.28821 16.7077 8.20265 16.9715L9.15387 17.28L10.1051 17.5885C10.1347 17.4971 10.1874 17.3821 10.2736 17.2227L9.39387 16.7472ZM9.15387 17.28V16.28H5.28027V17.28V18.28H9.15387V17.28ZM5.28027 17.28H6.28027V16.32H5.28027H4.28027V17.28H5.28027ZM5.28027 16.32V17.32H9.11067V16.32V15.32H5.28027V16.32Z"
                  fill="#454545"
                  mask="url(#path-5-inside-3_901_411)"
                />
                <path
                  d="M5.28027 13.44H9.50427C9.23547 13.7136 9.04347 14.04 8.93307 14.4H5.28027V13.44Z"
                  fill="#454545"
                />
                <path
                  d="M17.7604 22.08H2.40043V1.92002H12.2836L17.7604 7.39682V9.14882C18.0964 8.93282 18.418 8.74562 18.7204 8.58242V7.00322L12.6772 0.960022H1.44043V23.04H18.7204V21.4176C18.3892 21.3312 18.0676 21.2064 17.7604 21.0528V22.08Z"
                  fill="#454545"
                />
                <path
                  d="M23.5586 15.984L23.0114 15.7968L20.6306 13.0704L20.4482 12.8592L20.0018 12.3456C20.0834 12.264 20.1602 12.1824 20.237 12.1056C20.2946 12.0432 20.3522 11.9856 20.405 11.9232C20.4962 11.8272 20.5826 11.736 20.6642 11.6448C20.7554 11.5392 20.8466 11.4336 20.933 11.3328C21.0194 11.232 21.101 11.1312 21.173 11.0352C21.2354 10.9536 21.2978 10.8768 21.3506 10.8C21.4274 10.6944 21.4946 10.5936 21.557 10.4976C21.6098 10.4112 21.6626 10.3296 21.701 10.2528C21.7442 10.176 21.7778 10.104 21.8114 10.032C21.845 9.96478 21.869 9.90238 21.8882 9.83998C21.9026 9.79198 21.9122 9.74877 21.9218 9.70557C21.9314 9.67197 21.941 9.63838 21.941 9.60958C21.9794 9.35998 21.9266 9.15838 21.7922 8.99038C21.7538 8.94718 21.7106 8.90398 21.6674 8.87038C21.653 8.85598 21.6386 8.84638 21.6194 8.84158C21.5858 8.81758 21.5522 8.79837 21.5186 8.78397C21.4994 8.77437 21.4754 8.76478 21.4562 8.76478C21.4178 8.75038 21.3794 8.74078 21.341 8.73118C21.2786 8.71678 21.2114 8.71198 21.1394 8.71198C21.0338 8.71198 20.9138 8.72638 20.789 8.75038C20.6594 8.77918 20.5202 8.81758 20.3714 8.87038C20.237 8.91838 20.0882 8.97598 19.9346 9.04798C19.7474 9.12958 19.541 9.23037 19.325 9.34557C19.133 9.44637 18.9314 9.56158 18.7202 9.68638C18.461 9.83998 18.1874 10.008 17.9042 10.1952C17.8562 10.2288 17.8082 10.2576 17.7602 10.2912C17.7026 10.3296 17.645 10.368 17.5874 10.4064C17.4098 10.5264 17.2322 10.6512 17.045 10.7856C17.0258 10.7808 17.0018 10.776 16.9778 10.7712C16.4786 10.7088 16.0562 10.6656 15.6338 10.6272C15.6098 10.6272 15.5858 10.6224 15.5618 10.6224C15.1298 10.584 14.6978 10.5456 14.1842 10.4784C14.1218 10.4736 14.0642 10.4688 14.0066 10.4688C13.8098 10.4688 13.613 10.512 13.4402 10.5984C13.2722 10.6704 13.1234 10.7808 12.9986 10.9248L12.4754 11.52L10.7858 13.44L10.277 14.0208C10.1522 14.1312 10.0514 14.2608 9.9746 14.4C9.8642 14.592 9.797 14.8032 9.7778 15.0288C9.7586 15.3264 9.821 15.624 9.9602 15.8784C10.0082 15.9696 10.0658 16.0512 10.133 16.1328C10.1906 16.2 10.253 16.2624 10.325 16.32C10.3682 16.3584 10.4114 16.3872 10.4594 16.416C10.5266 16.464 10.6034 16.5072 10.6802 16.536C10.6418 16.584 10.6082 16.632 10.5746 16.68C10.4786 16.8144 10.3922 16.944 10.3202 17.0688C10.277 17.1408 10.2386 17.2128 10.205 17.28C10.1906 17.304 10.1762 17.3328 10.1666 17.3568C10.157 17.3712 10.1522 17.3856 10.1474 17.4C10.1186 17.4576 10.0946 17.5152 10.0754 17.5728C10.0562 17.6256 10.037 17.6784 10.0226 17.736C10.0082 17.784 9.9986 17.8272 9.9938 17.8704C9.989 17.8992 9.9842 17.9232 9.9842 17.952C9.9794 17.9664 9.9794 17.9856 9.9794 18V18.048C9.9794 18.0864 9.9842 18.12 9.989 18.1488C9.989 18.1728 9.9938 18.1968 10.0034 18.2208L9.5522 18.5856C9.4514 18.672 9.437 18.8208 9.5186 18.9264C9.5666 18.984 9.6338 19.0128 9.7058 19.0128C9.7586 19.0128 9.8114 18.9984 9.8546 18.96L10.2914 18.6048C10.3826 18.6624 10.5122 18.7056 10.7042 18.7056C10.7522 18.7056 10.805 18.7008 10.8626 18.696C10.8866 18.6912 10.9106 18.6912 10.9346 18.6864C10.9538 18.6864 10.973 18.6816 10.997 18.6816C11.0018 18.6768 11.0114 18.6768 11.0162 18.6768C11.1506 18.648 11.309 18.6096 11.4914 18.5472C11.4962 18.5472 11.501 18.5424 11.5058 18.5424C11.5106 18.5424 11.5154 18.5376 11.525 18.5328C11.5826 18.5136 11.645 18.4944 11.7074 18.4656C11.7842 18.4368 11.8658 18.4032 11.9474 18.3648C12.0914 18.3024 12.245 18.2304 12.4082 18.144C12.5042 18.1008 12.6002 18.048 12.701 17.9904C12.797 17.9376 12.9026 17.88 13.0082 17.8176C13.0178 17.8128 13.0274 17.808 13.037 17.7984C13.0658 17.784 13.0946 17.7648 13.1234 17.7504C13.2002 17.7072 13.277 17.6592 13.3586 17.6064C13.4498 17.5584 13.5458 17.496 13.6418 17.4336C13.7186 17.3856 13.7954 17.3376 13.8722 17.2848C14.0114 17.1984 14.1554 17.1024 14.3042 17.0016C14.4242 16.92 14.549 16.8336 14.6786 16.7424L15.3218 17.472L16.805 19.1424C16.8914 19.2384 16.9826 19.3296 17.0738 19.4208C17.165 19.512 17.2658 19.5936 17.3666 19.6704C17.4338 19.728 17.501 19.776 17.573 19.8288C17.5874 19.8384 17.6018 19.848 17.6162 19.8576C17.6594 19.8864 17.6978 19.9152 17.741 19.9392C17.741 19.944 17.7458 19.944 17.7458 19.944C17.7506 19.9488 17.7554 19.9488 17.7602 19.9536C17.909 20.0496 18.0626 20.136 18.2258 20.2128C18.245 20.2224 18.2594 20.232 18.2786 20.2368L18.2834 20.2416C18.3218 20.256 18.3602 20.2752 18.3986 20.2896C18.4898 20.3328 18.5858 20.3664 18.6818 20.4C18.6962 20.4048 18.7058 20.4096 18.7202 20.4144C18.9554 20.496 19.2002 20.5584 19.4498 20.5968L22.301 21.0576C22.325 21.0624 22.3538 21.0672 22.3778 21.0672C22.6082 21.0672 22.8146 20.8992 22.853 20.664C22.8962 20.4 22.7186 20.1552 22.4546 20.112L19.6034 19.6512C19.5026 19.6368 19.4018 19.6128 19.3058 19.5888C19.2146 19.5648 19.1282 19.5408 19.0418 19.512C19.0322 19.5072 19.0178 19.5024 19.0082 19.4976C18.9986 19.4928 18.9938 19.4928 18.9842 19.488C18.9746 19.4832 18.965 19.4832 18.9554 19.4784C18.8786 19.4544 18.8066 19.4256 18.7346 19.392C18.7298 19.392 18.725 19.3872 18.7202 19.3872C18.5426 19.3056 18.3698 19.2096 18.2066 19.0992C18.0482 18.9936 17.8994 18.8736 17.7602 18.744C17.6786 18.6672 17.597 18.5904 17.525 18.504L16.0034 16.7952V16.7904L14.6978 15.3216C14.549 15.1296 14.549 14.9376 14.5634 14.8368C14.5778 14.712 14.6402 14.6016 14.7218 14.5392C14.789 14.4912 14.8754 14.4672 14.9666 14.4672C15.077 14.4672 15.197 14.5008 15.2978 14.5632L15.3218 14.5776L15.797 14.8848L17.405 15.9168L17.7602 16.1472L18.0434 16.3296L18.7202 15.8064L19.325 15.3408C19.5362 15.1776 19.5746 14.8752 19.4162 14.664C19.3922 14.6352 19.3634 14.6064 19.3346 14.5872C19.3106 14.568 19.2914 14.5536 19.2626 14.5392H19.2578C19.2482 14.5296 19.2338 14.5296 19.2242 14.5248C19.2098 14.5152 19.1906 14.5104 19.1762 14.5056C19.1666 14.5008 19.157 14.496 19.1426 14.496C19.133 14.496 19.1234 14.496 19.1138 14.4912C19.0898 14.4816 19.061 14.4816 19.0322 14.4816C18.9314 14.4816 18.8258 14.5104 18.7394 14.5776L18.7202 14.592L17.9954 15.1536L17.7602 15.0048L17.3426 14.7312C17.4866 14.616 17.621 14.5056 17.7602 14.3856C18.053 14.1408 18.3314 13.9008 18.6002 13.6608C18.605 13.656 18.6098 13.6512 18.6194 13.6464C18.6482 13.6224 18.6818 13.5936 18.7106 13.5696C18.7106 13.5648 18.7154 13.5648 18.7154 13.56L18.7202 13.5552C18.7586 13.5216 18.8018 13.488 18.8402 13.4496C19.0034 13.3056 19.1666 13.1616 19.3154 13.0176L19.9442 13.7376L22.4546 16.6224L23.2514 16.8912C23.3042 16.9104 23.3522 16.9152 23.405 16.9152C23.6066 16.9152 23.7938 16.7904 23.861 16.5888C23.9426 16.3392 23.8082 16.0656 23.5586 15.984ZM20.5874 9.80638L20.741 9.99838C20.6066 10.2096 20.4098 10.4736 20.141 10.7856L19.6946 10.2336C20.0546 10.032 20.3522 9.89758 20.5874 9.80638ZM10.9298 14.7264L13.7186 11.5584C13.757 11.52 13.8002 11.4864 13.8482 11.4624C13.9106 11.4336 13.9874 11.4192 14.0594 11.4288C14.2274 11.4528 14.3858 11.472 14.5394 11.4864C14.8706 11.5248 15.1778 11.5536 15.4802 11.5776C15.6242 11.592 15.773 11.6016 15.9218 11.6208C15.7826 11.7264 15.6434 11.8368 15.4994 11.952C15.3554 12.0624 15.2066 12.1824 15.0626 12.2976C14.9954 12.3552 14.9186 12.4176 14.8322 12.4848C14.8034 12.5088 14.7746 12.5328 14.741 12.5568C14.7074 12.5808 14.6738 12.6096 14.6402 12.6432C14.5874 12.6864 14.5346 12.7296 14.477 12.7776C14.3858 12.8544 14.285 12.936 14.1842 13.0272C14.1026 13.0944 14.0162 13.1664 13.9298 13.2432C13.7186 13.4256 13.493 13.6224 13.2674 13.8288C13.1762 13.9104 13.0802 13.9968 12.989 14.0832C12.893 14.1648 12.8018 14.256 12.7058 14.3424C12.6194 14.424 12.533 14.5104 12.4466 14.592C12.4274 14.6112 12.4082 14.6256 12.3938 14.6448C12.3458 14.688 12.3026 14.736 12.2546 14.7792C12.1826 14.8512 12.1106 14.9184 12.0434 14.9904C11.9714 15.0624 11.9042 15.1344 11.837 15.2016C11.765 15.2736 11.6978 15.3456 11.6354 15.4128C11.5442 15.5088 11.4578 15.6 11.3714 15.696H11.3666C11.3426 15.7056 11.3138 15.7056 11.2898 15.7056C11.2754 15.7056 11.2562 15.7056 11.2418 15.7008C11.093 15.6912 10.9586 15.624 10.8626 15.5088C10.7714 15.3984 10.7234 15.2544 10.7378 15.1056C10.7378 15.072 10.7426 15.0432 10.7522 15.0096C10.7618 14.976 10.7714 14.9472 10.7906 14.9136C10.8194 14.8416 10.8674 14.7792 10.9298 14.7264ZM11.2322 17.6208C11.165 17.6496 11.1026 17.6688 11.045 17.688C11.0594 17.664 11.0738 17.64 11.0882 17.616C11.117 17.568 11.1506 17.5152 11.189 17.4624C11.189 17.4576 11.1938 17.4528 11.1986 17.448V17.4432C11.3378 17.2368 11.5346 16.9824 11.789 16.6896L12.1826 17.1744C11.8226 17.3712 11.5346 17.5008 11.3138 17.592C11.309 17.592 11.2994 17.5968 11.2946 17.5968C11.2754 17.6064 11.2562 17.616 11.237 17.6208H11.2322ZM13.8338 16.1616C13.6322 16.296 13.445 16.4208 13.2674 16.5312C13.253 16.5408 13.2386 16.5504 13.229 16.5552C13.157 16.6032 13.0898 16.6464 13.0226 16.6848L12.4418 15.9648C12.5378 15.864 12.6434 15.7584 12.749 15.648C12.869 15.5328 12.9938 15.408 13.1234 15.2784C13.1474 15.2592 13.1714 15.2352 13.1954 15.2112C13.277 15.1296 13.3634 15.048 13.4546 14.9664C13.5026 14.9184 13.5506 14.8752 13.6034 14.8272C13.589 15.0384 13.6178 15.2448 13.6898 15.4464C13.7186 15.5328 13.757 15.6096 13.8002 15.6912C13.8482 15.7728 13.901 15.8544 13.9586 15.9312L14.0402 16.0224C13.9682 16.0704 13.901 16.1184 13.8338 16.1616ZM18.7202 12.2544C18.701 12.2688 18.6866 12.288 18.6674 12.3024C18.5762 12.3888 18.485 12.4752 18.389 12.5616C18.341 12.6048 18.293 12.648 18.245 12.6912C18.197 12.7344 18.1442 12.7776 18.0962 12.8256V12.8304C17.9906 12.9216 17.8802 13.0224 17.7602 13.1232C17.6018 13.2576 17.4386 13.4016 17.2658 13.5456C17.0402 13.7376 16.8002 13.9344 16.5506 14.136L16.493 14.1888L15.8114 13.752C15.7682 13.728 15.725 13.6992 15.677 13.68C15.6722 13.68 15.6674 13.6752 15.6626 13.6752C15.6242 13.6512 15.581 13.632 15.5426 13.6224C15.5042 13.6032 15.4658 13.5888 15.4274 13.5792C15.3746 13.56 15.3218 13.5456 15.2642 13.5408C15.2066 13.5264 15.149 13.5168 15.0962 13.5168C15.2786 13.3584 15.4658 13.2048 15.6626 13.0464C15.7826 12.9504 15.8978 12.8592 16.0082 12.7728C16.0754 12.7152 16.1426 12.6624 16.2098 12.6096C16.3682 12.4848 16.5266 12.3648 16.6802 12.2496C16.781 12.1728 16.877 12.096 16.973 12.0288C16.9922 12.0144 17.0066 12 17.021 11.9904C17.1506 11.8944 17.2754 11.7984 17.4002 11.712C17.5202 11.6208 17.645 11.5344 17.7602 11.4528C17.8562 11.3856 17.9522 11.3184 18.0482 11.2512C18.149 11.184 18.245 11.1168 18.341 11.0544C18.4754 10.968 18.6002 10.8864 18.7202 10.8096C18.7682 10.7808 18.8114 10.752 18.8546 10.7232L19.157 11.0928L19.4882 11.5008C19.2818 11.712 19.0514 11.9376 18.797 12.1824C18.773 12.2064 18.749 12.2304 18.7202 12.2544Z"
                  fill="#454545"
                />
              </svg>
              Fakturor
            </VTab>
            <VTab value="avtal">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
              >
                <path
                  d="M12 19.68H17.76"
                  stroke="#454545"
                  stroke-miterlimit="10"
                />
                <path
                  d="M6.71973 9.12H17.2797"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M6.71973 12H17.2797"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M6.71973 14.88H17.2797"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M6.71973 17.28H17.2797"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M8.63965 9.59998V17.28"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M6.71973 9.12V16.8"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M17.2803 9.12V16.8"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M14.8799 9.59998V17.28"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M3.36035 22.56V1.44H20.6404V22.56H3.36035Z"
                  stroke="#454545"
                  stroke-miterlimit="10"
                  stroke-linecap="round"
                />
                <path
                  d="M6.24023 6.23999H17.7602"
                  stroke="#454545"
                  stroke-miterlimit="10"
                />
              </svg>
              Avtal
            </VTab>
          </VTabs>

          <VSpacer class="d-none d-md-block" />

          <div class="search" :class="windowWidth < 1024 ? 'd-none' : 'd-flex'">
            <VTextField v-model="searchQuery" placeholder="Sök" clearable />
          </div>

          <!-- Mobile filter button hidden per requirement -->
        </div>
        <VWindow v-model="tabBilling">
          <VWindowItem value="fakturor">
            <VTable 
              v-if="!$vuetify.display.smAndDown" 
              v-show="billings.length"
              class="p-0 text-no-wrap"
              style="border-radius: 0 !important"
            >
              <!-- 👉 table head -->
              <thead>
                <tr>
                  <th scope="col"># Faktura</th>
                  <th scope="col" v-if="role === 'SuperAdmin' || role === 'Administrator'">Leverantör</th>
                  <th scope="col" class="text-center">Summa</th>
                  <th scope="col" class="text-center">Fakturadatum</th>
                  <th scope="col" class="text-center">Förfaller</th>
                  <th scope="col" class="text-center">Status</th>
                  <th scope="col">Skapad av</th>
                  <th scope="col" v-if="$can('edit', 'billings') || $can('delete', 'billings')"></th>
                </tr>
              </thead>
              <!-- 👉 table body -->
              <tbody v-show="billings.length">
                <tr
                  v-for="billing in billings"
                  :key="billing.id"
                  style="height: 3rem"
                >
                  <td>{{ billing.invoice_id }}</td>
                  <td class="text-wrap" v-if="role === 'SuperAdmin' || role === 'Administrator'">
                    <span v-if="billing.supplier">
                      {{ billing.supplier.user.name }}
                      {{ billing.supplier.user.last_name ?? "" }}
                    </span>
                  </td>
                  <td class="text-center">
                    {{ formatNumber(billing.total + billing.amount_discount) ?? "0,00" }} kr
                  </td>
                  <td class="text-center">{{ billing.invoice_date }}</td>
                  <td class="text-center">{{ billing.due_date }}</td>
                  <!-- 😵 Statuses -->
                  <td class="text-center text-wrap d-flex justify-center align-center">
                    <div
                      class="status-chip"
                      :class="`status-chip-${resolveStatus(billing.state.id)?.class}`"
                    >
                      {{ billing.state.name }}
                    </div>
                  </td>
                  <td style="width: 1%; white-space: nowrap">
                    <div class="d-flex align-center gap-x-1">
                      <VAvatar
                        :variant="billing.user.avatar ? 'outlined' : 'tonal'"
                        size="38"
                      >
                        <VImg
                          v-if="billing.user.avatar"
                          style="border-radius: 50%"
                          :src="themeConfig.settings.urlStorage + billing.user.avatar"
                        />
                        <span v-else>{{ avatarText(billing.user.name) }}</span>
                      </VAvatar>
                      <div class="d-flex flex-column">
                        <span class="font-weight-medium">
                          {{ billing.user.name }} {{ billing.user.last_name ?? "" }}
                        </span>
                        <span class="text-sm text-disabled">
                          <VTooltip 
                            v-if="billing.user.email && billing.user.email.length > 20"
                            location="bottom" >
                            <template #activator="{ props }">
                              <span v-bind="props" class="cursor-pointer">
                                {{ truncateText(billing.user.email, 20) }}
                              </span>
                            </template>
                            <span>{{ billing.user.email }}</span>
                          </VTooltip>
                          <span class="text-sm text-disabled"v-else>{{ billing.user.email }}</span>
                        </span>
                      </div>
                    </div>
                  </td> 
                  <td
                    class="text-center"
                    style="width: 3rem"
                    v-if="$can('edit', 'billings') || $can('delete', 'billings')"
                  >
                    <VMenu>
                      <template #activator="{ props }">
                        <VBtn v-bind="props" icon variant="text" class="btn-white">
                          <VIcon icon="custom-dots-vertical" size="22" />
                        </VBtn>
                      </template>
                      <VList>
                        <VListItem
                          v-if="$can('view', 'billings')"
                          @click="showBilling(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-eye" size="24" class="mr-2" />
                          </template>
                          <VListItemTitle>Se detaljer</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="$can('edit', 'billings') && (billing.state_id === 4 || billing.state_id === 8)"
                          @click="updateBilling(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-cash-2" size="24" class="mr-2" />
                          </template>
                          <VListItemTitle>Betala</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="
                            $can('view', 'billings') &&
                            (billing.state_id === 4 || billing.state_id === 8)
                          "
                          @click="editBilling(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-pencil" size="24" class="mr-2" />
                          </template>
                          <VListItemTitle>Redigera</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="$can('view', 'billings')"
                          @click="printInvoice(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-print" size="24" class="mr-2" />
                          </template>
                          <VListItemTitle>Skriv ut</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="$can('view', 'billings')"
                          @click="openLink(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-pdf" size="24" class="mr-2" />
                          </template>
                          <VListItemTitle>Visa som PDF</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="$can('edit', 'billings')"
                          @click="duplicate(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-duplicate" size="24" class="mr-2" />
                          </template>
                          <VListItemTitle>Duplicera</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="$can('edit', 'billings') && billing.state_id === 8"
                          @click="sendReminder(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-alarm" size="24" class="mr-2" />
                          </template>
                          <VListItemTitle>Påminnelse</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="$can('view', 'billings')"
                          @click="send(billing)"
                        >
                          <template #prepend>
                            <VIcon icon="custom-paper-plane" size="24" class="mr-2" />
                          </template>
                          <VListItemTitle>Skicka</VListItemTitle>
                        </VListItem>

                        <VListItem
                          v-if="$can('edit', 'billings') && billing.state_id !== 9"
                          @click="credit(billing)"
                        >
                          <template #prepend>
                            <VIcon
                              icon="custom-cancel-contract"
                              size="24"
                              class="mr-2"
                            />
                          </template>
                          <VListItemTitle>Kreditera</VListItemTitle>
                        </VListItem>
                      </VList>
                    </VMenu>
                  </td>
                </tr>
              </tbody>
            </VTable>

            <div
              v-if="!billings.length"
              class="empty-state"
              :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
            >
              <VIcon
                :size="$vuetify.display.mdAndDown ? 80 : 120"
                icon="custom-order"
              />
              <div class="empty-state-content">
                <div class="empty-state-title">Inga fakturor skapade än</div>
                <div class="empty-state-text">
                  Här kommer alla dina skapade fakturor att listas.
                </div>
              </div>
            </div>

            <VExpansionPanels
              class="expansion-panels pb-6"
              v-if="billings.length && $vuetify.display.smAndDown"
            >
              <VExpansionPanel v-for="billing in billings" :key="billing.id">
                <VExpansionPanelTitle
                  collapse-icon="custom-chevron-right"
                  expand-icon="custom-chevron-down"
                >
                  <span class="order-id">{{ billing.invoice_id }}</span>
                  <div class="order-title-box">
                    <span class="title-panel">
                      {{ billing.client.fullname ?? "" }}</span
                    >
                    <div class="title-organization">
                      Summa
                      <div class="text-black">
                        {{
                          formatNumber(billing.total + billing.amount_discount) ??
                          "0,00"
                        }}
                        kr
                      </div>
                    </div>
                  </div>
                </VExpansionPanelTitle>
                <VExpansionPanelText>
                  <div class="mb-6">
                    <div class="expansion-panel-item-label">Fakturadatum:</div>
                    <div class="expansion-panel-item-value">
                      {{ billing.invoice_date }}
                    </div>
                  </div>
                  <div class="mb-6">
                    <div class="expansion-panel-item-label">Forfaller:</div>
                    <div class="expansion-panel-item-value">
                      {{ billing.due_date }}
                    </div>
                  </div>
                  <div class="mb-6">
                    <div class="expansion-panel-item-label">Status:</div>
                    <div class="expansion-panel-item-value">
                      <div
                        class="status-chip"
                        :class="`status-chip-${resolveStatus(billing.state.id)?.class}`"
                      >
                        {{ billing.state.name }}
                      </div>
                    </div>
                  </div>
                  <div class="d-flex gap-4">
                    <VBtn class="btn-light flex-1" @click="showBilling(billing)">
                      <VIcon icon="custom-eye" size="24" />
                      Se detaljer
                    </VBtn>

                    <VBtn class="btn-light" icon @click="selectedBillingForAction = billing; isMobileActionDialogVisible = true">
                      <VIcon icon="custom-dots-vertical" size="24" />
                    </VBtn>
                  </div>
                </VExpansionPanelText>
              </VExpansionPanel>
            </VExpansionPanels>

            <div
              v-if="billings.length"
              :class="windowWidth < 1024 ? 'd-block pt-0' : 'd-flex pt-4'"
              class="align-center flex-wrap gap-4 px-0"
            >
              <span class="text-pagination-results">
                {{ paginationData }}
              </span>

              <VSpacer class="d-none d-md-block" />

              <VPagination
                v-model="currentPage"
                size="small"
                :total-visible="4"
                :length="totalPages"
                next-icon="custom-chevron-right"
                prev-icon="custom-chevron-left"
              />
            </div>
          </VWindowItem>
          <VWindowItem value="avtal">
            <VTable
              v-if="!$vuetify.display.smAndDown"
              v-show="agreements.length"
              class="p-0 text-no-wrap"
            >
              <!-- 👉 table head -->
              <thead>
                <tr>
                  <th scope="col" class="text-center"> Reg. nr </th>
                  <th scope="col" class="text-center"> Inbytesfordon Reg. nr </th>
                  <th scope="col" class="text-center"> Kredit / Leasing </th>
                  <th scope="col" class="text-center"> Typ </th>
                  <th scope="col" v-if="role === 'SuperAdmin' || role === 'Administrator'"> Leverantör </th>
                  <th scope="col" class="text-center"> Skapad </th>
                  <th scope="col" class="text-center"> 
                    Signera status                            
                    <VTooltip location="bottom" max-width="200"> 
                      <template #activator="{ props }">
                        <span v-bind="props" class="cursor-pointer">
                          <VIcon icon="custom-circle-help" size="20" />
                        </span>
                      </template>
                      Klicka för att se hur signeringsprocessen fortskrider.
                    </VTooltip>
                  </th>
                  <th scope="col"> Skapad Av </th>
                  <th scope="col" v-if="$can('edit', 'agreements') || $can('delete', 'agreements')"></th>
                </tr>
              </thead>
              <!-- 👉 table body -->
              <tbody>
                <tr 
                  v-for="agreement in agreements"
                  :key="agreement.id"
                  style="height: 3rem;">
                  <td class="text-center" @click="goToTracker(agreement)">
                    <span class="font-weight-medium cursor-pointer text-aqua">
                      {{ agreement.agreement_type_id === 4 ?
                        agreement.offer.reg_num : 
                        (agreement.agreement_type_id === 3 ? 
                          agreement.commission?.vehicle.reg_num   :
                          agreement.vehicle_client?.vehicle.reg_num 
                        )                    
                      }} 
                    </span> 
                  </td>
                  <td class="text-center"> {{ agreement.vehicle_interchange?.reg_num }} </td>                
                  <td class="text-center"> {{ formatNumber(agreement.installment_amount ?? 0) }} kr </td>
                  <td class="text-center"> {{ agreement.agreement_type.name  }}</td> 
                  <td class="text-wrap" v-if="role === 'SuperAdmin' || role === 'Administrator'">
                    <span v-if="agreement.supplier">
                      {{ agreement.supplier.user.name }}
                      {{ agreement.supplier.user.last_name ?? "" }}
                    </span>
                  </td>         
                  <td class="text-center">  
                    {{ new Date(agreement.created_at).toLocaleString('sv-SE', { 
                        year: 'numeric', 
                        month: '2-digit', 
                        day: '2-digit', 
                        hour: '2-digit', 
                        minute: '2-digit',
                        hour12: false
                    }) }}
                  </td>           
                  <!-- 👉 Status -->
                  <td class="text-center text-wrap d-flex justify-center align-center">
                    <div
                      v-if="agreement.token"
                      class="status-chip"
                      :class="`status-chip-${resolveStatusAgreement(agreement.token?.signature_status)?.class}`"
                    >
                      <VIcon size="16" :icon="resolveStatusAgreement(agreement.token?.signature_status)?.icon" class="action-icon" />
                      {{ resolveStatusAgreement(agreement.token?.signature_status)?.name }}
                    </div>

                    <div
                      v-else
                      class="status-chip"
                      :class="`status-chip-${resolveStatusAgreement('pending')?.class}`"
                    >
                      <VIcon size="16" :icon="resolveStatusAgreement('pending')?.icon" class="action-icon" />
                      {{ resolveStatusAgreement('pending')?.name }}
                    </div>
                  </td>
                  <td style="width: 1%; white-space: nowrap">
                    <div class="d-flex align-center gap-x-1">
                      <VAvatar
                        :variant="agreement.user.avatar ? 'outlined' : 'tonal'"
                        size="38"
                      >
                        <VImg
                          v-if="agreement.user.avatar"
                          style="border-radius: 50%"
                          :src="themeConfig.settings.urlStorage + agreement.user.avatar"
                        />
                        <span v-else>{{ avatarText(agreement.user.name) }}</span>
                      </VAvatar>
                      <div class="d-flex flex-column">
                        <span class="font-weight-medium">
                          {{ agreement.user.name }} {{ agreement.user.last_name ?? "" }}
                        </span>
                        <span class="text-sm text-disabled">
                          <VTooltip 
                            v-if="agreement.user.email && agreement.user.email.length > 20"
                            location="bottom">
                            <template #activator="{ props }">
                              <span v-bind="props" class="cursor-pointer">
                                {{ truncateText(agreement.user.email, 20) }}
                              </span>
                            </template>
                            <span>{{ agreement.user.email }}</span>
                          </VTooltip>
                          <span class="text-sm text-disabled"v-else>{{ agreement.user.email }}</span>
                        </span>
                      </div>
                    </div>
                  </td> 
                  <!-- 👉 Actions -->
                  <td class="text-center" style="width: 3rem;" v-if="$can('edit', 'agreements') || $can('delete', 'agreements')">      
                    <VMenu>
                      <template #activator="{ props }">
                        <VBtn v-bind="props" icon variant="text" class="btn-white">
                          <VIcon icon="custom-dots-vertical" size="22" />
                        </VBtn>
                      </template>
                      <VList>
                        <VListItem
                          v-if="$can('view','agreements')"
                          @click="goToTracker(agreement)">
                          <template #prepend>
                            <VIcon icon="custom-eye" size="24" class="mr-2" />
                          </template>
                          <VListItemTitle>Spårare</VListItemTitle>
                        </VListItem>
                        <VListItem 
                          v-if="$can('edit','agreements') && agreement.token?.signature_status === 'created'"
                          @click="openStaticSignatureDialog(agreement)">
                          <template #prepend>
                            <VIcon icon="custom-signature" class="mr-2" />
                          </template>
                          <VListItemTitle>Signera</VListItemTitle>
                        </VListItem>
                        <VListItem
                          v-if="$can('view', 'agreements')"
                          @click="openLink(agreement)">
                          <template #prepend>
                            <VIcon icon="custom-pdf" class="mr-2" />
                          </template>
                          <VListItemTitle>Visa som PDF</VListItemTitle>
                        </VListItem>
                        <VListItem v-if="$can('view','agreements') && agreement.token?.signature_status === 'signed'"
                          @click="send(agreement)">
                          <template #prepend>
                            <VIcon icon="custom-send" class="mr-2" />
                          </template>
                          <VListItemTitle>Sänd PDF</VListItemTitle>
                        </VListItem>
                        <VListItem v-if="$can('view','agreements')" @click="download(agreement)">
                          <template #prepend>
                            <VIcon icon="custom-download" class="mr-2"/>
                          </template>
                          <VListItemTitle>Ladda ner</VListItemTitle>
                        </VListItem>
                        <VListItem 
                          v-if="$can('edit','agreements') && agreement.token?.signature_status === 'created'"
                          @click="editAgreement(agreement)">
                          <template #prepend>
                            <VIcon icon="custom-pencil" size="24" />
                          </template>
                          <VListItemTitle>Redigera</VListItemTitle>
                        </VListItem>
                        <VListItem v-if="$can('delete','agreements')" @click="showDeleteDialog(agreement)">
                          <template #prepend>
                            <VIcon icon="custom-waste" size="24" class="mr-2" />
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
              v-if="!agreements.length"
              class="empty-state"
              :class="$vuetify.display.smAndDown ? 'px-6 py-0' : 'pa-4'"
            >
              <VIcon
                :size="$vuetify.display.smAndDown ? 80 : 120"
                icon="custom-f-agreement"
              />
              <div class="empty-state-content">
                <div class="empty-state-title">Du har inga sparade avtal</div>
                <div class="empty-state-text">
                  Här kommer alla dina köpeavtal och servicedokument att listas. Skapa ditt första avtal för att enkelt hantera och spåra dina överenskommelser.
                </div>
              </div>
            </div>

            <div v-if="agreements.length && $vuetify.display.smAndDown" class="pb-2">
              <div v-for="agreement in agreements" :key="agreement.id" class="mobile-card-wrapper mb-4">
                <div class="card-header-type">
                  {{ agreement.agreement_type.name }}
                </div>
                <VExpansionPanels class="custom-expansion-panels expansion-panels">
                  <VExpansionPanel elevation="0">
                    <VExpansionPanelTitle
                      collapse-icon="custom-chevron-right"
                      expand-icon="custom-chevron-down"
                      style="height: 56px !important;"
                    >
                      <span class="order-id">{{ agreement.id }}</span>
                      <div class="order-title-box">
                        <span class="text-aqua">
                          Reg. nr. {{ agreement.agreement_type_id === 4 ?
                              agreement.offer.reg_num : 
                              (agreement.agreement_type_id === 3 ? 
                                agreement.commission?.vehicle.reg_num   :
                                agreement.vehicle_client?.vehicle.reg_num 
                              )                    
                          }}
                        </span>
                      </div>
                    </VExpansionPanelTitle>
                    <VExpansionPanelText>
                      <div class="mb-6">
                        <div class="expansion-panel-item-label">Skapad av</div>
                        <div class="expansion-panel-item-value">
                          {{ agreement.user.name }} {{ agreement.user.last_name ?? '' }}
                        </div>
                      </div>
                      
                      <div class="mb-6">
                        <div class="expansion-panel-item-label">Status:</div>
                        <div class="expansion-panel-item-value">
                          <div
                            v-if="agreement.token"
                            class="status-chip"
                            :class="`status-chip-${resolveStatusAgreement(agreement.token?.signature_status)?.class}`"
                          >
                            <VIcon size="16" :icon="resolveStatusAgreement(agreement.token?.signature_status)?.icon" class="action-icon" />
                            {{ resolveStatusAgreement(agreement.token?.signature_status)?.name }}
                          </div>

                          <div
                            v-else
                            class="status-chip"
                            :class="`status-chip-${resolveStatusAgreement('pending')?.class}`"
                          >
                            <VIcon size="16" :icon="resolveStatusAgreement('pending')?.icon" class="action-icon" />
                          {{ resolveStatusAgreement('pending')?.name }}
                          </div>
                        </div>
                      </div>
                      
                      <div class="d-flex gap-4">
                        <VBtn class="btn-light flex-1" @click="goToTracker(agreement)"
                        >
                          <VIcon icon="custom-eye" size="24" />
                          Se detaljer
                        </VBtn>
                        
                        <VBtn class="btn-light" icon @click="selectedAgreementForAction = agreement; isMobileActionDialogVisible = true">
                          <VIcon icon="custom-dots-vertical" size="24" />
                        </VBtn>
                      </div>
                    </VExpansionPanelText>
                  </VExpansionPanel>
                </VExpansionPanels>
              </div>
            </div>

            <div
              v-if="agreements.length"
              :class="windowWidth < 1024 ? 'd-block pt-0' : 'd-flex pt-4'"
              class="align-center flex-wrap gap-4 px-0"
            >
              <span class="text-pagination-results">
                {{ paginationData2 }}
              </span>

              <VSpacer class="d-none d-md-block" />

              <VPagination
                v-model="currentPageAgreements"
                size="small"
                :total-visible="4"
                :length="totalPagesAgreements"
              />
            </div>
          </VWindowItem>
        </VWindow>
      </VCardText>
    </VCard>

    <!-- 👉 Confirm send -->
    <VDialog 
      v-model="isConfirmSendMailVisible" 
      persistent 
      class="action-dialog">
      <!-- Dialog close btn -->

      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmSendMailVisible = !isConfirmSendMailVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-paper-plane" class="action-icon" />
          <div class="dialog-title">
            {{ selectedAgreement.id ? 'Skicka avtal via e-post' : 'Skicka fakturan via e-post' }}
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Är du säker på att du vill skicka {{ selectedAgreement.id ? 'avtal' : 'fakturor' }} till följande
          e-postadresser?
        </VCardText>
        <VCardText class="d-flex flex-column gap-2">
          <VCheckbox
            v-model="emailDefault"
            :label="selectedAgreement.id ? (selectedAgreement.agreement_client?.email || 'N/A') : (selectedBilling.client?.email || 'N/A')"
            class="ml-2"
          />
          <VLabel class="text-body-2 text-high-emphasis" :text="selectedAgreement.id ? 'Ange e-postadresser för att skicka avtal' : 'Ange e-postadresser för att skicka fakturan'" />
          <VCombobox
            v-model="selectedTags"
            :items="existingTags"
            multiple
            chips
            deletable-chips
            clearable
            @blur="addTag"
            @keydown.enter.prevent="addTag"
            @input="isValid = false"
          />
          <span class="text-xs text-error" v-if="isValid"
            >E-postadressen måste vara en giltig e-postadress</span
          >
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions pt-0">
          <VBtn class="btn-light" @click="isConfirmSendMailVisible = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="sendMails"> Skicka </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm kreditera -->
    <VDialog 
      v-model="isConfirmKreditera" 
      persistent
      class="action-dialog"
    >
      <!-- Dialog close btn -->
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmKreditera = !isConfirmKreditera"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
         <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-cancel-contract" class="action-icon" />
          <div class="dialog-title">
            Kreditera faktura
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          En hel kreditering innebär att du tar bort din fordran på kunden till fullo. 
          Är du säker på att du vill kreditera fakturan
          <strong>#{{ selectedBilling.invoice_id }}</strong
          >?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmKreditera = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="kreditera"> Kreditera </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Update State -->
    <VDialog
      v-model="isConfirmStateDialogVisible"
      persistent
      class="action-dialog"
    >
      <!-- Dialog close btn -->
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmStateDialogVisible = !isConfirmStateDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-cash-2" class="action-icon" />
          <div class="dialog-title">
            Uppdatera status
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Är du säker på att du vill uppdatera fakturans status
          <strong>#{{ selectedBilling.invoice_id }}</strong> till betalda?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmStateDialogVisible = false">
            Avbryt
          </VBtn>
          <VBtn  class="btn-gradient" @click="updateState"> Acceptera </VBtn>
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
            v-if="$can('edit', 'billings') && (selectedBillingForAction.state_id === 4 || selectedBillingForAction.state_id === 8)"
            @click="updateBilling(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-cash-2" size="24" />
            </template>
            <VListItemTitle>Betala</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('edit', 'billings') &&  (selectedBillingForAction.state_id === 4 || selectedBillingForAction.state_id === 8)"
            @click="editBilling(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view', 'billings')"
            @click="printInvoice(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-print" size="24" />
            </template>
            <VListItemTitle>Skriv ut</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view', 'billings')"
            @click="openLink(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-pdf" size="24" />
            </template>
            <VListItemTitle>Visa som PDF</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('edit', 'billings')"
            @click="duplicate(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-duplicate" size="24" />
            </template>
            <VListItemTitle>Duplicera</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view', 'billings') && selectedBillingForAction.state_id === 8"
            @click="sendReminder(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-alarm" size="24" />
            </template>
            <VListItemTitle>Påminnelse</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('view', 'billings')"
            @click="send(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-paper-plane" size="24" />
            </template>
            <VListItemTitle>Skicka</VListItemTitle>
          </VListItem>
          <VListItem
            v-if="$can('edit', 'billings') && selectedBillingForAction.state_id !== 9"
            @click="credit(selectedBillingForAction); isMobileActionDialogVisible = false;"
          >
            <template #prepend>
              <VIcon icon="custom-cancel-contract" size="24" />
            </template>
            <VListItemTitle>Kreditera</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>

    <!-- 👉 Mobile Action Dialog -->
    <VDialog
      v-model="isMobileActionDialogVisibleAgreement"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem
              v-if="$can('view', 'agreements')"
              @click="openLink(selectedAgreementForAction); isMobileActionDialogVisibleAgreement = false;">
            <template #prepend>
              <VIcon icon="custom-pdf" class="mr-2" />
            </template>
            <VListItemTitle>Visa som PDF</VListItemTitle>
          </VListItem>
          <VListItem v-if="$can('view','agreements') && selectedAgreementForAction.token?.signature_status === 'signed'"
            @click="send(selectedAgreementForAction); isMobileActionDialogVisibleAgreement = false;">
            <template #prepend>
              <VIcon icon="custom-send" class="mr-2" />
            </template>
            <VListItemTitle>Sänd PDF</VListItemTitle>
          </VListItem>
          <VListItem v-if="$can('view','agreements')" @click="download(selectedAgreementForAction); isMobileActionDialogVisibleAgreement = false;">
            <template #prepend>
              <VIcon icon="custom-download" class="mr-2"/>
            </template>
            <VListItemTitle>Ladda ner</VListItemTitle>
          </VListItem>
          <VListItem 
            v-if="$can('edit','agreements') && selectedAgreementForAction.token?.signature_status === 'created'"
            @click="editAgreement(selectedAgreementForAction); isMobileActionDialogVisibleAgreement = false;">
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem v-if="$can('delete','agreements')" @click="showDeleteDialog(selectedAgreementForAction); isMobileActionDialogVisibleAgreement = false;">
            <template #prepend>
              <VIcon icon="custom-waste" size="24" class="mr-2" />
            </template>
            <VListItemTitle>Ta bort</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm Delete Agreement -->
    <VDialog
      v-model="isConfirmDeleteDialogVisible"
      persistent
      class="action-dialog" >
      
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmDeleteDialogVisible = !isConfirmDeleteDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-filled-waste" class="action-icon" />
          <div class="dialog-title">
            Ta bort avtal
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Är du säker på att du vill radera kontraktet för fordonet
          <strong>
            #{{ selectedAgreement.agreement_type_id === 4 ?
                selectedAgreement.offer.reg_num : 
                (selectedAgreement.agreement_type_id === 3 ? 
                  selectedAgreement.commission?.vehicle.reg_num   :
                  selectedAgreement.vehicle_client?.vehicle.reg_num 
                )                    
            }}
          </strong>?.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="deleteAgreement">
              Acceptera
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Signature Dialog -->
    <VDialog
      v-model="isSignatureDialogVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isSignatureDialogVisible = !isSignatureDialogVisible"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VForm
          ref="refSignatureForm"
          @submit.prevent="submitStaticSignatureRequest"
        >
        <VCard flat class="card-form">
          <VCardText class="dialog-title-box">
            <VIcon size="32" icon="custom-signature" class="action-icon" />
            <div class="dialog-title">
              Skicka signeringsförfrågan
            </div>
          </VCardText>     
          <VCardText class="dialog-text">
            Ange den e-postadress till vilken du vill att länken för att underteckna fordonsavtalet, 
            Reg.nr
            <strong>
              {{ selectedAgreement.agreement_type_id === 4 ?
                selectedAgreement.offer?.reg_num : 
                (selectedAgreement.agreement_type_id === 3 ? 
                  selectedAgreement.commission?.vehicle.reg_num   :
                  selectedAgreement.vehicle_client?.vehicle.reg_num 
                )                    
              }}
            </strong>
            ska skickas.
          </VCardText>
          <VCardText class="dialog-text pt-2">
            <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-postadress*" />
            <VTextField
              v-model="signatureEmail"
              placeholder="kund@exempel.com"
            />
          </VCardText>
          <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
            <VBtn
              class="btn-light"
              @click="isSignatureDialogVisible = false">
                Avbryt
            </VBtn>
            <VBtn class="btn-gradient" type="submit">
                Skicka
            </VBtn>
          </VCardText>
        </VCard>
      </VForm>
    </VDialog>

    <!-- 👉 Tracker Dialog -->
    <VDialog
      v-model="isTrackerDialogVisible"
      persistent
      class="action-dialog" >
      <!-- Dialog close btn -->
      <VBtn
        icon
          class="btn-white close-btn"
          @click="isTrackerDialogVisible = false"
        >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <div class="dialog-title">
            Signaturprocess
          </div>
        </VCardText>
        
        <VCardText class="tracker-body">
          <div class="snake-timeline">
            <div 
              v-for="(item, index) in trackerEvents" 
              :key="item.key"
              class="snake-item"
              :class="[
                index % 2 === 0 ? 'icon-right' : 'icon-left',
                { 'is-first': index === 0 },
                { 'is-last': index === trackerEvents.length - 1 }
              ]"
            >
              <!-- Curved Border Line -->
              <div class="snake-curve"></div>
              
              <!-- Content Block (centered, covers the line) -->
              <div class="snake-content">
                <span class="snake-date">{{ item.meta }}</span>
                <h4 class="snake-heading">{{ item.title }}</h4>
                <p class="snake-text">{{ item.text }}</p>
                <div 
                  v-if="(item.key === 'created' || item.key === 'signed') && trackerAgreement && item.showFile" 
                  class="snake-file-btn" 
                  @click="openTrackerPreview"
                >
                  <VIcon icon="custom-pdf-2" size="14" />
                  <span>{{ item.key === 'created' ? trackerAgreement.file?.split('/').pop() : 'Signerad PDF' }}</span>
                </div>
              </div>

              <!-- Status Icon Circle -->
              <div class="snake-icon-wrapper" :class="item.bgClass">
                <VIcon :icon="item.icon" size="16" color="white" />
              </div>
            </div>
          </div>
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Tracker Preview Dialog -->
    <VDialog 
      v-model="isTrackerPreviewVisible"
      persistent
      class="action-dialog" >
      <!-- Dialog close btn -->
      <VBtn
        icon
          class="btn-white close-btn"
          @click="isTrackerPreviewVisible = !isTrackerPreviewVisible"
        >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
        <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-signature" class="action-icon" />
          <div class="dialog-title">
            Förhandsvisa avtal
          </div>
        </VCardText>
        <VDivider />
        <VCardText class="d-flex justify-center p-0" style="min-height:400px;">
          <VAlert 
            v-if="trackerPreviewError" 
            type="error" class="mb-4">
            {{ trackerPreviewError }}
          </VAlert>
          <VuePdfEmbed 
            v-if="trackerPreviewPdfSource && !trackerPreviewError" 
            :source="trackerPreviewPdfSource" 
            :width="windowWidth < 1024 ? 300 : 450"
            />
        </VCardText>
      </VCard>
    </VDialog>

    <!-- 👉 Confirm send reminder -->
    <VDialog 
      v-model="isConfirmSendMailReminder" 
      persistent
      class="action-dialog"
    >
      <!-- Dialog close btn -->
      <VBtn
        icon
        class="btn-white close-btn"
        @click="isConfirmSendMailReminder = !isConfirmSendMailReminder"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VCard>
         <VCardText class="dialog-title-box">
          <VIcon size="32" icon="custom-alarm" class="action-icon" />
          <div class="dialog-title">
            Skicka påminnelse via e-post
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Vill du skicka ett påminnelsemeddelande för faktura
          <strong>#{{ selectedBilling.invoice_id }}</strong
          >?
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="isConfirmSendMailReminder = false">
            Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="reminder"> Skicka </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss" scope>
.text-center {
  text-align: center !important;
}

.v-input--disabled svg rect {
  fill: #28c76f !important;
}

.v-input--disabled {
  pointer-events: visible !important;
  cursor: no-drop !important;
}

.billing-panel {
  border: 1px solid #e7e7e7 !important;
}

@media (max-width: 768px) {
  .billing-panel {
    border: 0px !important;
    padding: 0 !important;
  }
}
</style>

<style lang="scss" scoped>
  .bottom-sheet-card {
    border-radius: 20px 20px 0 0;
    width: 100%;
    max-height: 75vh;
    overflow-y: auto;
  }

  /* PDF Placement Styles */
  :deep(.pdf-container-admin) {
    position: relative;
    cursor: crosshair;
    box-shadow: 0 0 20px rgba(0,0,0,0.5);
    width: 90%;
    max-width: 800px;
    height: 95%;
    overflow-y: auto;
  }

  :deep(.pdf-container-admin > div){
    width: 100% !important;
  }

  :deep(.signature-placeholder-admin) {
      position: absolute;
      z-index: 10;
      pointer-events: none;
  }

  :deep(.signature-placeholder-content) {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 2px dashed #ffc107;
      background-color: rgba(255, 193, 7, 0.2);
      border-radius: 8px;
      padding: 8px 12px;
      color: #ffc107;
      font-weight: 600;
      white-space: nowrap;
  }

  /* Mobile Card Styles */
  .mobile-card-wrapper {
    border-radius: 16px;
    overflow: hidden;
    background-color: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }

  .card-header-type {
    background-color: #E7E7E7;
    padding: 4px;
    font-weight: 600;
    font-size: 12px;
    line-height: 16px;
    letter-spacing: 0;
    color: #5D5D5D;
    text-align: center;
  }

  .custom-expansion-panels {
    /* Remove default margins/shadows if needed */
    margin-top: 0;
    background-color: #f6f6f6 !important;
  }

  :deep(.custom-expansion-panels .v-expansion-panel) {
    background-color: transparent !important;
  }

  :deep(.custom-expansion-panels .v-expansion-panel-title) {
    padding: 16px;
    min-height: unset;
  }

  :deep(.custom-expansion-panels .v-expansion-panel-title__overlay) {
    background-color: transparent;
  }

  .reg-nr-text {
    color: #008C91;
    font-weight: 500;
  }

  :deep(.custom-expansion-panels .v-expansion-panel-title__icon .v-icon) {
    color: #008C91 !important;
  }

  .btn-details {
    /*border-color: #E0E0E0;*/
    text-transform: none;
    letter-spacing: normal;
    font-weight: 500;
    border: solid 1px #6e9383 !important;
    background-color: transparent !important;
  }

  /* Tracker Styles */
  .tracker-title {
    font-weight: 600;
    color: #333;
    font-size: 1.2rem;
  }

  .tracker-body {
    padding: 24px 32px 32px !important;
    max-height: 80vh;
    overflow-y: auto;
    
    /* Hide scrollbar but keep functionality */
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none;  /* IE and Edge */
    &::-webkit-scrollbar {
      display: none; /* Chrome/Safari/Edge */
    }
  }

  /* ===== SNAKE TIMELINE - PROD STYLE (Pixel Perfect Refinement) ===== */
  .snake-timeline {
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 600px; 
    margin: 0 auto;
    padding: 0 32px;
  }

  .snake-item {
    position: relative;
    display: flex;
    width: 100%;
    min-height: 140px;
    box-sizing: border-box;
    
    /* SOLUCIÓN DOBLE LÍNEA */
    & + .snake-item {
      margin-top: -2px; 
    }
  }

  /* Variables SASS Ajustadas */
  $curve-width: 2px;
  $curve-color: #E7E7E7;
  $curve-radius: 70px; /* Aumentado para curva más pronunciada/circular */
  $gap-to-center: 12px; /* 12px per Figma */

  /* ===== LA CURVA (SNAKE LINE) ===== */
  .snake-curve {
    position: absolute;
    top: 0;
    bottom: 0;
    width: calc(50% + 1px); 
    border: 0 solid $curve-color;
    pointer-events: none;
    z-index: 1;
  }

  /* --- Ítem: Icono Derecha (Contenido Izquierda) --- */
  .snake-item.icon-right {
    flex-direction: row;
    justify-content: flex-end; 
    padding-right: 10%; 
    text-align: right;

    .snake-curve {
      left: 50%; 
      border-top-width: $curve-width;
      border-right-width: $curve-width;
      border-bottom-width: $curve-width;
      border-radius: 0 $curve-radius $curve-radius 0;
    }

    .snake-content {
      align-items: flex-end; 
      margin-right: $gap-to-center;
    }
  }

  /* --- Ítem: Icono Izquierda (Contenido Derecha) --- */
  .snake-item.icon-left {
    flex-direction: row;
    justify-content: flex-start;
    padding-left: 10%; 
    text-align: left;

    .snake-curve {
      right: 50%; 
      border-top-width: $curve-width;
      border-left-width: $curve-width;
      border-bottom-width: $curve-width;
      border-radius: $curve-radius 0 0 $curve-radius;
    }

    .snake-content {
      align-items: flex-start; 
      margin-left: $gap-to-center;
    }
  }

  /* --- PRIMER ÍTEM (Corrección "Cola") --- */
  .snake-item.is-first {
    .snake-curve {
      top: 50%; 
      height: 50% !important;
      border-top-width: 0; /* IMPORTANTE: Eliminar borde superior */
      border-bottom-width: $curve-width;
    }
    
    &.icon-right .snake-curve {
      border-top-right-radius: 0; /* Eliminar curva superior */
      border-bottom-right-radius: $curve-radius;
      border-radius: 0 0 $curve-radius 0; /* Solo curva abajo-derecha */
    }

    &.icon-left .snake-curve {
      border-top-left-radius: 0; /* Eliminar curva superior */
      border-bottom-left-radius: $curve-radius;
      border-radius: 0 0 0 $curve-radius; /* Solo curva abajo-izquierda */
    }
  }

  /* --- ÚLTIMO ÍTEM (Final) --- */
  .snake-item.is-last {
    .snake-curve {
      top: 0;
      height: 50% !important; 
      border-top-width: $curve-width;
      border-bottom-width: 0; 
    }

    &.icon-right .snake-curve {
      border-radius: 0 $curve-radius 0 0;
    }
    &.icon-left .snake-curve {
      border-radius: $curve-radius 0 0 0;
    }
  }

  /* --- CONTENIDO DE TEXTO --- */
  .snake-content {
    position: relative;
    z-index: 10;
    display: flex;
    flex-direction: column;
    justify-content: center;
    width: 100%;
    max-width: 320px; 
    padding: 12px 0;
  }

  /* Typography */
  .snake-date {
    font-weight: 400;
    font-size: 12px;
    line-height: 16px;
    color: #878787;
    margin-bottom: 4px;
    text-transform: uppercase;
  }

  .snake-heading {
    font-weight: 600;
    font-size: 16px;
    line-height: 16px;
    text-align: right;
    color: #454545;
    margin: 0 0 4px 0;
  }

  .snake-text {
    font-weight: 400;
    font-size: 14px;
    line-height: 16px;
    color: #454545;
    margin: 0;
  }

  /* File Badge */
  .snake-file-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #E7E7E7;
    padding: 6px 12px;
    border-radius: 6px;
    margin-top: 8px;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;

    span {
      font-weight: 400;
      font-size: 14px;
      line-height: 16px;
      color: #454545;

      max-width: 160px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    
    &:hover {
      background: #E5E7EB;
      border-color: #D1D5DB;
    }
  }

  /* ===== ESTADO (ICONO) ===== */
  .snake-icon-wrapper {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    z-index: 20;
    top: 48%;
  }

  /* Icon Right */
  .snake-item.icon-right .snake-icon-wrapper {
    right: 0;
    transform: translate(50%, -50%);
  }

  /* Icon Left */
  .snake-item.icon-left .snake-icon-wrapper {
    left: 0;
    transform: translate(-50%, -50%);
  }

  /* Status Colors */
  .snake-icon-wrapper.status-success {
    background-color: #00EEB0;
  }

  .snake-icon-wrapper.status-info {
    background-color: #1890FF;
  }

  .snake-icon-wrapper.status-warning {
    background-color: #FAAD14;
  }

  .snake-icon-wrapper.status-error {
    background-color: #EF4444;
  }

   /* ===== RESPONSIVE ===== */
  @media (max-width: 480px) {
    .tracker-body {
      padding: 16px 12px 24px !important;
    }

    .snake-timeline {
      padding: 0 24px;
    }

    .snake-item {
      min-height: 120px;
    }
    
    .snake-content {
      max-width: none; 
    }

    .snake-item.icon-right .snake-content { margin-right: 20px; }
    .snake-item.icon-left .snake-content { margin-left: 20px; }

    .snake-icon-wrapper {
      width: 32px;
      height: 32px;
      
      .v-icon {
        font-size: 16px !important;
      }
    }
  }

  .file-upload-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 16px;
    border: 1px dashed #E7E7E7;
    border-radius: 8px;
    min-height: 88px !important;
    cursor: pointer;
    transition: all 0.2s ease;
    background-color: #F6F6F6;
    position: relative;

    &.has-error {
      border-color: rgb(var(--v-theme-error));
      background-color: rgba(var(--v-theme-error), 0.04);
    }
  }

  .file-upload-text {
    font-weight: 400;
    font-size: 16px;
    line-height: 24px;
    text-align: center;
    max-width: calc(100% - 40px);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .file-pdf-icon {
    font-size: 32px !important;
    width: 32px !important;
    height: 32px !important;
  }

  .file-remove-btn {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: white;
    cursor: pointer;
    transition: background-color 0.2s ease;

    &:hover {
      background-color: rgba(0, 0, 0, 0.2);
    }
  }

  .file-error-message {
    color: rgb(var(--v-theme-error));
    font-size: 12px;
    margin-top: 4px;
    padding-left: 8px;
  }

</style>

<style lang="scss">
.expansion-panels {
  .v-expansion-panel {
    border-radius: 6px !important;
    margin-bottom: 8px;

    &:not(:first-child)::after {
      border: none !important;
    }
  }

  &:not(.v-expansion-panels--variant-accordion)
    > :first-child:not(:last-child):not(.v-expansion-panel--active):not(
      .v-expansion-panel--before-active
    ) {
    border-radius: 16px !important;
  }

  &:not(.v-expansion-panels--variant-accordion)
    > :last-child:not(:first-child):not(.v-expansion-panel--active):not(
      .v-expansion-panel--after-active
    ) {
    border-radius: 16px !important;
  }

  &:not(.v-expansion-panels--variant-accordion)
    > :not(:first-child):not(:last-child):not(.v-expansion-panel--active):not(
      .v-expansion-panel--after-active
    ) {
    border-radius: 16px !important;
  }

  &:not(.v-expansion-panels--variant-accordion)
    > :not(:first-child):not(:last-child):not(.v-expansion-panel--active):not(
      .v-expansion-panel--before-active
    ) {
    border-radius: 16px !important;
  }
}
</style>
