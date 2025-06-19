import { defineStore } from 'pinia'
import Costs from '@/api/costs'

export const useCostsStores = defineStore('costs', {
    state: () => ({
        costs: {},
        loading: false
    }),
    getters:{
        getCosts(){
            return this.costs
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchCosts(params) {
            this.setLoading(true)
            
            return Costs.get(params)
                .then((response) => {
                    this.costs = response.data.data.costs.data
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addCost(data) {
            this.setLoading(true)

            return Costs.create(data)
                .then((response) => {                
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showCost(id) {
            this.setLoading(true)

            return Costs.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.cost)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateCost(data) {
            this.setLoading(true)
            
            return Costs.update(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteCost(id) {
            this.setLoading(true)

            return Costs.delete(id)
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
