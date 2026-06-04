import { defineStore } from 'pinia'
import Activities from '@/api/activities'

export const useActivitiesStore = defineStore('activities', {
    state: () => ({
        activities: {},
        suppliers: {},
        loading: false,
        last_page: 1,
        activitiesTotalCount: 0
    }),
    getters:{
        getActivities(){
            return this.activities
        },
        getSuppliers(){
            return this.suppliers
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchActivities(params) {
            this.setLoading(true)
            
            return Activities.get(params)
                .then((response) => {
                    const activitiesPayload = response.data.data.activities
                    const activityRows = Array.isArray(activitiesPayload)
                        ? activitiesPayload
                        : activitiesPayload?.data ?? []

                    this.activities = activityRows
                    this.suppliers = response.data.data.suppliers
                    this.last_page = activitiesPayload?.last_page ?? 1
                    this.activitiesTotalCount = response.data.data.activitiesTotalCount ?? activitiesPayload?.total ?? activityRows.length
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        }
    }
})
