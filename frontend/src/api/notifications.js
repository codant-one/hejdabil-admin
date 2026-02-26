import axios from '@axios'

class Notifications {

    get(params) {
        return axios.get('notifications', {params})
    }

    create(data) {
        return axios.post('/notifications', data)
    }

    show(id) {
        return axios.get(`/notifications/${id}`)
    }

    update(data) {
        return axios.post(`/notifications/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/notifications/${id}`)
    }

    listRecent() {
        return axios.get('/notifications/list/recent')
    }

    markAsRead(id) {
        return axios.post(`/notifications/${id}/read`)
    }

    markAllAsRead() {
        return axios.post('/notifications/read-all')
    }

    clearAll(data) {
        return axios.post('/notifications/clear-all', data)
    }

    send(data) {
        return axios.post('/notifications/send', data)
    }
}

const notifications = new Notifications();

export default notifications;
