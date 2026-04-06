<script setup>

import { useDisplay } from 'vuetify'
import { useMobilePaginationScroll } from '@/@core/composable/useMobilePaginationScroll'
import { useSuppliersStores } from '@/stores/useSuppliers'
import { useAuthStores } from '@/stores/useAuth'
import { useConfigsStores } from '@/stores/useConfigs'
import { useAppAbility } from '@/plugins/casl/useAppAbility'
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { formatNumber, formatNumberInteger, formatDateTime } from '@/@core/utils/formatters'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import html2pdf from 'html2pdf.js'
import PresetAvatarImage from "@/components/common/PresetAvatarImage.vue"; 
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import router from '@/router'

const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");
const sectionEl = ref(null)

const usersStores = useSuppliersStores()
const authStores = useAuthStores()
const configsStores = useConfigsStores()
const ability = useAppAbility()

const users = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalUsers = ref(0)

const isRequestOngoing = ref(true)
const hasLoaded = ref(false);

const permissionsRol = ref([])
const readonly = ref(false)

const isExportTypeMenuVisible = ref(false)
const exporteraMobile = ref(false)

const userData = ref(null)
const role = ref(null)
const COMPANY_STORAGE_KEY = 'clients_company_snapshot'

const readCachedCompany = () => {
  try {
    const cached = localStorage.getItem(COMPANY_STORAGE_KEY)
    if (!cached) return {}

    const parsed = JSON.parse(cached)
    return parsed && typeof parsed === 'object' ? parsed : {}
  } catch {
    return {}
  }
}

const company = ref(readCachedCompany())

const setCompany = (value) => {
  const normalized = value && typeof value === 'object' ? { ...value } : {}
  company.value = normalized
  localStorage.setItem(COMPANY_STORAGE_KEY, JSON.stringify(normalized))
}

const advisor = ref({
  type: '',
  message: '',
  show: false
})

useMobilePaginationScroll({
  targetRef: sectionEl,
  currentPage,
  isRequestOngoing,
  enabled: mdAndDown,
})

const emit = defineEmits([
  'alert'
])

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = users.value.length
    ? (currentPage.value - 1) * rowPerPage.value + 1
    : 0;
  const lastIndex =
    users.value.length + (currentPage.value - 1) * rowPerPage.value;
  return `${totalUsers.value} resultat`;
  // return `Visar ${firstIndex} till ${lastIndex} av ${totalClients.value} register`;
});


// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value
})

watch(searchQuery, () => {
  currentPage.value = 1
})

watchEffect(fetchData)

// 👉 Fetch users
async function fetchData() {
  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  let data = {
    search: searchQuery.value,
    limit: rowPerPage.value,
    page: currentPage.value
  }

  await usersStores.fetchReportUsers(data)

  users.value = usersStores.getUsers
  totalPages.value = usersStores.users_last_page
  totalUsers.value = usersStores.usersTotalCount

  hasLoaded.value = true;
  isRequestOngoing.value = false
}

const truncateText = (text, length = 15) => {
  if (text && text.length > length) {
    return text.substring(0, length) + "...";
  }
  return text;
};

onMounted(async () => {
  try {
    userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
    role.value = userData.value?.roles?.[0]?.name ?? null

    if (!role.value) return

    const { user_data, userAbilities } = await authStores.me(userData.value)

    localStorage.setItem('userAbilities', JSON.stringify(userAbilities))
    ability.update(userAbilities)
    localStorage.setItem('user_data', JSON.stringify(user_data))

    if (role.value === 'Supplier') {
      setCompany({
        ...(user_data?.user_detail ?? {}),
        email: user_data?.email ?? '',
        name: user_data?.name ?? '',
        last_name: user_data?.last_name ?? '',
      })
    } else if (role.value === 'User') {
      setCompany({
        ...(user_data?.supplier?.boss?.user?.user_detail ?? {}),
        email: user_data?.supplier?.boss?.user?.email ?? '',
        name: user_data?.supplier?.boss?.user?.name ?? '',
        last_name: user_data?.supplier?.boss?.user?.last_name ?? '',
      })
    } else {
      await configsStores.getFeature('company')
      await configsStores.getFeature('logo')

      const companyConfig = configsStores.getFeaturedConfig('company') ?? {}
      const logoConfig = configsStores.getFeaturedConfig('logo') ?? {}

      setCompany({
        ...companyConfig,
        logo: logoConfig.logo ?? companyConfig.logo ?? '',
      })
    }
  } catch (error) {
    console.error('Failed to load company data:', error)
  }
})

const downloadCSV = async () => {
  exporteraMobile.value = false
  isRequestOngoing.value = true

  try {
    const data = { limit: -1 }

    await usersStores.fetchReportUsers(data)

    const dataArray = usersStores.getUsers.map(element => ({
      NAMN: element.user?.name ?? element.name ?? '',
      EFTERNAMN: element.user?.last_name ?? element.last_name ?? '',
      E_POST: element.user?.email ?? element.email ?? '',
      FAKTUROR: element.invoices ?? 0,
      SWISH: element.swish ?? 0,
      AVTAL: element.agreements ?? 0,
    }))

    excelParser().exportDataFromJSON(dataArray, "report-users", "csv")
  } finally {
    isRequestOngoing.value = false
  }
}

const downloadPDF = async () => {
  exporteraMobile.value = false
  isRequestOngoing.value = true
  const pdfFontFamily = "'Gelion Regular', 'DM Sans', sans-serif"

  const escapeHtml = value => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')

  let pdfContainer = null

  try {
    const data = { limit: -1 }

    await usersStores.fetchReportUsers(data)

    if (document.fonts?.load) {
      await Promise.all([
        document.fonts.load(`400 12px ${pdfFontFamily}`),
        document.fonts.load(`600 32px ${pdfFontFamily}`),
      ])
    }

    const columnWidth = '25%'

    const rows = usersStores.getUsers.map(element => ({
      name: `${element.user?.name ?? element.name ?? ''} ${element.user?.last_name ?? element.last_name ?? ''}`.trim(),
      invoices: element.invoices ?? 0,
      swish: element.swish ?? 0,
      agreements: element.agreements ?? 0,
    }))

    const { headerMarkup } = await buildPdfTopHeader({
      company: company.value,
      title: 'Rapporter',
      themeConfig,
      escapeHtml,
      showCompanyDetailsWhenLogo: true,
    })

    const rowsMarkup = rows.map(item => `
      <tr style="height: 48px;">
        <td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.name)}</td>
        <td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.invoices)}</td>
        <td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.swish)}</td>
        <td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.agreements)}</td>
      </tr>
    `).join('')

    pdfContainer = document.createElement('div')
    pdfContainer.innerHTML = `
      <div style="font-family: ${pdfFontFamily} !important; color: #454545; background-color: #FFFFFF; letter-spacing: 0; width: 100%;">
        <table style="width: 100%; border-spacing: 0; border-collapse: separate; font-size: 11px; font-weight: 400;">
          <tbody>
            <tr>
              <td>
                ${headerMarkup}

                <table style="width: 100%; table-layout: fixed; border-spacing: 0; border-collapse: separate; margin-top: 10px; font-family: ${pdfFontFamily} !important; font-size: 11px;">
                    <thead>
                        <tr style="height: 48px;">
                            <td style="text-align: center; width: ${columnWidth}; padding: 0 8px; border-top-left-radius: 32px; border-bottom-left-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Namn</th>
                            <td style="text-align: center; width: ${columnWidth}; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Fakturor</th>
                            <td style="text-align: center; width: ${columnWidth}; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Swish</th>
                            <td style="text-align: center; width: ${columnWidth}; padding: 0 8px; border-top-right-radius: 32px; border-bottom-right-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Avtal</th>
                        </tr>
                    </thead>
                    <tbody>
                    ${rowsMarkup}
                    </tbody>
                </table>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    `

    document.body.appendChild(pdfContainer)

    await html2pdf()
      .set({
        margin: [12, 10, 12, 10],
        filename: 'report-users.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#FFFFFF' },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak: { mode: ['css', 'legacy'] },
      })
      .from(pdfContainer)
      .save()
  } finally {
    if (pdfContainer?.parentNode)
      pdfContainer.parentNode.removeChild(pdfContainer)

    isRequestOngoing.value = false
  }
}

defineExpose({
  resetFilters() {
    searchQuery.value = ''
    currentPage.value = 1
  }
})

</script>

<template>
    <section class="page-section p-0" ref="sectionEl">
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

        <VCard v-if="users" id="rol-list" >
            <VCardText class="px-0" :class="windowWidth < 1024 ? 'pb-0' : 'd-none'">
                <div class="title-tabs-profile">
                    Mitt team
                </div>
            </VCardText>

            <VCardTitle
                class="d-flex gap-6 justify-space-between px-0"
                :class="[
                    windowWidth < 1024 ? 'flex-column' : 'flex-row',
                    $vuetify.display.mdAndDown ? 'py-6 pa-0' : 'py-4'
                ]"
            >
                <div class="d-flex align-center flex-wrap gap-4 w-100 w-md-auto mobile-search-block"> 
                    <!-- 👉 Search  -->
                    <div class="search rol-list-filter">
                        <VTextField
                            v-model="searchQuery"
                            placeholder="Sök"
                            density="compact"
                            clearable
                        />
                    </div>
                </div>
                <div class="d-flex mobile-actions-block" :class="windowWidth < 1024 ? 'gap-2' : 'gap-4'">
                    <VMenu 
                        v-if="windowWidth >= 1024"
                        v-model="isExportTypeMenuVisible">
                        <template #activator="{ props }">
                            <VBtn
                                id="report-users-export-button"
                                class="btn-light w-auto"
                                block
                                v-bind="props"
                            >
                                <VIcon icon="custom-export" size="24" />
                                Exportera
                            </VBtn>
                        </template>

                        <VList>
                            <VListItem @click="isExportTypeMenuVisible = false; downloadPDF()">
                                <VListItemTitle>Exportera PDF</VListItemTitle>
                            </VListItem>
                            <VListItem @click="isExportTypeMenuVisible = false; downloadCSV()">
                                <VListItemTitle>Exportera Excel</VListItemTitle>
                            </VListItem>
                        </VList>
                    </VMenu>

                    <VBtn
                        v-if="windowWidth < 1024"
                        class="btn-light w-auto"
                        block
                        @click="exporteraMobile = true"
                    >
                        <VIcon icon="custom-export" size="24" />
                        Exportera
                    </VBtn>
                </div>
            </VCardTitle>

            <!-- SECTION Table -->
            <VTable 
                v-if="!$vuetify.display.mdAndDown"
                v-show="users.length"
                class="pt-2 px-0 pb-6 text-no-wrap rol-list-table"
                style="border-radius: 0 !important"
            >
                <!-- 👉 Table head -->
                <thead>
                    <tr>
                        <th scope="col"> #ID </th> 
                        <th scope="col"> Namn </th>
                        <th class="text-center" scope="col"> Fakturor </th>
                        <th class="text-center" scope="col"> Swish </th>
                        <th class="text-center" scope="col">Avtal</th>
                    </tr>
                </thead>

                <!-- 👉 Table Body -->
                <tbody>
                    <tr
                        v-for="user in users"
                        :key="user.user.id"
                        style="height: 3rem;"
                    >
                        <!-- 👉 Id -->
                        <td> {{ user.order_id }} </td>

                        <!-- 👉 name -->
                        <td class="text-wrap">
                            <div class="d-flex justify-between align-center font-weight-medium text-aqua">
                                <span class="flex-grow break-words">
                                {{ user.user.name }}  {{ user.user.last_name ?? '' }}
                                </span>
                            </div>
                        </td>

                        <!-- 👉 billings -->
                        <td class="text-center"> {{ user.invoices }} </td>

                        <!-- 👉 payouts -->
                        <td class="text-center"> {{ user.swish }} </td>

                        <!-- 👉 agreements -->
                        <td class="text-center"> {{ user.agreements }} </td>
                    </tr>
                </tbody>
            </VTable>

            <VExpansionPanels
                class="expansion-panels pb-6 px-0"
                v-if="users.length && windowWidth < 1024"
            >
                <VExpansionPanel v-for="user in users" :key="user.id" readonly>
                    <VExpansionPanelTitle
                        collapse-icon="custom-chevron-right"
                        expand-icon="custom-chevron-down"
                    >
                        <div class="d-flex align-center justify-space-between w-100">
                            <div class="d-flex align-center">
                                <span class="order-id">
                                    <VAvatar
                                    variant="outlined"
                                    size="38"
                                    >
                                        <VImg
                                            v-if="user.avatar"
                                            style="border-radius: 50%"
                                            :src="themeConfig.settings.urlStorage + user.avatar"
                                        />
                                        <PresetAvatarImage
                                            v-else
                                            :avatar-id="user.user_detail?.avatar_id"
                                        />
                                    </VAvatar>
                                </span>

                                <div class="order-title-box">
                                    <span class="title-panel">
                                        {{ user.name ?? '' }} {{ user.last_name ?? "" }}
                                    </span>
                                    <div class="title-organization">
                                        {{ truncateText(user.email, 20) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </VExpansionPanelTitle>
                    <VExpansionPanelText>
                        <div class="mb-6">
                            <div class="expansion-panel-item-label">Fakturor:</div>
                            <div class="expansion-panel-item-value">
                                {{ user.invoices ?? "" }}
                            </div>
                        </div>
                        <div class="mb-6">
                            <div class="expansion-panel-item-label">Swish:</div>
                            <div class="expansion-panel-item-value">
                                {{ user.swish ?? "" }}
                            </div>
                        </div>
                        <div>
                            <div class="expansion-panel-item-label">Avtal:</div>
                            <div class="expansion-panel-item-value">
                                {{ user.agreements ?? "" }}
                            </div>
                        </div>
                    </VExpansionPanelText>
                </VExpansionPanel>
            </VExpansionPanels>

            <div
                v-if="!isRequestOngoing && hasLoaded && !users.length"
                class="empty-state"
                :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
            >
                <VIcon
                :size="$vuetify.display.mdAndDown ? 80 : 120"
                icon="custom-account"
                />
                <div class="empty-state-content w-100 pa-4">
                    <div class="empty-state-title">Inget team ännu</div>
                    <div class="empty-state-text">
                        Bjud in dina medarbetare för att börja följa försäljning och prestation.
                    </div>
                </div>

                <VBtn
                    class="btn-ghost"
                    :to="{ name : 'dashboard-admin-suppliers-users-create' }"
                >
                    Bjud in medarbetare
                    <VIcon icon="custom-arrow-right" size="24" />
                </VBtn>
            </div>
            <!-- !SECTION -->

            <!-- SECTION Pagination -->
            <VCardText
                v-if="users.length"
                :class="windowWidth < 1024 ? 'd-block' : 'd-flex'"
                class="align-center flex-wrap gap-4 p-0"
            >
                <span class="text-pagination-results">
                {{ paginationData }}
                </span>

                <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

                <VPagination
                    v-model="currentPage"
                    size="small"
                    :total-visible="4"
                    :length="totalPages"
                    next-icon="custom-chevron-right"
                    prev-icon="custom-chevron-left"
                />
            </VCardText>
        </VCard>

        <!-- 👉 Export Mobile Dialog -->
        <VDialog
            v-model="exporteraMobile"
            transition="dialog-bottom-transition"
            content-class="dialog-bottom-full-width"
        >
            <VCard>
                <VList>
                    <VListItem @click="exporteraMobile = false; downloadPDF()">
                        <VListItemTitle>Exportera PDF</VListItemTitle>
                    </VListItem>

                    <VListItem @click="exporteraMobile = false; downloadCSV()">
                        <VListItemTitle>Exportera Excel</VListItemTitle>
                    </VListItem>
                </VList>
            </VCard>
        </VDialog>
    </section>
</template>

<style lang="scss">

  .title-tabs-profile {
    font-weight: 700;
    font-size: 24px;
    line-height: 100%;
    color: #454545;
  }

  .v-dialog {
    z-index: 1999 !important;
  }

  .user-list-filter {
    width: 100%;
  }

  @media(min-width: 991px){
    .user-list-filter {
      inline-size: 12rem;
    }
  }

  @media (max-width: 1023px) {
    .mobile-actions-block {
      order: 1;
    }

    .mobile-search-block {
      order: 2;
    }
  }

  .card-form {
    .v-list {
      padding: 28px 24px 40px !important;

      .v-list-item {
        margin-bottom: 0px;
        padding: 4px 0 !important;
        gap: 0px !important;

        .v-input--density-compact {
          --v-input-control-height: 48px !important;
        }

        .v-select .v-field,
        .v-autocomplete .v-field {

          .v-select__selection, .v-autocomplete__selection {
            align-items: center;
          }

          .v-field__input > input {
            top: 0px;
            left: 0px;
          }

          .v-field__append-inner {
            align-items: center;
            padding-top: 0px;
          }
        }

        .selector-user {
          .v-input__control {
            background: white !important;
            padding-top: 0 !important;
          }
          .v-input__prepend, .v-input__append {
            padding-top: 12px !important;
          }
        }

        .v-text-field {
          .v-input__control {
            padding-top: 0;
            input {
              min-height: 48px;
              padding: 12px 16px;
            }
          }
        }
      }
    }
    & .v-input {
      .v-input__prepend {
        padding-top: 12px !important;
      }
      & .v-input__control {
        .v-field {
          background-color: #f6f6f6;
          min-height: 48px !important;

          .v-text-field__suffix {
            padding: 12px 16px !important;
          }

          .v-field__input {
            min-height: 48px !important;
            padding: 12px 16px !important;

            input {
                min-height: 48px !important;
            }
          }

          .v-field-label {
            top: 12px !important;
          }

          .v-field__append-inner {
            align-items: center;
            padding-top: 0px;
          }
        }
      }
    }
  }

  .dialog-bottom-full-width {
    .v-card {
      border-radius: 24px 24px 0 0 !important;
    }
  }

  .mobile-user-actions-title {
    color: #878787;

    .v-expansion-panel-title__icon {
      .v-icon {
        color: #878787 !important;
      }
    }

    .mobile-actions-icon {
      color: #878787 !important;
    }
  }

</style>

<route lang="yaml">
  meta:
    permissionsAny:
      - action: view
        subject: users
      - action: view
        subject: my-team
</route>
