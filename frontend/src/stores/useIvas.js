import { defineStore } from 'pinia'
import Ivas from '@/api/ivas'

export const useIvasStores = defineStore('ivas', {
    state: () => ({
        ivas: {},
        loading: false,
        last_page: 1,
        ivasTotalCount: 6
    }),
    getters:{
        getIvas(){
            return this.ivas
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchIvas(params) {
            this.setLoading(true)
            
            return Ivas.get(params)
                .then((response) => {
                    this.ivas = response.data.data.ivas.data
                    this.last_page = response.data.data.ivas.last_page
                    this.ivasTotalCount = response.data.data.ivasTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        }
    }
})
