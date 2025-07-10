import { defineStore } from 'pinia'
import Currencies from '@/api/currencies'

export const useCurrenciesStores = defineStore('currencies', {
    state: () => ({
        currencies: {},
        loading: false,
        last_page: 1,
        currenciesTotalCount: 6
    }),
    getters:{
        getCurrencies(){
            return this.currencies
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchCurrencies(params) {
            this.setLoading(true)
            
            return Currencies.get(params)
                .then((response) => {
                    this.currencies = response.data.data.currencies.data
                    this.last_page = response.data.data.currencies.last_page
                    this.currenciesTotalCount = response.data.data.currenciesTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addCurrency(data) {
            this.setLoading(true)

            return Currencies.create(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showCurrency(id) {
            this.setLoading(true)

            return Currencies.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.currency)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateCurrency(data) {
            this.setLoading(true)
            
            return Currencies.update(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteCurrency(id) {
            this.setLoading(true)

            return Currencies.delete(id)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        updateState(id) {
            this.setLoading(true)

            return Currencies.updateState(id)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        }
    }
})
