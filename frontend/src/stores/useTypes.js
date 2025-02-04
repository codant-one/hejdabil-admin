import { defineStore } from 'pinia'
import Types from '@/api/types'

export const useTypesStores = defineStore('types', {
    state: () => ({
        types: {},
        loading: false
    }),
    getters:{
        getTypes() {
          return this.types
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        }, 
        fetchTypes(){
            this.setLoading(true)

            return Types.get()
                .then((response) => {
                    this.types = response.data.data
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                });
        }
    }
})
