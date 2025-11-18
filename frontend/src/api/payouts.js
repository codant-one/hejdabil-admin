import axios from '@axios'

class Payouts {

    get(params) {
        return axios.get('payouts', {params})
    }

    create(data) {
        // Usa el endpoint de servicio que dispara el flujo completo de Swish Payout
        return axios.post('/swish/payout', data)
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