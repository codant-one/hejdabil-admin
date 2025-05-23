import axios from '@axios'

class Billings {

    get(params) {
        return axios.get('billings', {params})
    }

    create(data) {
        return axios.post('/billings', data)
    }

    show(id) {
        return axios.get(`/billings/${id}`)
    }

    update(data) {
        return axios.post(`/billings/${data.id}`, data.data)
    }

    delete(id) {
        return axios.delete(`/billings/${id}`)
    }

    updateState(id) {
        return axios.get(`/billings/updateState/${id}`)
    }

    sendMails(data) {
        return axios.post(`/billings/sendMails/${data.id}`, data)
    }

    all(){
        return axios.get(`/billings/data/all`)
    }

    credit(id) {
        return axios.get(`/billings/credit/${id}`)
    }

    reminder(id) {
        return axios.get(`/billings/reminder/${id}`)
    }

    info(){
        return axios.get(`/billings/info/all`)
    }
}

const billings = new Billings();

export default billings;