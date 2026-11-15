import { defineStore } from 'pinia'
import Permissions from '@/api/permissions'

export const usePermissionsStores = defineStore('permissions', {
    state: () => ({
        permissions: {},
        loading: false,
        last_page: 1,
        permissionsTotalCount: 6
    }),
    getters:{
        getPermissions(){
            return this.permissions
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        allPermissions(params) {
            this.setLoading(true)
            
            return Permissions.all(params)
                .then((response) => {
                    return Promise.resolve(response.data.data)
                }).catch(error => {
                    return Promise.reject(error)
                }).finally(() => {
                    this.setLoading(false)
                })
            
        },
    }
})
