import axios from '@axios'

class CompanyInfo {

    getCompanyInfo(orgNumber) {
        return axios.get(`/companies/lookup/${orgNumber}`)
    }
    
}

export default new CompanyInfo()
