import { defineStore } from 'pinia'
import PersonInfo from '@/api/personinfo'

export const usePersonInfoStores = defineStore('personinfo', {
    state: () => ({
        loading: false
    }),
    getters: {
        //
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        getPersonInfo(personId) {
            this.setLoading(true)

            return PersonInfo.getPersonInfo(personId)
                .then((response) => {
                    return Promise.resolve(response.data)
                })
                .catch(error => {
                    return Promise.reject(error)
                })
                .finally(() => {
                    this.setLoading(false)
                })
        }
    }
})
