import axios from '@axios'

class Auth {

    me(hash) {
        return axios.post('auth/me', { hash: hash })
    }

    login(data) {
        return axios.post('auth/login', data)
    }

    logout() {
        return axios.post('auth/logout')
    }

    validate(data) {
        return axios.post('auth/2fa/validate', data)
    }

    forgot_password(data) {
        return axios.post('auth/forgot-password', data)
    }

    find(token) {
        return axios.get(`auth/password/find/${token}`)
    }

    change(data) {
        return axios.post('auth/change', data)
    }

    generateQR() {
        return axios.get('auth/generateQR')
    }

    findToken(token) {
        return axios.get(`auth/find/${token}`)
    }

    completed(data) {
        return axios.post('auth/completed', data)
    }

}

const auth = new Auth();

export default auth;