import { defineStore } from 'pinia'
import Brands from '@/api/brands'

export const useBrandsStores = defineStore('brands', {
    state: () => ({
        brands: {},
        loading: false,
        last_page: 1,
        brandsTotalCount: 6
    }),
    getters:{
        getBrands(){
            return this.brands
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchBrands(params) {
            this.setLoading(true)
            
            return Brands.get(params)
                .then((response) => {
                    this.brands = response.data.data.invoices.data
                    this.last_page = response.data.data.invoices.last_page
                    this.brandsTotalCount = response.data.data.invoicesTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addBrand(data) {
            this.setLoading(true)

            return Brands.create(data)
                .then((response) => {
                    this.brands.push(response.data.data.brand)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showBrand(id) {
            this.setLoading(true)

            return Brands.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.brand)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateBrand(data) {
            this.setLoading(true)
            
            return Brands.update(data)
                .then((response) => {
                    let pos = this.brands.findIndex((item) => item.id === response.data.data.brand.id)
                    this.brands[pos] = response.data.data.brand
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteBrand(id) {
            this.setLoading(true)

            return Brands.delete(id)
                .then((response) => {
                    let index = this.brands.findIndex((item) => item.id === id)
                    this.brands.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        }
    }
})
