import { defineStore } from 'pinia'
import Configs from '@/api/configs'

export const useConfigsStores = defineStore('configs', {
    state: () => ({
        configs: [],
        loading: false,
    }),
    getters:{
        getConfigs(){
            return this.configs
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        getFeaturedConfig(key){
            return this.configs[key]
        },
        getFeature(key) {
            this.setLoading(true)
            
            return Configs.get(key)
                .then((response) => {
                    this.configs[key] = JSON.parse(response.data.config.value)
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        postFeature(data) {
            this.setLoading(true)
            
            return Configs.post(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        postLogo(data) {
            this.setLoading(true)
            
            return Configs.postLogo(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
        }
    }
})
