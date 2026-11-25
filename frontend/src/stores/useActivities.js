import { defineStore } from 'pinia'
import Activities from '@/api/activities'

export const useActivitiesStore = defineStore('activities', {
    state: () => ({
        activities: {},
        suppliers: {},
        users: {},
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
        },
        getUsers(){
            return this.users
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
                    this.users = response.data.data.users
                    this.last_page = activitiesPayload?.last_page ?? 1
                    this.activitiesTotalCount = response.data.data.activitiesTotalCount ?? activitiesPayload?.total ?? activityRows.length
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        deleteActivity(id) {
            this.setLoading(true)

            return Activities.delete(id)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        deleteAllActivities(id) {
            this.setLoading(true)

            return Activities.deleteAll()
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        restoreActivity(id) {
            this.setLoading(true)

            return Activities.restoreItem(id)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        }
    }
})
