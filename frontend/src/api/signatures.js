import axios from '@axios'

class Signatures {

    static get publicRequestConfig() {
        return {
            skipAuthHeader: true,
            skipAuthRedirect: true,
        }
    }

    get(params) {
        return axios.get('signatures', {params})
    }

    create(data) {
        return axios.post('/signatures', data)
    }

    update(id, data) {
        return axios.put(`/signatures/${id}`, data)
    }

    show(id) {
        return axios.get(`/signatures/${id}`)
    }

    delete(id){
        return axios.delete(`/signatures/${id}`)
    }

    cancel(id){
        return axios.post(`/signatures/${id}/cancel`)
    }

    info(){
        return axios.get(`/signatures/info/all`)
    }

    logView(token) {
        return axios.post(`/signatures/${token}/log-view`, {}, Signatures.publicRequestConfig)
    }

    checkStatus(token) {
        return axios.get(`/signatures/${token}/status`, Signatures.publicRequestConfig)
    }

    getDetails(token) {
        return axios.get(`/signatures/${token}/details`, Signatures.publicRequestConfig)
    }

    getSignedPdf(token) {
        return axios.get(`/signatures/${token}/get-signed-pdf`, {
            responseType: 'blob',
            ...Signatures.publicRequestConfig,
        })
    }

    getUnsignedPdf(token) {
        return axios.get(`/signatures/${token}/get-unsigned-pdf`, {
            responseType: 'blob',
            ...Signatures.publicRequestConfig,
        })
    }

    submitSignature(token, payload) {
        return axios.post(`/signatures/submit/${token}`, payload, Signatures.publicRequestConfig)
    }
    
}

const signatures = new Signatures();

export default signatures;