import axios from '@axios'

class Alerts {

    get(params) {
        return axios.get('alerts', {params})
    }

    create(data) {
        return axios.post('/alerts', data)
    }

    show(id) {
        return axios.get(`/alerts/${id}`)
    }

    update(data) {
        return axios.post(`/alerts/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/alerts/${id}`)
    }
}

const alerts = new Alerts();

export default alerts;
