import axios from '@axios'

class Notifications {
    
    send(data) {
        return axios.post('/notifications/send', data)
    }
}

const notifications = new Notifications();

export default notifications;
