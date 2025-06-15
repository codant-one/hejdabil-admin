import { defineStore } from 'pinia'
import Models from '@/api/models'

export const useModelsStores = defineStore('models', {
    state: () => ({
        models: {},
        loading: false,
        last_page: 1,
        modelsTotalCount: 6
    }),
    getters:{
        getModels(){
            return this.models
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchModels(params) {
            this.setLoading(true)
            
            return Models.get(params)
                .then((response) => {
                    this.models = response.data.data.invoices.data
                    this.last_page = response.data.data.invoices.last_page
                    this.modelsTotalCount = response.data.data.invoicesTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addModel(data) {
            this.setLoading(true)

            return Models.create(data)
                .then((response) => {
                    this.models.push(response.data.data.model)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showModel(id) {
            this.setLoading(true)

            return Models.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.model)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateModel(data) {
            this.setLoading(true)
            
            return Models.update(data)
                .then((response) => {
                    let pos = this.models.findIndex((item) => item.id === response.data.data.model.id)
                    this.models[pos] = response.data.data.model
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteModel(id) {
            this.setLoading(true)

            return Models.delete(id)
                .then((response) => {
                    let index = this.models.findIndex((item) => item.id === id)
                    this.models.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        }
    }
})
