import axios from '@axios'

class Payouts {

    get(params) {
        return axios.get('payouts', {params})
    }

    create(data) {
        return axios.post('/payouts', data)
    }

    update(id, data) {
        return axios.put(`/payouts/${id}`, data)
    }

    show(id) {
        return axios.get(`/payouts/${id}`)
    }

    delete(id){
        return axios.delete(`/payouts/${id}`)
    }

    cancel(id){
        return axios.post(`/payouts/${id}/cancel`)
    }

    info(){
        return axios.get(`/payouts/info/all`)
    }
    
}

const payouts = new Payouts();

export default payouts;