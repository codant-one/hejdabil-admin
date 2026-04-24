
<script setup>

   import { useRemindersStores } from '@/stores/useReminders';
   import { requiredValidator } from '@validators';
   import { formatDate, formatDateYMD } from '@/@core/utils/formatters'
   import InlineBanner from '@/components/common/InlineBanner.vue'
   import ExportDateMenu from '@/components/common/ExportDateMenu.vue'

   const emit = defineEmits(['refresh', 'advisor'])

   const props = defineProps({
      reminders: {
         type: Array,
         default: () => [],
      },
   })

   const { width: windowWidth } = useWindowSize();
   const remindersStores = useRemindersStores()

   const skapatsDialog = ref(false)
   const inteSkapatsDialog = ref(false)
   const err = ref(null)

   const confirmDeleteDialog = ref(false)
   const deletedDialog = ref(false)
   const deleteErrorDialog = ref(false)
   const isDeleting = ref(false)

   const animatedReminderId = ref(null)
   let reminderAnimationTimeout = null

   const formInstanceKey = ref(0)
   const refVForm = ref()
   const isSubmitting = ref(false)
   const reminderDateMenuVisible = ref(false)
   const form = ref({
      description: '',
      date: '',
      is_done: 0,
   })

   const startDateTimePickerConfig = computed(() => ({
      inline: true,
      mode: 'single',
      enableTime: true, 
      dateFormat: 'Y-m-d H:i',
      position: 'auto right',
      time_24hr: true,
      monthSelectorType: 'dropdown',
   }))

   const openReminderDateMenu = () => {
      reminderDateMenuVisible.value = true
   }

   const resetForm = async () => {
      form.value = {
         description: '',
         date: '',
         is_done: 0,
      }
      refVForm.value?.resetValidation()

      await nextTick()
      formInstanceKey.value += 1
   }

   const onSubmit = async () => {
      const { valid } = await refVForm.value?.validate()

      if (!valid)
         return

      isSubmitting.value = true

      try {
         await remindersStores.addReminder({ ...form.value })
         await resetForm()
         skapatsDialog.value = true
         setTimeout(() => {
            skapatsDialog.value = false
         }, 3000)

         emit('refresh')
      } catch (error) {error

         const responseData = error?.response?.data

         if (responseData?.message) {
            err.value = responseData.message
         } else if (responseData?.errors) {
            err.value = Object.values(responseData.errors).flat().join('<br>')
         } else if (error?.message) {
            err.value = error.message
         } else {
            err.value = 'Ett fel uppstod när uppgiften skulle sparas. Försök igen.'
         }

         inteSkapatsDialog.value = true

         setTimeout(() => {
            inteSkapatsDialog.value = false
         }, 3000)
      } finally {
         isSubmitting.value = false
      }
   }

   const mapReminderItem = item => ({
      id: item?.id,
      title: item?.description ?? '',
      date: item?.date,
      completed: Boolean(item?.is_done),
      raw: item,
   })

   const taskItems = ref([])

   watch(
      () => props.reminders,
      reminders => {
         taskItems.value = (reminders ?? []).map(mapReminderItem)
      },
      { immediate: true }
   )

   const triggerReminderAnimation = id => {
      animatedReminderId.value = id

      if (reminderAnimationTimeout)
         clearTimeout(reminderAnimationTimeout)

      reminderAnimationTimeout = setTimeout(() => {
         if (animatedReminderId.value === id)
            animatedReminderId.value = null
      }, 320)
   }

   const onDeleteCompleted = async () => {
      confirmDeleteDialog.value = false

      try {
         await remindersStores.deleteCompleted()
         deletedDialog.value = true
         setTimeout(() => {
            deletedDialog.value = false
         }, 3000)
         emit('refresh')
      } catch (error) {
         err.value = error
         deleteErrorDialog.value = true
      } finally {
         isDeleting.value = false
      }
   }

   const showDeleteError = () => {
      deleteErrorDialog.value = false

      const responseData = err.value?.response?.data
      let message = ''

      if (responseData?.message) {
         message = responseData.message
      } else if (responseData?.errors) {
         message = Object.values(responseData.errors).flat().join('<br>')
      } else if (err.value?.message) {
         message = err.value.message
      } else {
         message = 'Ett serverfel uppstod. Försök igen.'
      }

      emit('advisor', { type: 'error', message })
   }

   const handleCheckboxChange = async (item, value) => {
      const previousValue = item.completed
      const nextValue = Boolean(value)

      triggerReminderAnimation(item.id)
      item.completed = nextValue

      try {
         await remindersStores.updateState(item.id, nextValue ? 1 : 0)
         emit('refresh')
      } catch (error) {
         item.completed = previousValue
         const message = error?.message ?? 'Det gick inte att uppdatera påminnelsen.'
      }
   }
</script>

<template>
   <VCard title="" class="card-dashboard">
      <VCardTitle class="title-box border-none">
         <div class="title-text mb-2">Mina anteckningar</div>

         <VBtn
            v-if="taskItems.some(item => item.completed)"
            class="px-3 h-40"
            :class="windowWidth < 1024 ? 'btn-light w-100' : 'btn-white-2'"
            :disabled="isDeleting"
            @click="confirmDeleteDialog = true"
         >
            <VIcon icon="custom-clean" size="24" color="#6E9383"/>
            <span class="text-gunmetal-3">Rensa markerade</span>
         </VBtn>

      </VCardTitle>

      <VCardText 
         class="form-dashboard flex-0 pb-4" 
         :class="windowWidth < 1024 ? 'px-4' : 'px-6'">
         <VForm :key="formInstanceKey" ref="refVForm" @submit.prevent="onSubmit">
            <div class="d-flex flex-column gap-2">
               <div class="information-form__field information-form__field--full">
                  <VTextField
                     v-model="form.description"
                     placeholder="Lägg till ny uppgift..."
                     :rules="[requiredValidator]"
                     hide-details="auto"
                  />
               </div>
               <div class="d-flex gap-2 w-100 information-form__dates">
                  <div class="information-form__field">
                     <VTextField
                        id="information-date-input"
                        v-model="form.date"
                        placeholder="Datum"
                        :rules="[requiredValidator]"
                        readonly
                        hide-details="auto"
                        @click="openReminderDateMenu"
                        @focus="openReminderDateMenu"
                     />

                     <ExportDateMenu
                        v-model="form.date"
                        v-model:menuVisible="reminderDateMenuVisible"
                        :show-activator="false"
                        :is-mobile="windowWidth < 1024"
                        :reset-on-open="false"
                        activator="#information-date-input"
                        button-text="Spara"
                        button-icon="custom-save"
                        picker-label="Datum"
                        picker-placeholder="Datum"
                        :picker-config="startDateTimePickerConfig"
                        @update:filtrera="reminderDateMenuVisible = false"
                     />
                  </div>
                  
                  <VBtn
                     class="btn-gradient information-form__submit"
                     style="border-radius: 8px !important;"
                     type="submit"
                     :loading="isSubmitting"
                     :disabled="isSubmitting"
                  >
                     <VIcon icon="custom-add-circle" size="24" />
                  </VBtn>

               </div>
            </div>
         </VForm>
      </VCardText>

      <VCardText 
         class="pt-4 h-50" 
         :class="windowWidth < 1024 ? 'px-4' : 'px-6'"
      >
         <div class="information-list d-flex flex-column">
            <InlineBanner
               v-if="skapatsDialog"
               variant="success"
               title="Uppgift skapad"
               icon="custom-check-mark"
               class="alert-no-shrink"
               style="flex: none;"
            >
               Din uppgift har lagts till.
            </InlineBanner>

            <InlineBanner
               v-if="deletedDialog"
               variant="success"
               title="Uppgifter borttagna"
               icon="custom-clean"
               class="alert-no-shrink"
               style="flex: none;"
            >
               Alla slutförda uppgifter har tagits bort.
            </InlineBanner>

            <InlineBanner
               v-if="inteSkapatsDialog"
               variant="error"
               title="Kunde inte skapa uppgiften"
               icon="custom-risk"
               class="alert-no-shrink"
               style="flex: none;"
            >
              {{ err }}
            </InlineBanner>

            <div
               v-for="item in taskItems"
               :key="item.id"
               class="information-item d-flex align-start"
            >
               <div
                  :class="[
                     'information-item__checkbox ms-2',
                     animatedReminderId === item.id ? 'information-item__checkbox--animating' : ''
                  ]"
               >
                  <VCheckbox
                     :model-value="item.completed"
                     :true-value="true"
                     :false-value="false"
                     true-icon="custom-filled-checkbox"
                     false-icon="custom-empty-checkbox"
                     color="#454545"
                     :ripple="false"
                     hide-details
                     @update:model-value="handleCheckboxChange(item, $event)"
                  />
               </div>

               <div
                  class="information-item__content"
                  :class="{ 'information-item__content--animating': animatedReminderId === item.id }"
               >
                  <div 
                     class="information-item__title"
                     :class="{ 'information-item__title--completed': !!item.completed }">
                     {{ item.title }}
                  </div>

                  <div class="information-item__meta d-flex align-center">
                     <VIcon icon="custom-calendar" size="16" />
                     <div class="d-flex align-center gap-1" style="margin-top: 1px;">
                       
                           {{ formatDateYMD(item.date) }}
                      
                        <VIcon size="16" icon="custom-clock" />
                      
                           {{ item.date ? formatDate(item.date, { hour: '2-digit', minute: '2-digit', hour12: false }) : ''}}
                      
                     </div>
                  </div>
               </div>
            </div>

            <div 
               v-if="!taskItems.length"  
               class="empty-state mb-0"
            >
               <VIcon
                  size="80"
                  icon="custom-coffee"
               />
               <div class="empty-state-content w-100 pa-4">
                  <div class="empty-state-title">Inga uppgifter ännu</div>
                  <div class="empty-state-text">
                     Håll koll på kunder, samtal och bokningar.<br>
                     Lägg till din första uppgift ovan.
                  </div>
               </div>
            </div>
         </div>
      </VCardText>
   </VCard>

   <!-- 👉 Confirm Delete Dialog -->
   <VDialog
      v-model="confirmDeleteDialog"
      persistent
      class="action-dialog dialog-big-icon"
   >
      <VBtn
         icon
         class="btn-white close-btn"
         @click="confirmDeleteDialog = false"
      >
         <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
         <VCardText class="dialog-title-box big-icon justify-center pb-0">
            <VIcon size="72" icon="custom-warning-triangle" />
         </VCardText>
         <VCardText class="dialog-title-box justify-center">
            <div class="dialog-title">Ta bort alla slutförda uppgifter?</div>
         </VCardText>
         <VCardText class="dialog-text text-center">
            Alla uppgifter markerade som klara kommer att tas bort permanent.
         </VCardText>
         <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
            <VBtn class="btn-light" @click="confirmDeleteDialog = false">
               Avbryt
            </VBtn>
            <VBtn class="btn-gradient" @click="onDeleteCompleted">
               Ta bort
            </VBtn>
         </VCardText>
      </VCard>
   </VDialog>

   <!-- 👉 Delete Error Dialog -->
   <VDialog
      v-model="deleteErrorDialog"
      persistent
      class="action-dialog dialog-big-icon"
   >
      <VBtn
         icon
         class="btn-white close-btn"
         @click="deleteErrorDialog = false"
      >
         <VIcon size="16" icon="custom-close" />
      </VBtn>

      <VCard>
         <VCardText class="dialog-title-box big-icon justify-center pb-0">
            <VIcon size="72" icon="custom-f-cancel" />
         </VCardText>
         <VCardText class="dialog-title-box justify-center">
            <div class="dialog-title">Kunde inte ta bort uppgifterna</div>
         </VCardText>
         <VCardText class="dialog-text text-center">
            Ett fel uppstod vid borttagning. Försök igen.
         </VCardText>
         <VCardText class="d-flex justify-center gap-3 flex-wrap dialog-actions">
            <VBtn class="btn-light" @click="showDeleteError">
               Stäng
            </VBtn>
         </VCardText>
      </VCard>
   </VDialog>
</template>

<style lang="scss">
   .form-dashboard {
      border-bottom: 1px solid #F6F6F6;

      .information-form__dates {
         align-items: flex-start;
      }

      .information-form__field {
         flex: 1 1 0;
         min-width: 0;
      }

      .information-form__field--full {
         width: 100%;
      }

      .information-form__submit {
         flex: 0 0 auto;
         height: 48px !important;
         min-height: 48px !important;
         align-self: flex-start;
      }

      & .v-input {
         margin-bottom: 0 !important;
         display: flex;
         flex-direction: column;

         .v-input__prepend {
            padding-top: 12px !important;
         }

         .v-input__details {
            position: static;
            min-height: 18px;
            padding-top: 4px;
            padding-inline: 0;

            .v-messages__message {
               font-size: 12px;
               line-height: 14px;
            }
         }

         & .v-input__control {
         .v-field {
            background-color: #FFFFFF;
            min-height: 48px !important;

            .v-text-field__suffix {
               padding: 12px 16px !important;
            }

            .v-field__input {
               min-height: 48px !important;
               padding: 12px 16px !important;

               @media (max-width: 767px) { 
                  font-size: 13px !important;
               }
               input {
                  min-height: 48px !important;

                  @media (max-width: 767px) {
                     font-size: 13px !important;
                  }
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

   .card-dashboard {
      height: 100%;
      display: flex;
      flex-direction: column;
      min-height: 0;
      overflow: hidden;
   }

   .card-dashboard {
      .information-list {
         gap: 20px;
      }

      .information-item {
         gap: 12px;
      }

      .information-item__checkbox {
         flex: 0 0 auto;
         display: flex;
         align-items: flex-start;
         transform-origin: center;

         &.information-item__checkbox--animating {
            animation: dashboard-checkbox-pop 0.28s ease;
         }
      }

      .information-item__content {
         min-width: 0;
         transition: transform 0.22s ease, opacity 0.22s ease;
      }

      .information-item__content--animating {
         animation: dashboard-item-highlight 0.32s ease;
      }

      .information-item__title {
         color: #454545;
         font-weight: 400;
         font-size: 14px;
         line-height: 20px;
         letter-spacing: 0px;
         vertical-align: middle;

         @media (max-width: 767px) {
            font-size: 13px;
         }
      }

      .information-item__title--completed {
         text-decoration: line-through;
         text-decoration-thickness: 1px;
         color: #878787 !important;
      }

      .information-item__meta {
         color: #878787;
         font-weight: 400;
         font-size: 14px;
         line-height: 20px;
         letter-spacing: 0px;
         gap: 4px;
      }

      .information-item__empty {
         color: #878787;
         font-size: 14px;
         line-height: 20px;
      }
   }

   @keyframes dashboard-checkbox-pop {
      0% {
         transform: scale(1);
      }
      45% {
         transform: scale(1.18);
      }
      100% {
         transform: scale(1);
      }
   }

   @keyframes dashboard-item-highlight {
      0% {
         transform: translateX(0);
         opacity: 1;
      }
      35% {
         transform: translateX(4px);
         opacity: 0.82;
      }
      100% {
         transform: translateX(0);
         opacity: 1;
      }
   }
</style>