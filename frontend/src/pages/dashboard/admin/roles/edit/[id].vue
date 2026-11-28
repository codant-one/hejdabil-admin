<script setup>

import { useDisplay } from 'vuetify'
import { requiredValidator } from '@/@core/utils/validators'
import { useRolesStores } from '@/stores/useRoles'
import { usePermissionsStores } from '@/stores/usePermissions'
import LoadingOverlay from '@/components/common/LoadingOverlay.vue'
import Toaster from '@/components/common/Toaster.vue'
import modalWarningIcon from '@/assets/images/icons/alerts/modal-warning-icon.svg'

const sectionEl = ref(null)

const { width: windowWidth } = useWindowSize()
const { mdAndDown } = useDisplay()

const snackbarLocation = computed(() => mdAndDown.value ? '' : 'top end')

const router = useRouter()
const route = useRoute()
const rolesStores = useRolesStores()
const permissionsStores = usePermissionsStores()

const refFormEdit = ref()
const id = ref(null)
const name = ref('')
const assignedPermissions = ref([])
const initialData = ref(null)
const isRequestOngoing = ref(false)
const permissionError = ref('')
const permissionLabels = ref(new Map())
const permissionsLoaded = ref(false)
const permissionLoadError = ref('')
const isConfirmLeaveVisible = ref(false)
const nextRoute = ref(null)
const allowNavigation = ref(false)

const advisor = ref({
  type: '',
  message: '',
  show: false,
})

const userData = JSON.parse(localStorage.getItem('user_data') || 'null')
const isSuperAdmin = userData?.roles?.[0]?.name === 'SuperAdmin'

const modulePermissions = (subject, actions = ['view', 'create', 'edit', 'delete']) =>
  actions.map(action => ({
    value: `${action} ${subject}`,
    disabled: subject === 'roles' && action === 'delete' && !isSuperAdmin,
  }))

const permissionSections = [
  {
    title: 'Konto & team',
    modules: [
      { title: 'Roller', description: 'Roller och deras behörigheter', permissions: modulePermissions('roles') },
      { title: 'Användare', description: 'Medarbetare med inloggning', permissions: modulePermissions('users') },
      {
        title: 'Mitt team',
        description: 'Företagsprofil och team',
        permissions: [
          ...modulePermissions('my-team'),
          { value: 'view team-reports' },
        ],
      },
      {
        title: 'Inställningar',
        description: 'Kontoinställningar',
        permissions: [
          { value: 'view company' },
          { value: 'view sms' },
          { value: 'view plan' },
          { value: 'view trash' }
        ],
      },
    ],
  },
  {
    title: 'Moduler',
    modules: [
      { title: 'Kunder', description: 'Kundregister och historik', permissions: modulePermissions('clients') },
      { title: 'Fakturor', description: 'Fakturor kopplade till affärer', permissions: modulePermissions('billings') },
      { title: 'Leverantörer', description: 'Leverantörer och inköp', permissions: modulePermissions('suppliers') },
      {
        title: 'Fordonslager',
        description: 'Fordon, märken och modeller',
        children: [
          { title: 'Märken', permissions: modulePermissions('brands') },
          { title: 'Modeller', permissions: modulePermissions('models') },
          { title: 'Lager', permissions: modulePermissions('stock') },
          { title: 'Sålda fordon', permissions: modulePermissions('sold', ['view', 'delete']) },
        ],
      },
      { title: 'Avtal', description: 'Köp- och försäljningsavtal', permissions: modulePermissions('agreements') },
      { title: 'Signera dokument', description: 'Dokument för digital signering', permissions: modulePermissions('signed-documents') },
      { title: 'Swish', description: 'Utbetalningar och betalningar', permissions: modulePermissions('payouts') },
      { title: 'Mina värderingar', description: 'Värderingar och anteckningar', permissions: modulePermissions('notes') },
    ],
  },
  {
    title: 'Administration',
    modules: [
      {
        title: 'Attribut',
        description: 'Inställningar för fakturor',
        children: [
          { title: 'Fakturor', permissions: modulePermissions('invoices') },
        ],
      },
      {
        title: 'Hjälpcenter',
        description: 'Hjälpartiklar och videor',
        children: [
          { title: 'Kategorier', permissions: modulePermissions('help-categories') },
          { title: 'Videor', permissions: modulePermissions('help-videos') },
        ],
      },
      { title: 'Valuta', description: 'Tillgängliga valutor', permissions: modulePermissions('currencies') },
      { title: 'Land', description: 'Tillgängliga länder', permissions: modulePermissions('countries') },
      { title: 'Planer', description: 'Abonnemang och funktioner', permissions: modulePermissions('plans') },
      { title: 'Notifikationer', description: 'Administrera notifikationer', permissions: modulePermissions('notifications', ['view', 'create', 'delete']) },
    ],
  },
  {
    title: 'Hjälpcenter',
    modules: [
      {
        title: 'Hjälpcenter',
        description: 'Hjälpartiklar och videor',
        permissions: [{ value: 'view help-center' }],
      },
    ],
  },
]

const getModuleRows = module => module.children ?? [module]

const getSectionPermissions = section =>
  section.modules.flatMap(module => getModuleRows(module).flatMap(row => row.permissions))

const selectablePermissions = section => getSectionPermissions(section).filter(permission => !permission.disabled)

const selectedCount = section =>
  selectablePermissions(section).filter(permission => assignedPermissions.value.includes(permission.value)).length

const isSectionSelected = section => selectedCount(section) === selectablePermissions(section).length
const isSectionIndeterminate = section => selectedCount(section) > 0 && !isSectionSelected(section)
const totalPermissions = permissionSections.flatMap(selectablePermissions).length
const selectedPermissionsCount = computed(() => permissionSections.reduce((total, section) => total + selectedCount(section), 0))

const isFormEdited = computed(() => initialData.value !== null && (
  name.value !== initialData.value.name
  || JSON.stringify([...assignedPermissions.value].sort()) !== JSON.stringify([...initialData.value.permissions].sort())
))

async function fetchData() {
  if (isRequestOngoing.value)
    return

  isRequestOngoing.value = true
  permissionsLoaded.value = false
  permissionLoadError.value = ''
  permissionError.value = ''
  allowNavigation.value = false

  try {
    const [role, { permissions }] = await Promise.all([
      rolesStores.showRole(route.params.id),
      permissionsStores.allPermissions({ with_labels: true }),
    ])

    const labels = new Map(permissions.map(permission => [permission.name, permission.label]))

    const hasMissingLabels = permissionSections.flatMap(getSectionPermissions)
      .some(permission => !labels.get(permission.value)?.trim())

    if (hasMissingLabels)
      throw new Error('En eller flera behörigheter saknar etikett. Kontrollera behörigheterna och försök igen.')

    id.value = role.id
    name.value = role.name
    assignedPermissions.value = role.permissions.map(permission => permission.name)
    permissionLabels.value = labels
    initialData.value = { name: name.value, permissions: [...assignedPermissions.value] }
    permissionsLoaded.value = true
  } catch (error) {
    permissionLoadError.value = typeof error?.message === 'string'
      ? error.message
      : 'Det gick inte att hämta rollen och behörigheterna. Försök igen.'
  } finally {
    isRequestOngoing.value = false
  }
}

const toggleSection = (section, checked) => {
  const values = selectablePermissions(section).map(permission => permission.value)

  assignedPermissions.value = checked
    ? [...new Set([...assignedPermissions.value, ...values])]
    : assignedPermissions.value.filter(permission => !values.includes(permission))
  permissionError.value = ''
}

const confirmLeave = () => {
  isConfirmLeaveVisible.value = false
  allowNavigation.value = true
  router.push(nextRoute.value)
}

const confirmNavigation = to => {
  if (isRequestOngoing.value)
    return false

  if (allowNavigation.value || !isFormEdited.value)
    return true

  nextRoute.value = to.fullPath
  isConfirmLeaveVisible.value = true

  return false
}

onBeforeRouteLeave(confirmNavigation)
onBeforeRouteUpdate(to => to.params.id === route.params.id || confirmNavigation(to))
watch(() => route.params.id, fetchData)

const onSubmitEdit = async () => {
  if (isRequestOngoing.value)
    return

  if (!permissionsLoaded.value) {
    advisor.value = {
      show: true,
      type: 'error',
      message: 'Hämta behörigheterna innan du uppdaterar rollen.',
    }

    return
  }

  isRequestOngoing.value = true
  advisor.value.show = false

  try {
    const { valid } = await refFormEdit.value.validate()

    permissionError.value = assignedPermissions.value.length ? '' : 'Välj minst en behörighet.'

    if (!valid || permissionError.value)
      return

    await rolesStores.updateRole({
      name: name.value.trim(),
      permissions: [...assignedPermissions.value],
    }, id.value)

    rolesStores.updateAlert = {
      type: 'success',
      message: 'Uppdaterad roll!',
    }
    allowNavigation.value = true
  } catch (error) {
    const messages = error?.feedback === 'params_validation_failed'
      ? Object.values(error.message ?? {}).flat().join(' ')
      : ''

    advisor.value = {
      show: true,
      type: 'error',
      message: messages || (error?.feedback === 'not_found' ? error.message : 'Ett serverfel uppstod. Försök igen.'),
    }
  } finally {
    isRequestOngoing.value = false
  }

  if (allowNavigation.value) {
    await router.push({ name: 'dashboard-admin-roles' })
  }
}

function resizeSectionToRemainingViewport() {
  const el = sectionEl.value

  if (!el)
    return

  const rect = el.getBoundingClientRect()
  const remaining = Math.max(0, window.innerHeight - rect.top - 25)

  el.style.minHeight = `${remaining}px`
}

onMounted(async () => {
  resizeSectionToRemainingViewport()
  window.addEventListener('resize', resizeSectionToRemainingViewport)
  await fetchData()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', resizeSectionToRemainingViewport)
})
</script>

<template>
  <section
    ref="sectionEl"
    class="page-section role-edit"
  >
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

    <Toaster />

    <VAlert
      v-if="permissionLoadError"
      type="error"
      variant="tonal"
      class="mb-4"
    >
      {{ permissionLoadError }}
      <VBtn
        variant="text"
        :disabled="isRequestOngoing"
        @click="fetchData"
      >
        Försök igen
      </VBtn>
      <VBtn
        variant="text"
        :disabled="isRequestOngoing"
        :to="{ name: 'dashboard-admin-roles' }"
      >
        Tillbaka
      </VBtn>
    </VAlert>

    <VForm
      ref="refFormEdit"
      class="card-form"
      validate-on="submit"
      @submit.prevent="onSubmitEdit"
    >
      <VCard
        v-if="permissionsLoaded"
        flat
        class="card-fill"
        :class="[
          windowWidth < 1024 ? 'flex-column' : 'flex-row',
          $vuetify.display.mdAndDown ? 'pa-6' : 'pa-4',
        ]"
      >
        <VCardText class="p-0">
          <div
            class="d-flex gap-y-4 gap-x-6 mb-4 justify-start justify-sm-space-between"
            :class="windowWidth < 1024 ? 'flex-column' : 'flex-wrap'"
          >
            <VBtn
              :class="windowWidth < 1024 ? 'd-flex' : 'd-none'"
              class="btn-light"
              style="width: 120px;"
              :to="{ name: 'dashboard-admin-roles' }"
            >
              <VIcon
                icon="custom-return"
                size="24"
              />
              Tillbaka
            </VBtn>

            <div class="d-flex flex-column gap-4">
              <span class="title-page">
                Redigera roll
              </span>
              <span class="subtitle-page justify-start">
                Ange rollens namn och välj vilka behörigheter rollen ska ha.
              </span>
            </div>

            <VSpacer :class="windowWidth < 1024 ? 'd-none' : 'd-block'" />

            <div :class="windowWidth < 1024 ? 'd-none' : 'd-flex gap-4 align-center'">
              <VBtn
                class="btn-light"
                :to="{ name: 'dashboard-admin-roles' }"
              >
                <VIcon
                  icon="custom-return"
                  size="24"
                />
                Tillbaka
              </VBtn>
            </div>
          </div>
        </VCardText>

        <VDivider :class="windowWidth < 1024 ? 'd-none' : 'my-4'" />

        <VCardText class="p-0 d-flex flex-column gap-4">
          <div class="d-flex flex-column">
            <VLabel
              class="mb-1 text-body-2 text-high-emphasis"
              text="Namn på roll*"
            />
            <VTextField
              v-model="name"
              :rules="[requiredValidator]"
              :disabled="isRequestOngoing"
            />
          </div>

          <div class="d-flex flex-column gap-1">
            <div class="permissions-title">
              Behörigheter
            </div>
            <div class="subtitle-page justify-start">
              Välj behörigheter per modul eller markera alla i en sektion.
            </div>
          </div>

          <p
            v-if="permissionError"
            role="alert"
            class="text-error mb-0"
          >
            {{ permissionError }}
          </p>

          <fieldset
            class="permission-fieldset"
            :disabled="isRequestOngoing"
            aria-label="Behörigheter"
            @change="permissionError = ''"
          >
            <section
              v-for="section in permissionSections"
              :key="section.title"
              class="permission-section"
              :aria-label="section.title"
            >
              <header class="permission-section-header">
                <h3>{{ section.title }}</h3>
                <span class="permission-count">{{ selectedCount(section) }}/{{ selectablePermissions(section).length }}</span>
                <div class="section-select-all">
                  <VCheckbox
                    :model-value="isSectionSelected(section)"
                    :indeterminate="isSectionIndeterminate(section)"
                    true-icon="custom-checked-filled-checkbox"
                    false-icon="custom-empty-checkbox"
                    indeterminate-icon="custom-minus-checkbox"
                    color="#454545"
                    :ripple="false"
                    hide-details
                    :disabled="isRequestOngoing"
                    :aria-label="`Välj alla i ${section.title}`"
                    label="Välj alla"
                    @update:model-value="toggleSection(section, $event)"
                  />
                </div>
              </header>

              <div
                v-for="module in section.modules"
                :key="module.title"
                class="permission-module"
                role="group"
                :aria-label="module.title"
              >
                <div class="module-heading d-flex flex-column justify-content-start">
                  <h4>{{ module.title }}</h4>
                  <p v-if="module.description">
                    {{ module.description }}
                  </p>
                </div>
                <div class="module-rows">
                  <div
                    v-for="row in getModuleRows(module)"
                    :key="row.title"
                    class="permission-row"
                    :class="{ 'permission-row-nested': module.children }"
                    role="group"
                    :aria-label="row.title"
                  >
                    <span
                      v-if="module.children"
                      class="permission-row-title"
                    >{{ row.title }}</span>
                    <div class="permission-options">
                      <div
                        v-for="permission in row.permissions"
                        :key="permission.value"
                        class="permission-chip"
                        :class="{
                          'permission-chip-selected': assignedPermissions.includes(permission.value),
                          'permission-chip-disabled': permission.disabled,
                        }"
                        :title="permission.disabled ? 'Endast SuperAdmin kan tilldela denna behörighet.' : undefined"
                      >
                        <VCheckbox
                          v-model="assignedPermissions"
                          :value="permission.value"
                          :label="permissionLabels.get(permission.value)"
                          :aria-label="permissionLabels.get(permission.value)"
                          :disabled="isRequestOngoing || permission.disabled"
                          true-icon="custom-checked-filled-checkbox"
                          false-icon="custom-empty-checkbox"
                          indeterminate-icon="custom-minus-checkbox"
                          color="#454545"
                          :ripple="false"
                          hide-details
                          @update:model-value="permissionError = ''"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </fieldset>
        </VCardText>

        <VCardText
          class="p-0 d-flex w-100 align-center justify-between my-4 gap-4"
          :class="windowWidth < 1024 ? 'flex-column' : ''"
        >
          <span
            class="selection-summary"
            aria-live="polite"
          >
            <strong style="color: #1C2925;">{{ selectedPermissionsCount }}</strong>
            av {{ totalPermissions }} behörigheter aktiva
          </span>
          <div
            class="d-flex gap-4"
            :class="windowWidth < 1024 ? 'flex-column-reverse w-100' : ''"
          >
            <VBtn
              class="btn-light"
              :disabled="isRequestOngoing"
              :block="windowWidth < 1024"
              :to="{ name: 'dashboard-admin-roles' }"
            >
              Avbryt
            </VBtn>
            <VBtn
              type="submit"
              class="btn-gradient"
              :loading="isRequestOngoing"
              :disabled="isRequestOngoing || !permissionsLoaded"
              :block="windowWidth < 1024"
            >
              Redigera
            </VBtn>
          </div>
        </VCardText>
      </VCard>
    </VForm>

    <VDialog
      v-model="isConfirmLeaveVisible"
      persistent
      class="action-dialog"
    >
      <VCard>
        <VCardText class="dialog-title-box">
          <img
            :src="modalWarningIcon"
            alt=""
            class="action-icon"
          >
          <div class="dialog-title">
            Du har osparade ändringar
          </div>
        </VCardText>
        <VCardText class="dialog-text">
          Om du lämnar sidan nu kommer dina ändringar inte att sparas.
        </VCardText>
        <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
          <VBtn
            class="btn-light"
            @click="confirmLeave"
          >
            Lämna sidan
          </VBtn>
          <VBtn
            class="btn-gradient"
            @click="isConfirmLeaveVisible = false"
          >
            Stanna kvar
          </VBtn>
        </VCardText>
      </VCard>
    </VDialog>
  </section>
</template>

<style lang="scss">
 .card-form {
    .v-input {
        .v-input__control {
            .v-field {
                background-color: #f6f6f6 !important;
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

                .v-text-field__prefix {
                    height: 48px;
                    color: #33303CAD;
                }
            }
        }
    }

    .v-input.always-show-prefix {
        .v-input__control {
            .v-field {
                .v-field__input {
                    padding: 8px 0 !important;
                }
            }
        }
    }

    .selector-country {
        .v-input__prepend {
            margin-inline-end: 6px !important;
        }
    }

    .v-select .v-field,
    .v-autocomplete .v-field {
        .v-select__selection,
        .v-autocomplete__selection {
            align-items: center;
        }

        .v-field__input > input {
            top: 0px;
            left: 0px;
        }
    }
}

.title-page {
    font-weight: 700;
    font-size: 32px;
    line-height: 100%;
    color: #1C2925;

    @media (max-width: 1023px) {
        font-size: 24px
    }
}

.subtitle-page {
    font-weight: 400;
    font-size: 16px;
    line-height: 100%;
    color: #454545;
}

.permissions-title {
   font-weight: 700;
    font-size: 24px;
    line-height: 100%;
    color: #454545;

    @media (max-width: 1023px) {
        font-size: 16px
    }
}

.permission-section-header {
  display: flex;
  align-items: center;
  gap: 10px;
}

.module-heading p,
.selection-summary {
  color: #6e7c78;
}

.permission-fieldset {
  min-inline-size: 0;
  padding: 0;
  border: 0;
}

.permission-section {
  overflow: hidden;
  border: 1px solid #E3EAE6;
  border-radius: 16px;

  & + & {
    margin-block-start: 20px;
  }
}

.permission-section-header {
  padding: 16px 20px;
  background: #F6FAF8;

  h3 {
    font-weight: 700;
    font-size: 17px;
    line-height: 100%;
    letter-spacing: 0;
    color: #1C2925;
  }
}

.permission-count {
    padding: 2px 9px;
    border: 1px solid #E3EAE6;
    border-radius: 999px;
    background: #FFFFFF;
    font-weight: 600;
    font-size: 12px;
    letter-spacing: 0;
    color: #6B7A75;
}

.section-select-all {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-inline-start: auto;
    cursor: pointer;
    white-space: nowrap;
    font-weight: 600;
    font-size: 14px;
    line-height: 100%;
    letter-spacing: 0;
    color: #1C2925;
}

.section-select-all,
.permission-chip {
    .v-selection-control {
        min-height: 24px;
    }

    .v-selection-control__wrapper {
        --v-selection-control-size: 24px;

        margin-inline-end: 8px;
    }

    .v-label {
        color: inherit;
        font: inherit;
        opacity: 1;
        margin-left: 0;
    }
}

.permission-module {
  display: grid;
  grid-template-columns: minmax(160px, 23%) minmax(0, 1fr);
  gap: 24px;
  padding: 16px 20px;
  border-block-start: 1px solid #edf1ef;
}

.module-heading {
  h4 {
    font-weight: 600;
    font-size: 15px;
    line-height: 100%;
    letter-spacing: 0;
    color: #1C2925;
  }

  p {
    margin: 2px 0 0;
    font-weight: 400;
    font-size: 12.5px;
    line-height: 16.88px;
    letter-spacing: 0;
    color: #6B7A75;
  }
}

.permission-row {
  display: flex;
  align-items: center;
  gap: 12px;

  & + & {
    margin-block-start: 10px;
  }
}

.permission-row-nested {
  display: grid;
  grid-template-columns: 90px minmax(0, 1fr);
}

.permission-row-title {
    font-weight: 600;
    font-size: 13px;
    line-height: 100%;
    letter-spacing: 0;
    color: #6B7A75;
}

.permission-options {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.permission-chip {
    display: inline-flex;
    flex: 0 1 auto;
    align-items: center;
    padding: 8px 16px;
    border: 1px solid #E3EAE6;
    border-radius: 10px;
    background: #FFFFFF;
    font-weight: 500;
    font-size: 14px;
    line-height: 100%;
    letter-spacing: 0;
    color: #1C2925;
    cursor: pointer;

    .v-input {
        flex: 1 1 auto;
        min-inline-size: 0;
    }

  .v-input__control,
  .v-selection-control {
    min-inline-size: 0;
    inline-size: 100%;
  }

  .v-selection-control__wrapper {
    flex-shrink: 0;
  }

  &:focus-within {
    outline: 2px solid #55786b;
    outline-offset: 2px;
  }
}

.permission-chip-selected {
    border-color: #B9E5CB;
    background: #F1FAF5;
}

.permission-chip-disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.permission-fieldset:disabled .v-label {
  cursor: wait;
}

.selection-summary {
    font-weight: 400;
    font-size: 14px;
    line-height: 100%;
    letter-spacing: 0;
    color: #6B7A75;
}

@media (max-width: 767px) {

  .permission-section-header,
  .permission-module {
    padding: 14px;
  }

  .permission-section-header {
    gap: 8px;

    h3 {
      font-size: 16px;
    }
  }

  .permission-module {
    grid-template-columns: minmax(0, 1fr);
    gap: 10px;
  }

  .module-heading {
    h4 {
        font-weight: 600;
        font-size: 15px;
        line-height: 100%;
        letter-spacing: 0;
    }

    p {
        margin-block-start: 4px;
        font-weight: 400;
        font-size: 12.5px;
        line-height: 100%;
        letter-spacing: 0;
    }
  }

  .permission-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 6px;
  }

  .permission-row-title {
    font-size: 10px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
  }

  .permission-options {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 6px;
  }

  .permission-chip {
    min-inline-size: 0;
    min-block-size: 34px;

    .v-label {
      min-inline-size: 0;
      overflow-wrap: anywhere;
      line-height: 1.35;
      white-space: normal;
      margin-left: 0;
    }
  }

  .permission-row-nested .permission-chip {
    font-size: 10px;
    letter-spacing: 0.04em;
  }
}
</style>

<route lang="yaml">
meta:
  action: edit
  subject: roles
</route>