import axios from '@axios'

class Swish {

    createPayout(payeeAlias, amount, payoutRef) {
        return axios.post(`/swish/payout`, { payeeAlias, amount, payoutRef })
    }
    
    handlePayout(payload) {
        return axios.post(`/swish/payout/callback`, payload)
    }
}

const swish = new Swish();

export default swish;