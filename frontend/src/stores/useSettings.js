import { defineStore } from 'pinia'
import Settings from '@/api/settings'

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
            
        }
    }
})
