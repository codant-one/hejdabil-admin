import { defineStore } from 'pinia'
import Genders from '@/api/genders'

export const useGendersStores = defineStore('genders', {
    state: () => ({
        genders: {},
        loading: false
    }),
    getters:{
        getGenders() {
          return this.genders
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        }, 
        fetchGenders(){
            this.setLoading(true)

            return Genders.get()
                .then((genders) => {
                    this.genders = genders.data.data
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                });
        }
    }
})
