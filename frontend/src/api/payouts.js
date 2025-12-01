import axios from '@axios'

class Payouts {

    get(params) {
        return axios.get('payouts', {params})
    }

    create(data) {
        return axios.post('/payouts', data)
    }

    show(id) {
        return axios.get(`/payouts/${id}`)
    }

    delete(id){
        return axios.delete(`/payouts/${id}`)
    }
    
}

const payouts = new Payouts();

export default payouts;