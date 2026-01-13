import axios from '@axios'

class Notifications {

    getAll() {
        return axios.get('/notifications')
    }

    markAsRead(id) {
        return axios.post(`/notifications/${id}/read`)
    }

    markAllAsRead() {
        return axios.post('/notifications/read-all')
    }

    send(data) {
        return axios.post('/notifications/send', data)
    }
}

const notifications = new Notifications();

export default notifications;
