
<script setup>

   import { useRemindersStores } from '@/stores/useReminders';
   import { requiredValidator } from '@validators';

   const emit = defineEmits(['refresh'])

   const props = defineProps({
      reminders: {
         type: Array,
         default: () => [],
      },
   })

   const { width: windowWidth } = useWindowSize();
   const remindersStores = useRemindersStores()

   const skapatsDialog = ref(false);
   const inteSkapatsDialog = ref(false);

   const animatedReminderId = ref(null)
   let reminderAnimationTimeout = null

   const formInstanceKey = ref(0)
   const refVForm = ref()
   const isSubmitting = ref(false)
   const form = ref({
      description: '',
      start_date: '',
      end_date: '',
      is_done: 0,
   })

   const startDateTimePickerConfig = computed(() => ({
      dateFormat: 'Y-m-d',
      position: 'auto right',
   }))

   const endDateTimePickerConfig = computed(() => {
      const config = {
         dateFormat: 'Y-m-d',
         position: 'auto right',
      }

      if (form.value.start_date)
         config.minDate = form.value.start_date

      return config
   })

   const endDateAfterOrEqualValidator = value => {
      if (!value)
         return true

      if (!form.value.start_date)
         return true

      return value >= form.value.start_date || 'Slutdatum måste vara samma datum som eller senare än startdatum'
   }

   const resetForm = async () => {
      form.value = {
         description: '',
         start_date: '',
         end_date: '',
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
         emit('refresh')
      } catch (error) {
         const message = error?.message ?? 'Det gick inte att skapa påminnelsen.'
         console.error(message)
      } finally {
         isSubmitting.value = false
      }
   }

   const formatDate = value => {
      if (!value)
         return ''

      const parsedDate = new Date(value)

      if (Number.isNaN(parsedDate.getTime()))
         return value

      return parsedDate.toLocaleDateString('sv-SE')
   }

   const mapReminderItem = item => ({
      id: item?.id,
      title: item?.description ?? '',
      startDate: formatDate(item?.start_date),
      endDate: formatDate(item?.end_date),
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
      <VCardTitle 
         class="title-box border-none pb-2"
         :class="windowWidth < 1024 ? 'flex-row align-center' : ''"
      >
         <div class="title-text mb-2">Mina uppgifter</div>

      </VCardTitle>

      <VCardText class="pt-2 form-dashboard" :class="windowWidth < 1024 ? 'px-4' : 'px-6'">
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
                     <AppDateTimePicker
                        :key="JSON.stringify(startDateTimePickerConfig)"
                        v-model="form.start_date"
                        density="default"
                        :config="startDateTimePickerConfig"
                        clearable
                        class="field-solo-flat"
                        placeholder="Startdatum"
                        :rules="[requiredValidator]"
                        hide-details="auto"
                     />
                  </div>
                  <div class="information-form__field">
                     <AppDateTimePicker
                        :key="JSON.stringify(endDateTimePickerConfig)"
                        v-model="form.end_date"
                        density="default"
                        :config="endDateTimePickerConfig"
                        clearable
                        class="field-solo-flat"
                        placeholder="Slutdatum"
                        :rules="[requiredValidator, endDateAfterOrEqualValidator]"
                        hide-details="auto"
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
         class="pt-4" 
         :class="[
            windowWidth < 1024 ? 'px-4' : 'px-6', 
            !taskItems.length ? 'h-100' : ''
         ]"
      >
         <div class="information-list d-flex flex-column">
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
                     <span>{{ item.startDate }} - {{ item.endDate }}</span>
                  </div>
               </div>
            </div>
            <div v-if="!taskItems.length" class="information-item__empty">
               Inga uppgifter hittades.
            </div>
         </div>
      </VCardText>
   </VCard>
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
         vertical-align: middle;
         gap: 4px;
         line-height: 22px;
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