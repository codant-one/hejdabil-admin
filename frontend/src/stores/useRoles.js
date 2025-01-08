import { defineStore } from 'pinia'
import Roles from '@/api/roles'

export const useRolesStores = defineStore('roles', {
    state: () => ({
        roles: {},
        loading: false,
        last_page: 1,
        rolesTotalCount: 6
    }),
    getters:{
        getRoles(){
            return this.roles
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchRoles(params) {
            this.setLoading(true)
            
            return Roles.get(params)
                .then((response) => {
                    this.roles = response.data.data.roles.data
                    this.last_page = response.data.data.roles.last_page
                    this.rolesTotalCount = response.data.data.rolesTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addRole(data) {
            this.setLoading(true)

            return Roles.create(data)
                .then((response) => {
                    this.roles.push(response.data.data.role)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateRole(data, id) {
            this.setLoading(true)
            
            return Roles.update(data, id)
                .then((response) => {
                    let pos = this.roles.findIndex((item) => item.id === response.data.data.role.id)
                    this.roles[pos] = response.data.data.role
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteRole(id) {
            this.setLoading(true)

            return Roles.delete(id)
                .then((response) => {
                    let index = this.roles.findIndex((item) => item.id === id)
                    this.roles.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        allRoles() {
            this.setLoading(true)
            
            return Roles.all()
                .then((response) => {
                    return Promise.resolve(response.data.data)
                }).catch(error => {
                    return Promise.reject(error)
                }) 
            
        },
    }
})
