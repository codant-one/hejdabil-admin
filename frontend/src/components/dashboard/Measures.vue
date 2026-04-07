<script setup>

   import { useRouter } from 'vue-router'
   import { formatNumber, formatDateYMD } from '@/@core/utils/formatters'
   import { useTasksStores } from '@/stores/useTasks'

   const emit = defineEmits(['loading', 'refresh'])

   const props = defineProps({
      measures: {
         type: Object,
         default: () => ({}),
      },
   })

   const { width: windowWidth } = useWindowSize();

   const router = useRouter()
   const tasksStores = useTasksStores()
   const animatedMeasureId = ref(null)
   let measureAnimationTimeout = null

   const measureItems = computed(() => {
      const items = props.measures?.measures ?? props.measures

      return Array.isArray(items) ? items : []
   })

   const getVehicleDisplayTitle = item => {
      const regNumber = item?.vehicle?.reg_num ?? ''
      const brandName = item?.vehicle?.model?.brand?.name ?? ''
      const year = item?.vehicle?.year ?? ''
      const primaryText = [regNumber, brandName].filter(Boolean).join(' ')

      return [primaryText, year].filter(Boolean).join(', ')
   }

   const getMeasureDescription = item => {
      const measureName = item?.measure ?? ''
      const description = item?.description ?? ''

      return [measureName, description].filter(Boolean).join('. ')
   }

   const isConfirmStatusTaskDialogVisible = ref(false)
   const selectedTaskSource = ref(null)
   const selectedTaskOriginalStatus = ref(0)
   const selectedTask = ref({
      measure: null,
      description: null,
      cost: null,
      start_date: null,
      end_date: null,
      is_cost: 0
   })

   const restoreSelectedTaskStatus = () => {
      if (selectedTaskSource.value)
         selectedTaskSource.value.is_cost = selectedTaskOriginalStatus.value
   }

   const closeStatusModal = () => {
      restoreSelectedTaskStatus()
      selectedTaskSource.value = null
      isConfirmStatusTaskDialogVisible.value = false
      emit('loading', false)
   }

   const showStatusModal = (taskData) => {
      selectedTask.value = {
         ...taskData,
         start_date: taskData.start_date ?? null,
         end_date: taskData.end_date ?? null
      }
      isConfirmStatusTaskDialogVisible.value = true
   }

   const handleCheckboxChange = (taskData, value) => {
      animatedMeasureId.value = taskData?.id ?? null

      if (measureAnimationTimeout)
         clearTimeout(measureAnimationTimeout)

      measureAnimationTimeout = setTimeout(() => {
         if (animatedMeasureId.value === taskData?.id)
            animatedMeasureId.value = null
      }, 320)

      selectedTaskSource.value = taskData
      selectedTaskOriginalStatus.value = taskData?.is_cost ?? 0
      taskData.is_cost = value
      showStatusModal(taskData)
   }

   const updateTypeTask = async () => {
      emit('loading', true)

      try {
         isConfirmStatusTaskDialogVisible.value = false
         await tasksStores.typeTask(selectedTask.value.id)
         selectedTaskSource.value = null
         emit('refresh')
      } catch (error) {
         restoreSelectedTaskStatus()
         selectedTaskSource.value = null
         emit('loading', false)
         throw error
      }

   }

   const truncateText = (text, length = 15) => {
      if (text && text.length > length) {
         return text.substring(0, length) + '...';
      }
      return text;
   };

   const goToVehicles = () => {

      router.push({ name : 'dashboard-admin-stock'}) 

   };

   const goToTask = vehicleId => {
      if (!vehicleId)
         return

      router.push(`/dashboard/admin/stock/edit/${vehicleId}#tab-tasks`)
   }

</script>

<template>
   <VCard title="" class="card-dashboard">
      <VCardTitle 
         class="title-box"
         :class="windowWidth < 1024 ? 'flex-row align-center' : ''"
      >
         <div class="title-text">Planerade åtgärder</div>

         <VBtn
            class="btn-white-2 px-3 h-24"
            @click="goToVehicles"
         >
            <VIcon icon="custom-eye" size="24" />
            Visa avslutade
         </VBtn>
      </VCardTitle>

      <VCardText class="pt-6 measures-card-text" :class="windowWidth < 1024 ? 'px-4' : 'px-6'">
         <div class="measure-list d-flex flex-column h-100">
            <div
               v-for="item in measureItems"
               :key="item.id"
               class="measure-item d-flex align-start"
            >
               <div
                  :class="[
                     'measure-item__checkbox ms-2',
                     animatedMeasureId === item.id ? 'measure-item__checkbox--animating' : ''
                  ]"
               >
                  <VCheckbox
                     :model-value="item.is_cost"
                     :true-value="1"
                     :false-value="0"
                     true-icon="custom-filled-checkbox"
                     false-icon="custom-empty-checkbox"
                     color="#454545"
                     :ripple="false"
                     hide-details
                     @update:model-value="handleCheckboxChange(item, $event)"
                  />
               </div>

               <div
                  class="measure-item__content"
                  :class="{ 'measure-item__content--animating': animatedMeasureId === item.id }"
               >
                  <div
                     class="measure-item__header cursor-pointer d-flex align-center flex-wrap"
                     @click="goToTask(item.vehicle_id)"
                  >
                     <VIcon icon="custom-autofordon" size="16" :color="item.is_cost ? '#878787' : '#6E9383'" />
                     <span
                        class="measure-item__title"
                        :class="{ 'measure-item__title--completed': !!item.is_cost }"
                     >
                        {{ getVehicleDisplayTitle(item) }}
                     </span>
                     <VIcon icon="custom-arrow-right" size="16" :color="item.is_cost ? '#878787' : '#6E9383'" />
                  </div>

                  <div 
                     class="measure-item__description"
                     :class="{ 'measure-item__title--completed': !!item.is_cost }"
                  >
                     {{ 
                        truncateText(
                           getMeasureDescription(item), 
                           windowWidth < 1024 ? 70 : 130
                        ) 
                     }}
                  </div>

                  <div class="measure-item__meta d-flex align-center">
                     <div class="measure-item__meta-group d-flex align-center">
                        <VIcon icon="custom-pris-information" size="16" color="#878787" />
                        <span>{{ formatNumber(item.cost) }} SEK</span>
                     </div>

                     <div class="measure-item__meta-group d-flex align-center">
                        <VIcon icon="custom-calendar" size="16" color="#878787" />
                        <span>{{ formatDateYMD(item.start_date) }} - {{ formatDateYMD(item.end_date) }}</span>
                     </div>
                  </div>
               </div>
            </div>

            <div 
               v-if="!measureItems.length"  
               class="empty-state mb-0"
            >
               <VIcon
                  size="80"
                  icon="custom-settings-2"
               />
               <div class="empty-state-content w-100 pa-4">
                  <div class="empty-state-title">Inga åtgärder ännu</div>
                  <div class="empty-state-text">
                     Lägg till service eller arbete på ett fordon för att se det här.
                  </div>
               </div>
            </div>
         </div>
      </VCardText>
   </VCard>

   <!-- 👉 Confirm update state task -->
   <VDialog
      v-model="isConfirmStatusTaskDialogVisible"
      persistent
      class="action-dialog" >
      
      <VBtn
            icon
            class="btn-white close-btn"
         @click="closeStatusModal"
      >
            <VIcon size="16" icon="custom-close" />
      </VBtn>

      <!-- Dialog Content -->
      <VForm
            ref="refForm"
            @submit.prevent="updateTypeTask">
            <VCard flat class="card-form">
               <VCardText class="dialog-title-box">
                  <VIcon size="32" icon="custom-finance" class="action-icon" />
                  <div class="dialog-title">
                        Flytta till kostnader
                  </div>
               </VCardText>
               <VCardText class="dialog-text">
                  Är du säker på att du vill ändra planen <strong>{{ selectedTask.measure }}</strong> till kostnad?
               </VCardText>

               <VCardText class="d-flex justify-end gap-3 flex-wrap dialog-actions">
                  <VBtn
                        class="btn-light"
                     @click="closeStatusModal">
                        Avbryt
                  </VBtn>
                  <VBtn class="btn-gradient" type="submit">
                        Uppdatera
                  </VBtn>
               </VCardText>
            </VCard>
      </VForm>
   </VDialog>
</template>

<style lang="scss">

   .card-dashboard {
      height: 100%;
      display: flex;
      flex-direction: column;
      min-height: 0;
      overflow: hidden;
   }

   .measures-card-text {
      border-bottom: 1px solid #F6F6F6;
   }

   .measure-list {
      gap: 24px;
   }

   .measure-item {
      gap: 12px;

      @media (max-width: 767px) {
          gap: 4px;
      }
   }

   .measure-item__checkbox {
      flex: 0 0 auto;
      display: flex;
      align-items: flex-start;
      transform-origin: center;

      &.measure-item__checkbox--animating {
         animation: dashboard-checkbox-pop 0.28s ease;
      }
   }

   .measure-item__content {
      min-width: 0;
      display: flex;
      flex: 1 1 auto;
      flex-direction: column;
      gap: 4px;
      transition: transform 0.22s ease, opacity 0.22s ease;
   }

   .measure-item__content--animating {
      animation: dashboard-item-highlight 0.32s ease;
   }

   .measure-item__header {
      gap: 4px;
   }

   .measure-item__title {
      font-weight: 400;
      font-size: 16px;
      line-height: 20px;
      letter-spacing: 0px;
      vertical-align: middle;
      color: #6E9383;

      @media (max-width: 767px) {
         font-size: 14px;
      }
   }

   .measure-item__title--completed {
      text-decoration: line-through;
      text-decoration-thickness: 1px;
      color: #878787 !important;
   }

   .measure-item__description {
      color: #454545;
      font-weight: 400;
      font-size: 14px;
      line-height: 20px;
      letter-spacing: 0px;
      vertical-align: middle;
   }

   .measure-item__meta {
      color: #878787;
      font-weight: 400;
      font-size: 14px;
      line-height: 20px;
      letter-spacing: 0px;
      vertical-align: middle;
      gap: 16px;

      @media (max-width: 767px) {
         font-size: 11px;
         flex-direction: row;
         gap: 4px;
      }
   }

   .measure-item__meta-group {
      gap: 4px;
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