import { defineStore } from 'pinia'
import NotificationsBroadcast from '@/api/notificationsBroadcast'

export const useNotificationsBroadcastStores = defineStore('notificationsBroadcast', {
    state: () => ({
        broadcasts: [],
        roles: [],
        allCount: 0,
        loading: false,
        last_page: 1,
        broadcastsTotalCount: 0
    }),
    getters: {
        getBroadcasts() {
            return this.broadcasts
        },
        getRoles() {
            return this.roles
        }
    },
    actions: {
        setLoading(payload) {
            this.loading = payload
        },
        fetchBroadcasts(params) {
            this.setLoading(true)

            return NotificationsBroadcast.get(params)
                .then((response) => {
                    this.broadcasts = response.data.data.broadcasts.data
                    this.last_page = response.data.data.broadcasts.last_page
                    this.broadcastsTotalCount = response.data.data.broadcastsTotalCount
                })
                .catch(error => console.log(error))
                .finally(() => {
                    this.setLoading(false)
                })
        },
        fetchRoles() {
            return NotificationsBroadcast.roles()
                .then((response) => {
                    this.roles = response.data.data.roles
                    this.allCount = response.data.data.all_count
                })
                .catch(error => console.log(error))
        },
        searchUsers(params) {
            return NotificationsBroadcast.users(params)
                .then(response => response.data.data.users)
        },
        previewCount(data) {
            return NotificationsBroadcast.previewCount(data)
                .then(response => response.data.data.recipients_count)
        },
        deleteBroadcast(id) {
            this.setLoading(true)

            return NotificationsBroadcast.delete(id)
                .finally(() => {
                    this.setLoading(false)
                })
        },
        sendBroadcast(data) {
            this.setLoading(true)

            return NotificationsBroadcast.create(data)
                .then(response => Promise.resolve(response))
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        }
    }
})
