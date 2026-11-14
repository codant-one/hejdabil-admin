<script setup>

import tree from './tree.vue'
import create from './create.vue'
import show from './show.vue'
import edit from './edit.vue'
import destroy from './destroy.vue'

import { avatarText } from '@/@core/utils/formatters'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import { ref } from "vue";
import { useDisplay } from "vuetify";
import { themeConfig } from '@themeConfig'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import html2pdf from 'html2pdf.js';
import { useRolesStores } from '@/stores/useRoles'
import { excelParser } from '@/plugins/csv/excelParser'

const { width: windowWidth } = useWindowSize();

const rolesStores = useRolesStores()

const roles = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalRoles = ref(0)
const selectedRows = ref([])

const isRoleDetailDialog = ref(false)
const isRoleEditDialog = ref(false)
const isRoleDeleteDialog = ref(false)
const isConfirmActiveDialogVisible = ref(false)
const isMobileActionDialogVisible = ref(false);
const hasLoaded = ref(false);

const selectedRol = ref({})
const permissionsRol = ref([]) 
const readonly = ref(false)

const isRequestOngoing = ref(true)

const COMPANY_STORAGE_KEY = 'clients_company_snapshot';

const readCachedCompany = () => {
  try {
    const cached = localStorage.getItem(COMPANY_STORAGE_KEY);
    if (!cached) return {};

    const parsed = JSON.parse(cached);
    return parsed && typeof parsed === 'object' ? parsed : {};
  } catch {
    return {};
  }
};

const company = ref(readCachedCompany())

const { mdAndDown } = useDisplay();
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end");

const advisor = ref({
  type: '',
  message: '',
  show: false
})

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = roles.value.length ? (currentPage.value - 1) * rowPerPage.value + 1 : 0
  const lastIndex = roles.value.length + (currentPage.value - 1) * rowPerPage.value

  return `${totalRoles.value} resultat`;
  
  // return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalRoles.value } roller`
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value
})

watchEffect(fetchData)


// 👉 Fetch roles
async function fetchData() {
  isRequestOngoing.value = searchQuery.value !== '' ? false : true

  let data = {
    search: searchQuery.value,
    orderByField: 'id',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value
  }

  await rolesStores.fetchRoles(data)

  roles.value = rolesStores.getRoles
  totalPages.value = rolesStores.last_page
  totalRoles.value = rolesStores.rolesTotalCount

  isRequestOngoing.value = false
  hasLoaded.value = true
}


// show dialogs
const showRoleDetailDialog = function(rol){
  isRoleDetailDialog.value = true
  selectedRol.value = { ...rol }

  rol.permissions.forEach(function(pe) {
    permissionsRol.value.push(pe.name)
  })

  selectedRol.value.assignedPermissions = permissionsRol
  readonly.value= true
}

const showRoleEditDialog = function(rol){
  isRoleEditDialog.value = true
  selectedRol.value = { ...rol }

  rol.permissions.forEach(function(pe) {
    permissionsRol.value.push(pe.name)
  })

  selectedRol.value.assignedPermissions = permissionsRol
  readonly.value= false
}

const showRoledDeleteDialog = function($rol){
  isRoleDeleteDialog.value = true
  selectedRol.value = { ...$rol }
}

const showAlert = function(alert) {
  advisor.value.show = alert.value.show
  advisor.value.type = alert.value.type
  advisor.value.message = alert.value.message
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
    const data = {
      limit: -1 ,
      orderByField: "id",
      orderBy: "desc"
    }

    await rolesStores.fetchUsers(data)

    if (document.fonts?.load) {
      await Promise.all([
        document.fonts.load(`400 12px ${pdfFontFamily}`),
        document.fonts.load(`600 32px ${pdfFontFamily}`),
      ])
    }
    
    const rows = rolesStores.getUsers.map(element => ({
      id: element.id,
      name: element.name,
    }))

    const { headerMarkup } = await buildPdfTopHeader({
      company: company.value,
      title: 'Användare',
      themeConfig,
      escapeHtml,
      showCompanyDetailsWhenLogo: true,
    })

    const rowsMarkup = rows.map(item => `
      ${(() => {
        return `
      <tr style="height: 48px;">
        <td style="width: 10%; padding: 0 12px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.id)}</td>
        <td style="width: 50%; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.name)}</td>
      </tr>
    `
      })()}
    `).join('')

    pdfContainer = document.createElement('div')
    pdfContainer.innerHTML = `
      <div style="font-family: ${pdfFontFamily} !important; color: #454545; background-color: #FFFFFF; letter-spacing: 0; width: 100%;">
        <table style="width: 100%; border-spacing: 0; border-collapse: separate; font-size: 12px; font-weight: 400;">
          <tbody>
            <tr>
              <td>
                ${headerMarkup}

                <table style="width: 100%; table-layout: fixed; border-spacing: 0; border-collapse: separate; margin-top: 10px; font-family: ${pdfFontFamily} !important; font-size: 12px;">
                  <thead>
                    <tr style="height: 48px;">
                      <td style="text-align: center; width: 10%; padding: 0 12px; border-top-left-radius: 32px; border-bottom-left-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Id</td>
                      <td style="text-align: center; width: 40%; padding: 0 8px; border-top-right-radius: 32px; border-bottom-right-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Namn</td>
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
        filename: 'roller.pdf',
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

const downloadCSV = async () => {

  isRequestOngoing.value = true

  let data = { limit: -1}

  await rolesStores.fetchRoles(data)

  let dataArray = []

  rolesStores.getRoles.forEach(element => {
    let data = {
      ROLL: element.name
    }
        
    dataArray.push(data)
  })

  excelParser()
    .exportDataFromJSON(dataArray, "roller", "csv")

  isRequestOngoing.value = false
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
          <h2>Roller</h2>
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"/>

        <div class="d-flex gap-4">
          <VMenu v-if="windowWidth >= 1024">
            <template #activator="{ props }">
              <VBtn
                id="payout-export-button"
                class="btn-light w-auto"
                block
                v-bind="props"
              >
                <VIcon icon="custom-export" size="24" />
                Exportera
              </VBtn>
            </template>

            <VList>
              <VListItem @click="downloadPDF">
                <VListItemTitle>Exportera PDF</VListItemTitle>
              </VListItem>
              <VListItem @click="downloadCSV">
                <VListItemTitle>Exportera Excel</VListItemTitle>
              </VListItem>
            </VList>
          </VMenu>

          <VBtn
            v-if="windowWidth < 1024"
            id="payout-export-button"
            class="btn-light w-auto"
            block
            @click="exporteraMobile = true"
          >
            <VIcon icon="custom-export" size="24" />
            Exportera
          </VBtn>

          <create 
            @data="fetchData" 
            :type="'button'"
            @alert="showAlert"
          />

          <!-- <tree /> -->

        </div>
      </VCardTitle>

      <VCardText
        class="d-flex align-center justify-space-between gap-1"
        :class="$vuetify.display.mdAndDown ? 'p-6' : 'pa-4 gap-2'"
      >
        <!-- 👉 Search  -->
        <div class="search">
          <VTextField v-model="searchQuery" placeholder="Sök" clearable />
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

        <div
          v-if="!$vuetify.display.mdAndDown"
          class="d-flex align-center visa-select"
        >
          <span class="text-no-wrap pr-4">Visa</span>
          <VSelect
            v-model="rowPerPage"
            class="custom-select-hover"
            :items="[10, 20, 30, 50]"
          />
        </div>
      </VCardText>

      <!-- SECTION Table -->
      <VTable
        v-if="!$vuetify.display.mdAndDown"
        v-show="roles.length"
        class="px-4 pb-6 text-no-wrap"
      >
        <!-- 👉 Table head -->
        <thead class="text-uppercase">
          <tr>

            <th scope="col">
              Roll
            </th>

            <th scope="col" v-if="$can('view', 'roles') || $can('edit','roles') || $can('delete','roles')"> </th>
          </tr>
        </thead>

        <!-- 👉 Table Body -->
        <tbody v-show="roles.length">
          <tr
            v-for="rol in roles"
            :key="rol.id"
            style="height: 3rem;"
          >

            <!-- 👉 nombre -->
            <td>
              {{ rol.name }}
            </td>

            <!-- 👉 Actions -->
            <td style="width: 3rem;">
              <VMenu>
                <template #activator="{ props }">
                  <VBtn v-bind="props" icon variant="text" class="btn-white">
                    <VIcon icon="custom-dots-vertical" size="22" />
                  </VBtn>
                </template>
                <VList>
                  <VListItem
                      v-if="$can('view', 'roles')"
                      @click="showRoleDetailDialog(rol)">
                    <template #prepend>
                      <VIcon icon="custom-eye" size="24" />
                    </template>
                    <VListItemTitle>Visa</VListItemTitle>
                  </VListItem>
                  <VListItem
                      v-if="$can('edit', 'roles')"
                      @click="showRoleEditDialog(rol)">
                    <template #prepend>
                      <VIcon icon="custom-pencil" size="24" />
                    </template>
                    <VListItemTitle>Redigera</VListItemTitle>
                  </VListItem>
                  <VListItem 
                    v-if="$can('delete','roles')"
                    @click="showRoledDeleteDialog(rol)">
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
        v-if="!isRequestOngoing && hasLoaded && !roles.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-f-supplier"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">  Inga roller har skapats ännu</div>
          <div class="empty-state-text">
            Här visas alla roller som du har skapat.
          </div>
        </div>

        <create 
          @data="fetchData" 
          :type="'link'"
          @alert="showAlert"
        />
      </div>
      <!-- !SECTION -->

      <!-- SECTION Mobile Table -->
      <VExpansionPanels
          class="expansion-panels pb-6 px-6"
          v-if="roles.length && windowWidth < 1024"
        >
          <VExpansionPanel v-for="rol in roles" :key="rol.id">
            <VExpansionPanelTitle
              collapse-icon="custom-chevron-right"
              expand-icon="custom-chevron-down"
            >
              <div class="d-flex align-center gap-x-3">
                <div class="ml-3 d-flex flex-column">
                  {{ rol.name }}
                </div>
              </div>
            </VExpansionPanelTitle>
            
            <VExpansionPanelText>

              <div class="d-flex justify-end flex-wrap gap-4">
                <VBtn 
                  v-if="$can('edit', 'roles')"
                  class="btn-light" 
                  icon 
                  @click="selectedRol = rol; isMobileActionDialogVisible = true"
                >
                  <VIcon icon="custom-dots-vertical" size="24" />
                </VBtn>
              </div>
            </VExpansionPanelText>
          </VExpansionPanel>
      </VExpansionPanels>
      <!-- !SECTION -->

      <!-- SECTION Pagination -->
      <VCardText
        v-if="roles.length"
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
          :total-visible="4"
          :length="totalPages"
          next-icon="custom-chevron-right"
          prev-icon="custom-chevron-left"
        />
      </VCardText>

      <show 
        v-model:isDrawerOpen="isRoleDetailDialog"
        :role="selectedRol"
        :readonly="readonly"
        @close="permissionsRol = []"
        @readonly="readonly = false"/>
      
      <edit 
        v-model:isDrawerOpen="isRoleEditDialog"
        :role="selectedRol"
        @data="fetchData"
        @close="permissionsRol = []"
        @alert="showAlert"
        @readonly="readonly = true"/>

      <destroy 
        v-model:isDrawerOpen="isRoleDeleteDialog"
        :role="selectedRol"
        @data="fetchData"
        @alert="showAlert"/>
    </VCard>

    <!-- 👉 Mobile Action Dialog -->
    <VDialog
      v-model="isMobileActionDialogVisible"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem
              v-if="$can('view', 'roles')"
              @click="showRoleDetailDialog(selectedRol); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-eye" size="24" />
            </template>
            <VListItemTitle>Visa</VListItemTitle>
          </VListItem>
          <VListItem
              v-if="$can('edit', 'roles')"
              @click="showRoleEditDialog(selectedRol); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-pencil" size="24" />
            </template>
            <VListItemTitle>Redigera</VListItemTitle>
          </VListItem>
          <VListItem 
            v-if="$can('delete','roles')"
            @click="showRoledDeleteDialog(selectedRol); isMobileActionDialogVisible = false;">
            <template #prepend>
              <VIcon icon="custom-waste" size="24" />
            </template>
            <VListItemTitle>Ta bort</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>

    <!-- 👉 Export Mobile Dialog -->
    <VDialog
      v-model="exporteraMobile"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
    >
      <VCard>
        <VList>
          <VListItem @click="downloadPDF">
            <VListItemTitle>Exportera PDF</VListItemTitle>
          </VListItem>

          <VListItem @click="downloadCSV">
            <VListItemTitle>Exportera Excel</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss">
  .v-label {
    text-overflow: clip;
  }

  #permisos-lista {
    .n1 strong {
      font-size: 1.7rem;
    }

    .n2 strong {
      font-size: 1.3rem;
    }

    .n3 strong {
      font-size: 1rem;
    }

    .tab {
      margin-block: 4px;
      margin-inline-start: 2rem;
    }
  }

  @media(min-width: 991px){
    #rol-list {
    .rol-list-actions {
      inline-size: 8rem;
    }

    .rol-list-filter {
      inline-size: 12rem;
    }
  }
  }
</style>

<route lang="yaml">
  meta:
    action: view
    subject: roles
</route>
