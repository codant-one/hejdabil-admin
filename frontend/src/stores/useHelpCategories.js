import { defineStore } from 'pinia'
import HelpCategories from '@/api/helpCategories'

export const useHelpCategoriesStores = defineStore('help-categories', {
    state: () => ({
        helpCategories: {},
        loading: false,
        last_page: 1,
        helpCategoriesTotalCount: 6
    }),
    getters:{
        getHelpCategories(){
            return this.helpCategories
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchHelpCategories(params) {
            this.setLoading(true)
            
            return HelpCategories.get(params)
                .then((response) => {
                    this.helpCategories = response.data.data.helpCategories.data
                    this.last_page = response.data.data.helpCategories.last_page
                    this.helpCategoriesTotalCount = response.data.data.helpCategoriesTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addHelpCategory(data) {
            this.setLoading(true)

            return HelpCategories.create(data)
                .then((response) => {
                    this.helpCategories.push(response.data.data.helpCategory)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showHelpCategory(id) {
            this.setLoading(true)

            return HelpCategories.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.helpCategory)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateHelpCategory(data) {
            this.setLoading(true)
            
            return HelpCategories.update(data)
                .then((response) => {
                    let pos = this.helpCategories.findIndex((item) => item.id === response.data.data.helpCategory.id)
                    this.helpCategories[pos] = response.data.data.helpCategory
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteHelpCategory(id) {
            this.setLoading(true)

            return HelpCategories.delete(id)
                .then((response) => {
                    let index = this.helpCategories.findIndex((item) => item.id === id)
                    this.helpCategories.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        updateState(id) {
            this.setLoading(true)

            return HelpCategories.updateState(id)
                .then((response) => Promise.resolve(response))
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        }
    }
})
