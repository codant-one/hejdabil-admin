import { defineStore } from 'pinia'
import Settings from '@/api/settings'
import billings from '@/api/billings'
import agreements from '@/api/agreements'

export const useSettingsStore = defineStore('settings', {
    state: () => ({
        settings: {},
    }),
    getters:{
        getSettings(){
            return this.settings
        },
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },   
        showSettings(id) {
            this.setLoading(true)

            return Settings.get(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.settings)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },  
        colors(data) {
            this.setLoading(true)

            return Settings.colors(data)
                .then((response) => {
                    this.settings = response.data.data.settings
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        billings(data) {
            this.setLoading(true)

            return Settings.billings(data)
                .then((response) => {
                    this.settings = response.data.data.settings
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        agreements(data) {
            this.setLoading(true)

            return Settings.agreements(data)
                .then((response) => {
                    this.settings = response.data.data.settings
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        }
    }
})
