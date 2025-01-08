import axios from '@axios'

class Roles {

    get(params) {
        return axios.get('roles', {params})
    }

    create(data) {
        return axios.post('/roles', data)
    }

    update(data, id) {
        return axios.put(`/roles/${id}`, data)
    }

    delete(id){
        return axios.delete(`/roles/${id}`)
    }

    all(){
        return axios.get('roles/role/all')
    }
}

const roles = new Roles();

export default roles;