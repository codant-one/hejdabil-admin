import axios from '@axios'

class Profile {

    updateData(data) {
        return axios.post('users/update/profile',  data , { headers: { 'Content-Type': 'multipart/form-data' } } )
    }
    
    updatePassword(data) {
        return axios.post('users/update/password', data)
    }

    updateCompany(data) {
        return axios.post('users/update/company', data)
    }

    updateLogo(data) {
        return axios.post('users/update/company/logo', data)
    }

    updateSignature(data) {
        return axios.post('users/update/company/signature', data)
    }

}

const profile = new Profile();

export default profile;