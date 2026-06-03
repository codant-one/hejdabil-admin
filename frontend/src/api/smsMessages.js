import axios from '@axios'

class SmsMessages {

    get(params = {}) {
        return axios.get('/sms-messages', { params })
    }
}

const smsMessages = new SmsMessages()

export default smsMessages