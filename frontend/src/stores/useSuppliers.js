import { defineStore } from 'pinia'
import Suppliers from '@/api/suppliers'

export const useSuppliersStores = defineStore('suppliers', {
    state: () => ({
        suppliers: {},
        loading: false,
        last_page: 1,
        suppliersTotalCount: 6,
        users: {},
        users_last_page: 1,
        usersTotalCount: 6,
    }),
    getters:{
        getSuppliers(){
            return this.suppliers
        },
        getUsers(params) {
            return this.users
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
        },
        activateSupplier(id) {
            this.setLoading(true)

            return Suppliers.activate(id)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        getUsersOnline(params) {
            this.setLoading(true)
            
            return Suppliers.getUsersOnline(params)
                .then((response) => {
                    return Promise.resolve(response.data.data.users)
                }).catch(error => {
                    return Promise.reject(error)
                }) 
            
        },
        addUser(data) {
            this.setLoading(true)

            return Suppliers.addUser(data)
                .then((response) => {
                    this.users.push(response.data.data.user)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        fetchUsers(params) {
            this.setLoading(true)
            
            return Suppliers.getUsers(params)
                .then((response) => {
                    this.users = response.data.data.users.data
                    this.users_last_page = response.data.data.users.last_page
                    this.usersTotalCount = response.data.data.usersTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        deleteUser(id) {
            this.setLoading(true)

            return Suppliers.deleteUser(id)
                .then((response) => {
                    let index = this.users.findIndex((item) => item.id === id)
                    this.users.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        updateUser(data, id) {
            this.setLoading(true)
            
            return Suppliers.updateUser(data, id)
                .then((response) => {
                    let pos = this.users.findIndex((item) => item.id === response.data.data.user.id)
                    this.users[pos] = response.data.data.user
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        updatePermissions(data, id) {
            this.setLoading(true)
            
            return Suppliers.updatePermissions(data, id)
                .then((response) => {
                    let pos = this.users.findIndex((item) => item.id === response.data.data.user.id)
                    this.users[pos] = response.data.data.user
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        swish(id, data) {
            this.setLoading(true)

            return Suppliers.swish(id, data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        masterPassword(id, data) {
            this.setLoading(true)

            return Suppliers.masterPassword(id, data)
                .then((response) => {
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        getMasterPassword(id) {
            this.setLoading(true)   
            
            return Suppliers.getMasterPassword(id)
                .then((response) => {
                    return Promise.resolve(response.data.data.master_password)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        }
    }
})
