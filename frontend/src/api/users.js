import axios from '@axios'

class Users {

    get(params) {
        return axios.get('users', {params})
    }

    create(data) {
        return axios.post('/users', data)
    }

    update(data, id) {
        return axios.put(`/users/${id}`, data)
    }

    delete(id){
        return axios.delete(`/users/${id}`)
    }

    getUsersOnline(params) {
        return axios.get('users/user/online', {params})
    }

    updatePasswordUser(data, id) {
        return axios.post(`users/update/password/${id}`, data)
    }
    
}

const users = new Users();

export default users;