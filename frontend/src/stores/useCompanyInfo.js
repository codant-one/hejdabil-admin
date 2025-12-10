import { defineStore } from 'pinia'
import CompanyInfo from '@/api/companyinfo'

export const useCompanyInfoStores = defineStore('companyinfo', {
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
        getCompanyInfo(orgNumber) {
            this.setLoading(true)

            return CompanyInfo.getCompanyInfo(orgNumber)
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
