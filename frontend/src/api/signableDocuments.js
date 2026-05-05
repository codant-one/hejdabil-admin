import axios from '@axios'

class SignableDocuments {

    get(params) {
        return axios.get('signable-documents', {params})
    }

    create(data) {
        return axios.post('/signable-documents', data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
    }

    show(id) {
        return axios.get(`/signable-documents/${id}`)
    }

    update(data) {
        return axios.post(`/signable-documents/${data.id}`, data.data, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
    }

    delete(id){
        return axios.delete(`/signable-documents/${id}`)
    }

    getAdminPreviewPdf(id) {
        return axios.get(`/signable-documents/${id}/get-admin-preview-pdf`, {
            responseType: 'blob'
        })
    }

    sendSignatureRequest(documentId, payload) {
        return axios.post(`/signable-documents/${documentId}/send-signature-request`, payload)
    }

    resendSignatureRequest(data) {
        const documentId = typeof data === 'object' && data !== null ? data.id : data
        const payload = typeof data === 'object' && data !== null ? data : {}

        return axios.post(`/signable-documents/${documentId}/resend-signature-request`, payload)
    }

    send(data) {
        return axios.post('/signable-documents/send', data)
    }
}

const signableDocuments = new SignableDocuments();

export default signableDocuments;

