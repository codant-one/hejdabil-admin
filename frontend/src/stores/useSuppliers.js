import { defineStore } from 'pinia'
import Suppliers from '@/api/suppliers'

export const useSuppliersStores = defineStore('suppliers', {
    state: () => ({
        suppliers: {},
        loading: false,
        last_page: 1,
        suppliersTotalCount: 6
    }),
    getters:{
        getSuppliers(){
            return this.suppliers
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchSuppliers(params) {
            this.setLoading(true)
            
            return Suppliers.get(params)
                .then((response) => {
                    this.suppliers = response.data.data.suppliers.data
                    this.last_page = response.data.data.suppliers.last_page
                    this.suppliersTotalCount = response.data.data.suppliersTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addSupplier(data) {
            this.setLoading(true)

            return Suppliers.create(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showSupplier(id) {
            this.setLoading(true)

            return Suppliers.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.supplier)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateSupplier(data) {
            this.setLoading(true)
            
            return Suppliers.update(data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteSupplier(id) {
            this.setLoading(true)

            return Suppliers.delete(id)
                .then((response) => {
                    let index = this.suppliers.findIndex((item) => item.id === id)
                    this.suppliers.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        }
    }
})
