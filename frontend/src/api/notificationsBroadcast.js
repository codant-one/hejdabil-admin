import axios from '@axios'

class NotificationsBroadcast {

    get(params) {
        return axios.get('admin/notifications-broadcast', { params })
    }

    roles() {
        return axios.get('admin/notifications-broadcast/roles')
    }

    users(params) {
        return axios.get('admin/notifications-broadcast/users', { params })
    }

    previewCount(data) {
        return axios.post('admin/notifications-broadcast/preview-count', data)
    }

    create(data) {
        return axios.post('admin/notifications-broadcast', data)
    }

    delete(id) {
        return axios.delete(`admin/notifications-broadcast/${id}`)
    }
}

const notificationsBroadcast = new NotificationsBroadcast();

export default notificationsBroadcast;
