<script setup>

import { ref, nextTick } from 'vue'
import { useDisplay } from 'vuetify'
import { useMobilePaginationScroll } from '@/@core/composable/useMobilePaginationScroll'
import { useNotesStores } from '@/stores/useNotes'
import { useAuthStores } from '@/stores/useAuth';
import { useConfigsStores } from '@/stores/useConfigs';
import { useAppAbility } from '@/plugins/casl/useAppAbility';
import { excelParser } from '@/plugins/csv/excelParser'
import { themeConfig } from '@themeConfig'
import { formatNumber, formatDateTime } from '@/@core/utils/formatters'
import { PerfectScrollbar } from 'vue3-perfect-scrollbar'
import { emailValidator, requiredValidator, phoneValidator } from '@/@core/utils/validators'
import { buildPdfTopHeader } from '@/@core/utils/pdfHeaderTemplate'
import html2pdf from 'html2pdf.js'
import AddNewNoteDrawer from './AddNewNoteDrawer.vue'
import LoadingOverlay from "@/components/common/LoadingOverlay.vue";
import AddNewNoteMobile from "./AddNewNoteMobile.vue";
import modalWarningIcon from "@/assets/images/icons/alerts/modal-warning-icon.svg";
import ExportDateMenu from '@/components/common/ExportDateMenu.vue'
import PresetAvatarImage from "@/components/common/PresetAvatarImage.vue";

const notesStores = useNotesStores()
const configsStores = useConfigsStores();
const authStores = useAuthStores();
const ability = useAppAbility();
const emitter = inject("emitter")

const notes = ref([])
const searchQuery = ref('')
const rowPerPage = ref(10)
const currentPage = ref(1)
const totalPages = ref(1)
const totalNotes = ref(0)
const isRequestOngoing = ref(true)
const isAddNewNoteDrawerVisible = ref(false)
const isConfirmDeleteDialogVisible = ref(false)
const selectedNote = ref({})

const userData = ref(null)
const role = ref(null)
const suppliers = ref([])
const supplier_id = ref(null)
const comment = ref(null)

const date = ref(null)
const selectedExportType = ref(null)
const isExportTypeMenuVisible = ref(false)
const isExportMenuVisible = ref(false)
const isExportingFile = ref(false)
const lastExportSelectionKey = ref(null)

const advisor = ref({
  type: '',
  message: '',
  show: false
})

const { width: windowWidth } = useWindowSize();
const { mdAndDown } = useDisplay()
const snackbarLocation = computed(() => mdAndDown.value ? "" : "top end")
const sectionEl = ref(null);
const hasLoaded = ref(false);

useMobilePaginationScroll({
  targetRef: sectionEl,
  currentPage,
  isRequestOngoing,
  enabled: mdAndDown,
})

const isDialogOpen = ref(false);
const isNoteFormEdited = ref(false);
const isEdit = ref(false);
const isNoteEditFormEdited = ref(false);
const isConfirmLeaveVisible = ref(false);
const isFilterDialogVisible = ref(false);
const isConfirmUpdateNoteDialogVisible = ref(false);
const isConfirmUpdateNoteMobileDialogVisible = ref(false);
const leaveContext = ref(null); // 'mobile' | 'route' | 'noteEdit' | 'noteEditMobile' | null
const originalNoteData = ref(null);

const COMPANY_STORAGE_KEY = 'clients_company_snapshot';

const exporteraMobile = ref(false)

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

const setCompany = (value) => {
  const normalized = value && typeof value === 'object' ? { ...value } : {};
  company.value = normalized;
  localStorage.setItem(COMPANY_STORAGE_KEY, JSON.stringify(normalized));
};

let nextRoute = null;

// 👉 Computing pagination data
const paginationData = computed(() => {
  const firstIndex = notes.value.length 
    ? (currentPage.value - 1) * rowPerPage.value + 1 
    : 0
  const lastIndex = 
    notes.value.length + (currentPage.value - 1) * rowPerPage.value

  return `${totalNotes.value} resultat`;
  // return `Visar ${ firstIndex } till ${ lastIndex } av ${ totalNotes.value } register`
})

watch(isExportMenuVisible, isVisible => {
  if (isVisible)
    lastExportSelectionKey.value = null
})

// 👉 watching current page
watchEffect(() => {
  if (currentPage.value > totalPages.value)
    currentPage.value = totalPages.value

    if (!isAddNewNoteDrawerVisible.value)
        selectedNote.value = {}
})

watchEffect(fetchData)

async function fetchData(cleanFilters = false) {

  if(cleanFilters === true) {
    searchQuery.value = ''
    rowPerPage.value = 10
    currentPage.value = 1
    supplier_id.value = null
  }

  let data = {
    search: searchQuery.value,
    orderByField: 'created_at',
    orderBy: 'desc',
    limit: rowPerPage.value,
    page: currentPage.value,
    supplier_id: supplier_id.value
  }

  isRequestOngoing.value = searchQuery.value !== '' ? false : true
  isFilterDialogVisible.value = false;

  await notesStores.fetchNotes(data)

  notes.value = notesStores.getNotes
  totalPages.value = notesStores.last_page
  totalNotes.value = notesStores.notesTotalCount

  userData.value = JSON.parse(localStorage.getItem('user_data') || 'null')
  role.value = userData.value?.roles?.[0]?.name ?? null

  if(role.value === 'SuperAdmin' || role.value === 'Administrator') {
    suppliers.value = notesStores.getSuppliers
  }

  hasLoaded.value = true;
  isRequestOngoing.value = false
}

watchEffect(registerEvents)

// Guard route changes if client creation form has unsaved changes
onBeforeRouteLeave((to, from, next) => {
  const hasUnsavedNewNote = isNoteFormEdited.value && (isAddNewNoteDrawerVisible.value || isDialogOpen.value);
  const hasUnsavedEditNote = checkNoteFormChanges() && (isConfirmUpdateNoteDialogVisible.value || isConfirmUpdateNoteMobileDialogVisible.value);
  
  if (hasUnsavedNewNote || hasUnsavedEditNote) {
    isConfirmLeaveVisible.value = true;
    nextRoute = next;
    leaveContext.value = 'route';
  } else {
    next();
  }
});

function registerEvents() {
    emitter.on('cleanFilters', fetchData)
}

const confirmLeave = () => {
  isConfirmLeaveVisible.value = false;
  if (leaveContext.value === 'route' && nextRoute) {
    isAddNewNoteDrawerVisible.value = false;
    isDialogOpen.value = false;
    isNoteFormEdited.value = false;
    isConfirmUpdateNoteDialogVisible.value = false;
    isConfirmUpdateNoteMobileDialogVisible.value = false;
    isNoteEditFormEdited.value = false;
    originalNoteData.value = null;
    const go = nextRoute;
    nextRoute = null;
    leaveContext.value = null;
    go();
    return;
  }
  if (leaveContext.value === 'mobile') {
    isDialogOpen.value = false;
    isNoteFormEdited.value = false;
  }
  if (leaveContext.value === 'noteEdit') {
    isConfirmUpdateNoteDialogVisible.value = false;
    isNoteEditFormEdited.value = false;
    originalNoteData.value = null;
    selectedNote.value = {};
  }
  if (leaveContext.value === 'noteEditMobile') {
    isConfirmUpdateNoteMobileDialogVisible.value = false;
    isNoteEditFormEdited.value = false;
    originalNoteData.value = null;
    selectedNote.value = {};
  }
  leaveContext.value = null;
};

const cancelLeave = () => {
  isConfirmLeaveVisible.value = false;
  if (leaveContext.value === 'route' && nextRoute) {
    nextRoute(false);
    nextRoute = null;
  }
  // Para noteEdit y noteEditMobile, simplemente cerramos el diálogo de confirmación
  // y el usuario permanece en el formulario de edición
  leaveContext.value = null;
};

const showNote = (noteData, isMobile = false, is_edit = false) => {
    isEdit.value = is_edit;
    selectedNote.value = { ...noteData }

    // Guardar copia original para detectar cambios
    originalNoteData.value = JSON.stringify({
        reg_num: noteData.reg_num,
        note: noteData.note,
        name: noteData.name,
        phone: noteData.phone,
        email: noteData.email,
        comment: noteData.comment
    })
    isNoteEditFormEdited.value = false
    
    if (isMobile) {
        isConfirmUpdateNoteMobileDialogVisible.value = true
    } else {
        isConfirmUpdateNoteDialogVisible.value = true
    }
}

// Detectar cambios en el formulario de edición
const checkNoteFormChanges = () => {
    if (!originalNoteData.value) return false
    const currentData = JSON.stringify({
        reg_num: selectedNote.value.reg_num,
        note: selectedNote.value.note,
        name: selectedNote.value.name,
        phone: selectedNote.value.phone,
        email: selectedNote.value.email,
        comment: selectedNote.value.comment
    })
    return currentData !== originalNoteData.value
}

const closeNote = (forceClose = false) => {
    // Verificar si hay cambios sin guardar
    if (!forceClose && checkNoteFormChanges()) {
        isConfirmLeaveVisible.value = true
        leaveContext.value = isConfirmUpdateNoteMobileDialogVisible.value ? 'noteEditMobile' : 'noteEdit'
        return
    }
    
    isConfirmUpdateNoteDialogVisible.value = false
    isConfirmUpdateNoteMobileDialogVisible.value = false
    selectedNote.value = {}
    originalNoteData.value = null
    isNoteEditFormEdited.value = false
}

const showDeleteDialog = noteData => {
  isConfirmDeleteDialogVisible.value = true
  selectedNote.value = { ...noteData }
}

const removeNote = async () => {
  isConfirmDeleteDialogVisible.value = false
  let res = await notesStores.deleteNote(selectedNote.value.id)
  selectedNote.value = {}

  advisor.value = {
    type: res.data.success ? 'success' : 'error',
    message: res.data.success ? 'Värdering raderad!' : res.data.message,
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

const formatCommentDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    const months = ['jan', 'feb', 'mars', 'apr', 'maj', 'juni', 'juli', 'aug', 'sept', 'okt', 'nov', 'dec']
    const day = date.getDate()
    const month = months[date.getMonth()]
    const year = date.getFullYear()
    const hours = String(date.getHours()).padStart(2, '0')
    const minutes = String(date.getMinutes()).padStart(2, '0')
    return `${day} ${month} ${year}, ${hours}:${minutes}`
}

const sendComment = async () => {

    if(comment.value !== null && comment.value !== '') {
        isRequestOngoing.value = true
        
        const noteId = selectedNote.value.id
        
        await notesStores.sendComment({ id: noteId, comment: comment.value})
        
        // Refrescar datos desde el servidor
        await fetchData()
        
        await nextTick()
        
        // Actualizar selectedNote con los datos frescos
        const updatedNote = notes.value.find(item => item.id === noteId)
        if (updatedNote) {
            selectedNote.value = {
                ...updatedNote
            }
        }

        comment.value = null

        advisor.value = {
            type: 'success',
            message: 'Kommentar skapad!',
            show: true
        }

        setTimeout(() => {
            advisor.value = {
                type: '',
                message: '',
                show: false
            }
        }, 3000)

        return true
    }
}

const editComment = async (commentData) => {
    if(commentData.comment !== null && commentData.comment !== '') {
        isRequestOngoing.value = true
        
        const noteId = selectedNote.value.id
        
        await notesStores.updateComment({ 
            note_id: noteId, 
            comment_id: commentData.id, 
            comment: commentData.comment
        })
        
        // Refrescar datos desde el servidor
        await fetchData()
        
        await nextTick()
        
        // Actualizar selectedNote con los datos frescos
        const updatedNote = notes.value.find(item => item.id === noteId)
        if (updatedNote) {
            selectedNote.value = {
                ...updatedNote
            }
        }

        advisor.value = {
            type: 'success',
            message: 'Kommentar uppdaterad!',
            show: true
        }

        setTimeout(() => {
            advisor.value = {
                type: '',
                message: '',
                show: false
            }
        }, 3000)

        return true
    }
}

const deleteComment = async (commentData) => {
    
    isRequestOngoing.value = true
    
    const noteId = selectedNote.value.id
    
    await notesStores.deleteComment({ 
        note_id: noteId, 
        comment_id: commentData.id
    })
    
    // Refrescar datos desde el servidor
    await fetchData()
    
    await nextTick()
    
    // Actualizar selectedNote con los datos frescos
    const updatedNote = notes.value.find(item => item.id === noteId)
    if (updatedNote) {
        selectedNote.value = {
            ...updatedNote
        }
    }

    advisor.value = {
        type: 'success',
        message: 'Kommentar borttagen!',
        show: true
    }

    setTimeout(() => {
        advisor.value = {
            type: '',
            message: '',
            show: false
        }
    }, 3000)

    return true

}

const submitForm = async (note, method) => {
  isRequestOngoing.value = true

  if (method === 'update') {
    note.data.append('_method', 'PUT')
    submitUpdate(note)
    return
  }

  submitCreate(note.data)
}

const submitFormFromDrawer = async () => {
  isRequestOngoing.value = true

  const formData = new FormData()
  formData.append('_method', 'PUT')
  formData.append('reg_num', selectedNote.value.reg_num)
  formData.append('note', selectedNote.value.note)
  formData.append('name', selectedNote.value.name)
  formData.append('phone', selectedNote.value.phone)
  formData.append('email', selectedNote.value.email)
  formData.append('comment', selectedNote.value.comment)

  submitUpdate({ id: selectedNote.value.id, data: formData })
}

const submitCreate = noteData => {

    notesStores.addNote(noteData)
        .then((res) => {
            if (res.data.success) {
                advisor.value = {
                    type: 'success',
                    message: 'Kunden har lagts till! ',
                    show: true
                }
                fetchData()
            }
            isRequestOngoing.value = false
        })
        .catch((err) => {
            advisor.value = {
                type: 'error',
                message: err.message,
                show: true
            }
            isRequestOngoing.value = false
        })

    setTimeout(() => {
        advisor.value = {
            type: '',
            message: '',
            show: false
        }
    }, 3000)
}

const submitUpdate = noteData => {

    notesStores.updateNote(noteData)
        .then((res) => {
            if (res.data.success) {
                    advisor.value = {
                    type: 'success',
                    message: 'Värdering uppdaterad!',
                    show: true
                }
                fetchData()
            }

            isConfirmUpdateNoteDialogVisible.value = false
            isConfirmUpdateNoteMobileDialogVisible.value = false
            isRequestOngoing.value = false
        })
        .catch((err) => {
            advisor.value = {
                type: 'error',
                message: err.message,
                show: true
            }
            isRequestOngoing.value = false
        })

    setTimeout(() => {
        advisor.value = {
            type: '',
            message: '',
            show: false
        }
    }, 3000)
}

const downloadCSV = async () => {

  isRequestOngoing.value = true
  exporteraMobile.value = false

  try {

    const data = {
      limit: -1,
      ...getDateRangePayload(),
      orderByField: 'id',
      orderBy: "desc",
    };

    await notesStores.fetchNotes(data)

    const includeSupplierColumn = role.value === 'SuperAdmin' || role.value === 'Administrator'

    const dataArray = notesStores.getNotes.map(element => {

      const row = {
        Reg_nr: element.reg_num,
        Egen_värdering: element.note ?? '',
        Kundnamn: element.name ?? '',
        Tel_nr: element.phone ?? '',
        E_post: element.email ?? '',
        Kommentar: element.comment ?? ''
      }
      
      if (includeSupplierColumn) {
        row.Leverantör = element.supplier
          ? `${element.supplier.user.name} ${element.supplier.user.last_name ?? ''}`.trim()
          : ''
      }

      return row
   })

  excelParser().exportDataFromJSON(dataArray, "notes", "csv");

  } finally {
    isRequestOngoing.value = false
  }

};

const toYmd = value => {
  if (!value)
    return null

  if (value instanceof Date && !Number.isNaN(value.getTime())) {
    const year = value.getFullYear()
    const month = `${value.getMonth() + 1}`.padStart(2, '0')
    const day = `${value.getDate()}`.padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  if (typeof value === 'string') {
    const normalized = value.trim()
    const ymdMatch = normalized.match(/^\d{4}-\d{2}-\d{2}/)
    if (ymdMatch)
      return ymdMatch[0]

    const parsed = new Date(normalized)
    if (!Number.isNaN(parsed.getTime())) {
      const year = parsed.getFullYear()
      const month = `${parsed.getMonth() + 1}`.padStart(2, '0')
      const day = `${parsed.getDate()}`.padStart(2, '0')
      return `${year}-${month}-${day}`
    }
  }

  return null
}

const getDateRangePayload = () => {
  if (!date.value)
    return {}

  if (Array.isArray(date.value)) {
    const from = toYmd(date.value[0])
    const to = toYmd(date.value[1] ?? date.value[0])
    if (from && to)
      return { date_from: from, date_to: to }
  }

  if (typeof date.value === 'string') {
    const splitByRange = date.value.split(/\s+to\s+|\s+till\s+|\s+a\s+/i)
    if (splitByRange.length >= 2) {
      const from = toYmd(splitByRange[0])
      const to = toYmd(splitByRange[1])
      if (from && to)
        return { date_from: from, date_to: to }
    }

    const single = toYmd(date.value)
    if (single)
      return { date_from: single, date_to: single }
  }

  if (date.value instanceof Date) {
    const single = toYmd(date.value)
    if (single)
      return { date_from: single, date_to: single }
  }

  return {}
}

const downloadPDF = async () => {
  isRequestOngoing.value = true
  exporteraMobile.value = false
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
      limit: -1,
      ...getDateRangePayload(),
      orderByField: 'id',
      orderBy: 'desc'
    }

    await notesStores.fetchNotes(data)

    if (document.fonts?.load) {
      await Promise.all([
        document.fonts.load(`400 12px ${pdfFontFamily}`),
        document.fonts.load(`600 32px ${pdfFontFamily}`),
      ])
    }

    const includeSupplierColumn = role.value === 'SuperAdmin' || role.value === 'Administrator'
    const columnWidth = includeSupplierColumn ? '14.2%' : '16.6%'

    const rows = notesStores.getNotes.map(element => ({
      reg_num: element.reg_num,
      name: element.name ?? '',
      supplier: element.supplier
        ? `${element.supplier.user.name} ${element.supplier.user.last_name ?? ''}`.trim()
        : '',
      phone: element.phone,
      email: element.email,
      note: `${formatNumber(element.note ?? 0)} kr`,
      comment: element.comment ?? ''
    }))

    const { headerMarkup } = await buildPdfTopHeader({
      company: company.value,
      title: 'Mina värderingar',
      themeConfig,
      escapeHtml,
      showCompanyDetailsWhenLogo: true,
    })

    const rowsMarkup = rows.map(item => `
      <tr style="height: 48px;">
        <td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.reg_num)}</td>
        <td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.note)}</td>
        ${includeSupplierColumn ? `<td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.supplier)}</td>` : ''}
        <td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.name)}</td>        
        <td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.phone)}</td>
        <td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.email)}</td>
        <td style="width: ${columnWidth}; padding: 0 8px; border-bottom: 1px solid #E7E7E7; text-align: center; vertical-align: middle;">${escapeHtml(item.comment)}</td>
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
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 8px; border-top-left-radius: 32px; border-bottom-left-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Reg nr</td>
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Egen värdering</td>
                      ${includeSupplierColumn ? `<td style="text-align: center; width: ${columnWidth}; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Leverantör</td>` : ''}
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Kundnamn</td>
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Tel nr</td>
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 8px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">E-post</td>
                      <td style="text-align: center; width: ${columnWidth}; padding: 0 8px; border-top-right-radius: 32px; border-bottom-right-radius: 32px; background-color: #F6F6F6; font-weight: 400; vertical-align: middle;">Kommentar</td>
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
        filename: 'notes.pdf',
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

const exportPDFAndCloseMenu = async () => {
  if (isExportingFile.value)
    return

  if (!selectedExportType.value)
    return

  isExportingFile.value = true
  try {
    if (selectedExportType.value === 'excel') {
      await downloadCSV()
    } else {
      await downloadPDF()
    }

    isExportMenuVisible.value = false
  } finally {
    selectedExportType.value = null
    isExportingFile.value = false
  }
}

const openExportDateMenu = type => {
  exporteraMobile.value = false
  selectedExportType.value = type
  isExportTypeMenuVisible.value = false

  nextTick(() => {
    isExportMenuVisible.value = true
  })
}

const buildRangeSelectionKey = value => {
  if (!value)
    return null

  const normalize = item => {
    if (!item)
      return ''

    if (item instanceof Date && !Number.isNaN(item.getTime())) {
      const year = item.getFullYear()
      const month = `${item.getMonth() + 1}`.padStart(2, '0')
      const day = `${item.getDate()}`.padStart(2, '0')

      return `${year}-${month}-${day}`
    }

    if (typeof item === 'string')
      return item.trim()

    return String(item)
  }

  if (Array.isArray(value)) {
    const first = normalize(value[0])
    const second = normalize(value[1] ?? value[0])

    return `${first}__${second}`
  }

  if (typeof value === 'string') {
    const chunks = value.split(/\s+to\s+|\s+till\s+|\s+a\s+/i).map(item => item.trim()).filter(Boolean)
    if (chunks.length >= 2)
      return `${chunks[0]}__${chunks[1]}`

    const single = normalize(value)
    return `${single}__${single}`
  }

  const single = normalize(value)
  return `${single}__${single}`
}

const isCompleteRangeSelection = value => {
  if (!value)
    return false

  if (Array.isArray(value))
    return value.length >= 2 && !!value[0] && !!value[1]

  if (typeof value === 'string') {
    const chunks = value.split(/\s+to\s+|\s+till\s+|\s+a\s+/i)
    return chunks.length >= 2 && !!chunks[0]?.trim() && !!chunks[1]?.trim()
  }

  return false
}

const onDatePickerUpdate = value => {
  if (!selectedExportType.value)
    return

  if (!isCompleteRangeSelection(value))
    return

  const selectionKey = buildRangeSelectionKey(value)
  if (!selectionKey || selectionKey === lastExportSelectionKey.value)
    return

  lastExportSelectionKey.value = selectionKey

  if (!isExportingFile.value)
    exportPDFAndCloseMenu()
}

// Intercept mobile dialog outside-click close
const handleMobileDialogUpdate = (val) => {
  if (val === false && isNoteFormEdited.value) {
    // keep dialog open and show confirm
    isDialogOpen.value = true;
    isConfirmLeaveVisible.value = true;
    leaveContext.value = 'mobile';
    return;
  }
  isDialogOpen.value = val;
};

const onNoteFormEdited = (val) => {
  isNoteFormEdited.value = !!val;
};

const openAddNewNoteDrawerMobile = () => {
  isDialogOpen.value = true;
  selectedNote.value = {};
};

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value;
  if (!el) return;

  const rect = el.getBoundingClientRect();
  const remaining = Math.max(0, window.innerHeight - rect.top - 25);
  el.style.minHeight = `${remaining}px`;
}


onMounted(async () => {
  try {
    resizeSectionToRemainingViewport();
    window.addEventListener("resize", resizeSectionToRemainingViewport);
    
    userData.value = JSON.parse(localStorage.getItem("user_data") || "null");
    role.value = userData.value.roles[0].name;

    if (!role.value) return;

    if (role.value === "SuperAdmin" || role.value === "Administrator") {
      suppliers.value = notesStores.getSuppliers;
    }

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
      });
    } else if (role.value === 'User') {
      setCompany({
        ...(user_data?.supplier?.boss?.user?.user_detail ?? {}),
        email: user_data?.supplier?.boss?.user?.email ?? '',
        name: user_data?.supplier?.boss?.user?.name ?? '',
        last_name: user_data?.supplier?.boss?.user?.last_name ?? '',
      });
    } else {
      await configsStores.getFeature('company')
      await configsStores.getFeature('logo')

      const companyConfig = configsStores.getFeaturedConfig('company') ?? {};
      const logoConfig = configsStores.getFeaturedConfig('logo') ?? {};

      setCompany({
        ...companyConfig,
        logo: logoConfig.logo ?? companyConfig.logo ?? '',
      });
    }

  } catch (error) {
    console.error('Failed to load company data:', error);
  }
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", resizeSectionToRemainingViewport);
});
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
          <h2>Mina värderingar <span v-if="hasLoaded">({{ totalNotes }})</span></h2>
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-flex'"/>

        <div class="d-flex gap-4">
          <VMenu 
            v-if="windowWidth >= 1024"
            v-model="isExportTypeMenuVisible">
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
              <VListItem @click="openExportDateMenu('pdf')">
                <VListItemTitle>Exportera PDF</VListItemTitle>
              </VListItem>
              <VListItem @click="openExportDateMenu('excel')">
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

          <ExportDateMenu
            v-model="date"
            v-model:menuVisible="isExportMenuVisible"
            :show-activator="false"
            :is-mobile="windowWidth < 1024"
            activator="#payout-export-button"
            @update:modelValue="onDatePickerUpdate"
          />

          <VBtn
            v-if="$can('create', 'notes') && windowWidth >= 1024"
            class="btn-gradient"
            block
            @click="isAddNewNoteDrawerVisible = true">
              <VIcon icon="custom-plus" size="24" />
              Ny värdering
          </VBtn>
          <VBtn
            v-if="windowWidth < 1024 && $can('create', 'notes')"
            class="btn-gradient"
            block
            @click="openAddNewNoteDrawerMobile"
          >
            <VIcon icon="custom-plus" size="24" />
            Ny värdering
          </VBtn>
        </div>
      </VCardTitle>
        
      <VDivider :class="$vuetify.display.mdAndDown ? 'm-0' : 'mt-2 mx-4'" />

      <VCardText
        class="d-flex align-center justify-space-between gap-1 pb-0"
        :class="$vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'"
      >
        <!-- 👉 Search  -->
        <div class="search">
          <VTextField v-model="searchQuery" placeholder="Sök" clearable />
        </div>

        <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

        <VBtn 
          class="btn-white-2 px-3" 
          v-if="role !== 'Supplier' && role !== 'User'"
          @click="isFilterDialogVisible = true"
        >
          <VIcon icon="custom-filter" size="24" />
          <span :class="windowWidth < 1024 ? 'd-none' : 'd-flex'">Filtrera efter</span>
        </VBtn>

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

      <div 
        v-show="notes.length"
        class="d-flex flex-wrap gap-4"
        :class="$vuetify.display.mdAndDown ? 'pa-6' : 'pa-4'">
        <VCard
            v-for="(note, index) in notes"
            :key="note.id"
            flat
            :style="windowWidth < 1024 ? 'width: 100%;' : 'width: calc(33.333% - 11px);'"
            class="border-card-comment py-2 px-4 readonly-form d-flex flex-column"
        >
            <VCardText 
                class="d-flex align-center px-0 border-comments" 
                style="min-height: 48px; max-height: 48px;"
                > 
                <div class="d-flex flex-column gap-1">
                  <span class="title-comments" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1;">
                    {{ note.reg_num }}
                  </span>
                  <span class="subtitle-comments">
                    {{ note.name }} - {{ note.phone }}
                  </span>
                </div>
                <VSpacer />
                <VIcon 
                    icon="custom-waste" 
                    size="24" 
                    class="cursor-pointer"
                    style="flex-shrink: 0;"
                    @click="showDeleteDialog(note)"
                />
            </VCardText>

            <div class="d-block gap-4 px-0 mt-auto">
                <div class="text-comments mt-4" v-if="note.comment">
                    {{ note.comment }}
                </div>

                <div 
                  class="d-flex gap-2 mt-10 mb-4"
                  :class="windowWidth < 1024 ? 'flex-column' : 'flex-row'">
                  <div :class="windowWidth < 1024 ? 'w-100' : 'w-50'">
                    <div class="text-comments">
                      Datum
                    </div>
                    <div class="note-value-field mt-4">
                      {{ formatDateTime(note.created_at) }} 
                    </div>  
                  </div>
                  <div :class="windowWidth < 1024 ? 'w-100' : 'w-50'">
                    <div class="text-comments">
                      Egen värdering
                    </div>
                    <div class="note-value-field mt-4">
                      {{ formatNumber(note.note ?? 0) }} (kr)
                    </div> 
                  </div>
                </div>
                
                <div class="d-flex align-center px-0">
                    <div class="text-no-wrap">
                        <VAvatar
                            variant="outlined"
                            size="40"
                        >
                            <VImg
                                v-if="note.user.avatar"
                                style="border-radius: 50%;"
                                :src="themeConfig.settings.urlStorage + note.user.avatar"
                            />
                            <PresetAvatarImage
                              v-else
                              :avatar-id="note.user?.user_detail?.avatar_id"
                            />
                        </VAvatar>
                        <span class="ms-2 text-comments text-neutral-3">{{ note.user.name }} {{ note.user.last_name }}</span>
                    </div>

                    <VSpacer />

                    <div class="d-flex align-center">
                      <VIcon 
                        icon="custom-pencil" 
                        size="24" 
                        class="cursor-pointer me-2"
                        @click="showNote(note, windowWidth < 1024 ? true : false, true)"
                      />
                      <VIcon 
                        icon="custom-comments" 
                        size="24" 
                        class="cursor-pointer"
                        @click="showNote(note, windowWidth < 1024 ? true : false, false)"
                      />
                      <span class="ms-2 text-comments text-neutral-3">
                        {{ note.comments?.length ?? 0 }}
                      </span>
                    </div>
                </div>
            </div>    
        </VCard>
      </div>

      <div
        v-if="!isRequestOngoing && hasLoaded && !notes.length"
        class="empty-state"
        :class="$vuetify.display.mdAndDown ? 'px-6 py-0' : 'pa-4'"
      >
        <VIcon
          :size="$vuetify.display.mdAndDown ? 80 : 120"
          icon="custom-f-notes"
        />
        <div class="empty-state-content">
          <div class="empty-state-title">Inga värderingar sparade</div>
          <div class="empty-state-text">
            Använd det här utrymmet för att spara detaljer från förhandlingar och värderingar. 
            Glöm aldrig en överenskommelse igen.
          </div>
        </div>
        <VBtn
          class="btn-ghost"
          v-if="$can('create', 'notes') && !$vuetify.display.mdAndDown"
          @click="isAddNewNoteDrawerVisible = true"
        >
          Skapa ny anteckning
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>

        <VBtn
          class="btn-ghost"
          v-if="$vuetify.display.mdAndDown && $can('create', 'notes')"
          @click="isDialogOpen = true"
        >
          Skapa ny anteckning
          <VIcon icon="custom-arrow-right" size="24" />
        </VBtn>
      </div>

      <VCardText
        v-if="notes.length"
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
    </VCard>
     
 
    <!-- 👉 Add New Note -->
    <AddNewNoteDrawer
      v-model:isDrawerOpen="isAddNewNoteDrawerVisible"
      :note="selectedNote"
      @note-data="submitForm"
      @edited="onNoteFormEdited"/>

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
            Radera värdering?
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Är du säker på att du vill radera denna värdering? 
          All information i anteckningen kommer att försvinna permanent.
        </VCardText>

        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="isConfirmDeleteDialogVisible = false">
              Avbryt
          </VBtn>
          <VBtn class="btn-gradient" @click="removeNote">
              Ja, radera
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
            :item-value="(item) => item.id"
            autocomplete="off"
            clearable
            clear-icon="tabler-x"
            class="selector-user"
            :menu-props="{ maxHeight: '400px' }"
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

    <!-- 👉 Add New Note Mobile Dialog -->
    <VDialog
      v-model="isDialogOpen"
      transition="dialog-bottom-transition"
      content-class="dialog-bottom-full-width"
      @update:model-value="handleMobileDialogUpdate"
    >
      <VCard>
        <AddNewNoteMobile
          v-model:isDrawerOpen="isDialogOpen"
          :note="selectedNote"
          @note-data="submitForm"
          @edited="onNoteFormEdited"
        />
      </VCard>
    </VDialog>

    <!-- 👉 View/Update Note Dialog (Desktop) -->
    <VNavigationDrawer
      temporary
      :width="550"
      location="end"
      class="scrollable-content right-drawer rounded-left-4"
      :model-value="isConfirmUpdateNoteDialogVisible"
      @update:model-value="(val) => !val && closeNote()"
    >

      <div class="d-flex align-center pa-6 pb-1">
        <h6 class="title-modal font-blauer">
          {{ isEdit ? 'Uppdatera värdering' : 'Kommentera värdering' }}
        </h6>

        <VSpacer />

        <!-- Dialog close btn -->
        <VBtn
          icon
          class="btn-white"
          @click="closeNote()"
        >
          <VIcon size="32" icon="custom-cancel" />
        </VBtn>
      </div>

      <VDivider class="mt-4" />

      <PerfectScrollbar :options="{ wheelPropagation: false }" class="scrollbar-no-border">
        <VCard flat class="card-form note-index">
          <VCardText>
            <!-- 👉 Form -->
            <VForm
              ref="refForm"
              @submit.prevent="submitFormFromDrawer"
            >
              <VRow>
                <VCol cols="12" md="12" class="pb-0">
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Reg nr*" />
                    <VTextField
                      v-model="selectedNote.reg_num"
                      :rules="[requiredValidator]"
                      :readonly="!isEdit"
                      @input="selectedNote.reg_num = selectedNote.reg_num.toUpperCase()"
                    />
                </VCol>
                <VCol cols="12" md="12" class="pb-0">
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Egen värdering*" />
                    <VTextField
                      v-model="selectedNote.note"
                      type="number"
                      min="0"
                      suffix="KR"
                      :rules="[requiredValidator]"
                      :readonly="!isEdit"
                    />
                </VCol>
                <VCol cols="12" md="12" class="pb-0">
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kundnamn" />
                    <VTextField
                      v-model="selectedNote.name"
                      :readonly="!isEdit"
                    />
                </VCol>
                <VCol cols="12" md="12" class="pb-0">
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Tel nr" />
                    <VTextField
                      v-model="selectedNote.phone"
                      :rules="[phoneValidator]"
                      :readonly="!isEdit"
                    />
                </VCol>
                <VCol cols="12" md="12" class="pb-0">
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post" />
                    <VTextField
                      v-model="selectedNote.email"
                      :rules="[emailValidator]"
                      :readonly="!isEdit"
                    />
                </VCol>
                <VCol cols="12" md="12">
                    <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kommentar" />
                    <VTextarea
                      v-model="selectedNote.comment"
                      rows="3"
                      :readonly="!isEdit"
                    />
                </VCol>
                <!-- 👉 Submit and Cancel -->
                <VCol cols="12" :class="isEdit ? '' : 'd-none'">
                  <VBtn
                    class="btn-light me-3"
                    @click="closeNote()"
                  >
                    Avbryt
                  </VBtn>
                  <VBtn
                    type="submit"
                    class="btn-gradient"
                  >
                    Uppdatering
                  </VBtn>
                </VCol>
              </VRow>

              <VDivider 
                :class="[
                  windowWidth < 1024 ? 'my-4' : 'my-6',
                  isEdit ? 'd-none' : ''
                ]" 
              />

              <div class="mb-6" :class="isEdit ? 'd-none' : 'd-flex gap-2'">
                  <VIcon size="24" icon="custom-comments-2" class="action-icon" />
                  <span class="span-comments">
                      Kommentarer
                  </span>
              </div>

              <div :class="isEdit ? 'd-none' : 'd-flex flex-column gap-6'">
                <VTextField
                    v-model="comment"
                    placeholder="Skriv en kommentar"
                />
                <VBtn class="btn-light w-auto align-self-start" @click="sendComment">
                    Kommentar
                </VBtn>
              </div>

              <VDivider v-if="selectedNote.comments?.length > 0" :class="[
                windowWidth < 1024 ? 'my-4' : 'my-6',
                isEdit ? 'd-none' : ''
              ]" />

              <div 
                v-for="(comment, index) in selectedNote.comments" 
                :key="index"
                class="mb-4"
                :class="isEdit ? 'd-none' : 'd-flex flex-column gap-2 justify-center'"
              >                
                  <div class="text-no-wrap w-100">
                      <VAvatar
                          variant="outlined"
                          size="40"
                      >
                          <VImg
                              v-if="comment.user.avatar"
                              style="border-radius: 50%;"
                              :src="themeConfig.settings.urlStorage + comment.user.avatar"
                          />
                          <PresetAvatarImage
                            v-else
                            :avatar-id="comment.user?.user_detail?.avatar_id"
                          />
                      </VAvatar>
                      <span class="ms-2 user-comments">
                          {{ comment.user.name }} {{ comment.user.last_name }}

                          <span class="date-comments">  
                              {{ formatCommentDate(comment.created_at) }}
                          </span>
                      </span>
                      
                  </div>
                  <VTextField
                      v-model="comment.comment"
                      placeholder="Kommentar.."
                  />
                  <div class="d-flex gap-4">
                      <span class="link-comments cursor-pointer" @click="editComment(comment)">Redigera</span>
                      <span class="link-comments cursor-pointer" @click="deleteComment(comment)">Eliminera</span>
                  </div>
              </div>
            </VForm>
          </VCardText>
        </VCard>
      </PerfectScrollbar>
     
    </VNavigationDrawer>

    <!-- 👉 View/Update Note Dialog (Mobile) -->
    <VDialog
      v-model="isConfirmUpdateNoteMobileDialogVisible"
      fullscreen
      persistent
      :scrim="false"
      transition="dialog-bottom-transition"
      class="action-dialog dialog-fullscreen" >

      <VBtn
        icon
        class="btn-white close-btn"
        @click="closeNote()"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>
      <VForm
        ref="refUpdate"
        class="h-100 d-flex flex-column card-form note-index"
        @submit.prevent="submitFormFromDrawer">
        <VCard flat class="card-drawer-form h-100 d-flex flex-column">
            <VCardText class="dialog-title-box mb-2 pb-0 flex-0">
                <div class="dialog-title">
                  {{ isEdit ? 'Uppdatera värdering' : 'Kommentera värdering' }}
                </div>
            </VCardText>
            <VCardText class="pt-4 flex-grow-1" style="overflow-y: auto; overflow-x: hidden;">
                <VRow>
                    <VCol cols="12" md="12" class="pb-0">
                      <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Reg nr*" />
                      <VTextField
                        v-model="selectedNote.reg_num"
                        :rules="[requiredValidator]"
                        :readonly="!isEdit"
                        @input="selectedNote.reg_num = selectedNote.reg_num.toUpperCase()"
                      />
                    </VCol>
                    <VCol cols="12" md="12" class="pb-0">
                      <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Egen värdering*" />
                      <VTextField
                        v-model="selectedNote.note"
                        type="number"
                        min="0"
                        suffix="KR"
                        :rules="[requiredValidator]"
                        :readonly="!isEdit"
                      />
                    </VCol>
                    <VCol cols="12" md="12" class="pb-0">
                      <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kundnamn" />
                      <VTextField
                          v-model="selectedNote.name"
                          :readonly="!isEdit"
                      />
                    </VCol>
                    <VCol cols="12" md="12" class="pb-0">
                      <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Tel nr" />
                      <VTextField
                        v-model="selectedNote.phone"
                        :rules="[phoneValidator]"
                        :readonly="!isEdit"
                      />
                    </VCol>
                    <VCol cols="12" md="12" class="pb-0">
                      <VLabel class="mb-1 text-body-2 text-high-emphasis" text="E-post" />
                      <VTextField
                        v-model="selectedNote.email"
                        :rules="[emailValidator]"
                        :readonly="!isEdit"
                      />
                    </VCol>
                    <VCol cols="12" md="12">
                      <VLabel class="mb-1 text-body-2 text-high-emphasis" text="Kommentar" />
                      <VTextarea
                        v-model="selectedNote.comment"
                        rows="3"
                        :readonly="!isEdit"
                      />
                    </VCol>
                </VRow>
                
                <div :class="isEdit ? 'd-flex justify-end gap-3 flex-wrap dialog-actions px-0 pb-2' : 'd-none'">
                    <VBtn
                        class="btn-light"
                        @click="closeNote()">
                        Avbryt
                    </VBtn>
                    <VBtn class="btn-gradient" type="submit">
                        Uppdatering
                    </VBtn>
                </div>

                <VDivider 
                  :class="[
                    windowWidth < 1024 ? 'mt-6 mb-4' : 'my-6',
                    isEdit ? 'd-none' : ''
                  ]" 
                />

                <div class="mb-6" :class="isEdit ? 'd-none' : 'd-flex gap-2'">
                    <VIcon size="24" icon="custom-comments-2" class="action-icon" />
                    <span class="span-comments">
                        Kommentarer
                    </span>
                </div>

                <div :class="isEdit ? 'd-none' : 'd-flex flex-column gap-6'">
                    <VTextField
                        v-model="comment"
                        placeholder="Skriv en kommentar"
                    />
                    <VBtn class="btn-light w-auto align-self-start" @click="sendComment">
                        Kommentar
                    </VBtn>
                </div>

                <VDivider 
                  v-if="selectedNote.comments?.length > 0" 
                  :class="[
                    windowWidth < 1024 ? 'my-4' : 'my-6', 
                    isEdit ? 'd-none' : ''
                  ]"
                />

                <div 
                    v-for="(comment, index) in selectedNote.comments" 
                    :key="index"
                    class="mb-4"
                    :class="isEdit ? 'd-none' : 'd-flex flex-column gap-2 justify-center'"
                >
                    <div class="text-no-wrap w-100">
                        <VAvatar
                            variant="outlined"
                            size="40"
                        >
                            <VImg
                                v-if="comment.user.avatar"
                                style="border-radius: 50%;"
                                :src="themeConfig.settings.urlStorage + comment.user.avatar"
                            />
                            <PresetAvatarImage
                              v-else
                              :avatar-id="comment.user?.user_detail?.avatar_id"
                            />
                        </VAvatar>
                        <span class="ms-2 user-comments">
                            {{ comment.user.name }} {{ comment.user.last_name }}

                            <span class="date-comments">  
                                {{ formatCommentDate(comment.created_at) }}
                            </span>
                        </span>
                        
                    </div>
                    <VTextField
                        v-model="comment.comment"
                        placeholder="Kommentar.."
                    />
                    <div class="d-flex gap-4">
                        <span class="link-comments cursor-pointer" @click="editComment(comment)">Redigera</span>
                        <span class="link-comments cursor-pointer" @click="deleteComment(comment)">Eliminera</span>
                    </div>
                </div>
                
                
            </VCardText>
        </VCard>
      </VForm>
      
    </VDialog>

    <!-- Confirm leave without saving (parent-level for mobile outside-click and route change) -->
    <VDialog
      v-model="isConfirmLeaveVisible"
      persistent
      class="action-dialog"
    >
      <VBtn
        icon
        class="btn-white close-btn"
        @click="cancelLeave"
      >
        <VIcon size="16" icon="custom-close" />
      </VBtn>
      <VCard>
        <VCardText class="dialog-title-box">
          <img :src="modalWarningIcon" alt="Warning" class="action-icon" />
          <div class="dialog-title">Du har osparade ändringar</div>
        </VCardText>
        <VCardText class="dialog-text">
            Om du lämnar den här sidan nu kommer den information du har angett inte att sparas.
        </VCardText>
        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn class="btn-light" @click="confirmLeave">Lämna sidan</VBtn>
          <VBtn class="btn-gradient" @click="cancelLeave">Stanna kvar</VBtn>
        </VCardText>
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
          <VListItem @click="openExportDateMenu('pdf')">
            <VListItemTitle>Exportera PDF</VListItemTitle>
          </VListItem>

          <VListItem @click="openExportDateMenu('excel')">
            <VListItemTitle>Exportera Excel</VListItemTitle>
          </VListItem>
        </VList>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss">

  .card-form.note-index {
    .v-input:not(.v-textarea) {
      .v-input__control {
        .v-field {
          background-color: #f6f6f6 !important;
          min-height: 40px !important;
          height: 40px !important;

          .v-text-field__suffix {
            padding: 8px 16px !important;
          }

          .v-field__input {
            min-height: 40px !important;
            height: 40px !important;
            padding: 8px 16px !important;

            input {
              min-height: 40px !important;
              height: 40px !important;
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

    .v-select .v-field {
      .v-select__selection {
          align-items: center;
          color: #454545;
      }

      .v-field__input > input {
        top: 0px;
        left: 18px;

      }

      .v-field__input input::placeholder,
      input.v-field__input::placeholder,
      .v-field__input textarea::placeholder,
      textarea.v-field__input::placeholder {
          color: #454545 !important;
          opacity: 1 !important;
        }
    }

    .selector-country {
      .v-input__prepend {
        margin-inline-end: 6px !important;
      }
    }

  }

  .dialog-fullscreen.v-overlay--active .v-overlay__content {
    width: 100% !important;
    height: 100% !important;
    max-width: 100% !important;
    max-height: 100% !important;
  }

  .note-value-field {
    background-color: #F6F6F6;
    border-radius: 8px;
    border: 1px solid #E7E7E7;
    padding: 0 8px;
    height: 40px !important;
    align-items: center;
    display: flex;
    font-weight: 400;
    font-size: 16px;
    line-height: 24px;
    color: #878787;
  }

  .border-card-comment {
      border: 1px solid #E7E7E7;
      border-radius: 16px !important;
  }

  .title-comments {
      font-weight: 700;
      font-size: 20px;
      line-height: 100%;
      color: #454545; 
  }

  .subtitle-comments {
    font-weight: 400;
    font-size: 16px;
    line-height: 100%;
    color: #878787;
  }

  .text-comments {
      font-weight: 400;
      font-size: 16px;
      line-height: 100%;
      color: #454545; 
  }

  .span-comments {
      font-weight: 400;
      font-size: 24px;
      line-height: 100%;
      color: #454545; 
  }

  .user-comments {
      font-weight: 400;
      font-size: 16px;
      line-height: 100%;
      color: #454545; 
  }

  .date-comments {
      font-weight: 400;
      font-size: 12px;
      line-height: 100%;
      color: #878787; 
  }

  .link-comments {
      font-weight: 500;
      font-size: 12px;
      line-height: 100%;
      color: #454545;
      text-decoration: underline;
  }

  .border-comments {
      border-bottom: 1px solid #E7E7E7;
  }
</style>

<route lang="yaml">
  meta:
    action: view
    subject: notes
</route>