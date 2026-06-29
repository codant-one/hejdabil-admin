import axios from '@axios'

class Agreements {

    get(params) {
        return axios.get('agreements', {params})
    }

    create(data) {
        return axios.post('/agreements', data)
    }

    show(id) {
        return axios.get(`/agreements/${id}`)
    }

    update(data) {
        return axios.post(`/agreements/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/agreements/${id}`)
    }

    info(params){
        return axios.get(`/agreements/info/all`, {params})
    }
    
    sendMails(data) {
        return axios.post(`/agreements/sendMails/${data.id}`, data)
    }

    sendSms(data) {
        return axios.post(`/agreements/sendSms/${data.id}`, data)
    }

    resendSignatureRequest(data) {
        const agreementId = typeof data === 'object' && data !== null ? data.id : data
        const payload = typeof data === 'object' && data !== null ? data : {}

        return axios.post(`/agreements/${agreementId}/resend-signature-request`, payload)
    }

    resendSignatureSms(data) {
        const agreementId = typeof data === 'object' && data !== null ? data.id : data
        const payload = typeof data === 'object' && data !== null ? data : {}

        return axios.post(`/agreements/${agreementId}/resend-signature-sms`, payload)
    }

    cancelSignatureRequest(data) {
        const agreementId = typeof data === 'object' && data !== null ? data.id : data

        return axios.post(`/agreements/${agreementId}/cancel-signature-request`)
    }

    getAdminPreviewPdf(id) {
        return axios.get(`/agreements/${id}/get-admin-preview-pdf`, {
            responseType: 'blob'
        })
    }

    downloadZip(data) {
        return axios.post('/agreements/downloadZip', data, {
            responseType: 'blob'
        })
    }
}

const agreements = new Agreements();

export default agreements;