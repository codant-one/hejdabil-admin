import { defineStore } from 'pinia'
import Plans from '@/api/plans'

export const usePlansStores = defineStore('plans', {
    state: () => ({
        plans: {},
        features: {},
        loading: false,
        last_page: 1,
        plansTotalCount: 6
    }),
    getters:{
        getPlans(){
            return this.plans
        },
        getFeatures(){
            return this.features
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchPlans(params) {
            this.setLoading(true)
            
            return Plans.get(params)
                .then((response) => {
                    this.plans = response.data.data.plans.data
                    this.features = response.data.data.features
                    this.last_page = response.data.data.plans.last_page
                    this.plansTotalCount = response.data.data.plansTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addPlan(data) {
            this.setLoading(true)

            return Plans.create(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showPlan(id) {
            this.setLoading(true)

            return Plans.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.plan)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updatePlan(data) {
            this.setLoading(true)
            
            return Plans.update(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deletePlan(id) {
            this.setLoading(true)

            return Plans.delete(id)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        updateState(id) {
            this.setLoading(true)

            return Plans.updateState(id)
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
