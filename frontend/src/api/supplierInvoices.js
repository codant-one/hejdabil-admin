import axios from '@axios'

class SupplierInvoices {

    get(params) {
        return axios.get('supplier-invoices', {params})
    }

    create(data) {
        return axios.post('/supplier-invoices', data)
    }

    show(id) {
        return axios.get(`/supplier-invoices/${id}`)
    }

    update(data) {
        return axios.post(`/supplier-invoices/${data.id}`, data.data)
    }

    delete(id) {
        return axios.delete(`/supplier-invoices/${id}`)
    }

    updateState(id) {
        return axios.get(`/supplier-invoices/updateState/${id}`)
    }

    credit(id) {
        return axios.get(`/supplier-invoices/credit/${id}`)
    }

    replaceFile(data) {
        return axios.post(`/supplier-invoices/replaceFile/${data.id}`, data.data)
    }

    all(params){
        return axios.get(`/supplier-invoices/data/all`, {params})
    }
}

const supplierInvoices = new SupplierInvoices();

export default supplierInvoices;