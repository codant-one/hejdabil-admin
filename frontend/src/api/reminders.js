import axios from '@axios'

class Reminders {

    create(data) {
        return axios.post('/reminders', data)
    }

    updateState(id, data) {
        return axios.post(`/reminders/updateState/${id}`, data)
    }

    delete(id){
        return axios.delete(`/reminders/${id}`)
    }

    
}

const reminders = new Reminders();

export default reminders;