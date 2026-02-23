import { defineStore } from 'pinia'
import Countries from '@/api/countries'

export const useCountriesStores = defineStore('countries', {
    state: () => ({
        countries: {},
        loading: false,
        last_page: 1,
        countriesTotalCount: 6
    }),
    getters:{
        getCountries(){
            return this.countries
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchCountries(params) {
            this.setLoading(true)
            
            return Countries.get(params)
                .then((response) => {
                    this.countries = response.data.data.countries.data
                    this.last_page = response.data.data.countries.last_page
                    this.countriesTotalCount = response.data.data.countriesTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addCountry(data) {
            this.setLoading(true)

            return Countries.create(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showCountry(id) {
            this.setLoading(true)

            return Countries.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.country)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateCountry(data) {
            this.setLoading(true)
            
            return Countries.update(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteCountry(id) {
            this.setLoading(true)

            return Countries.delete(id)
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

            return Countries.updateState(id)
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
