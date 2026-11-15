import axios from '@axios'

class Permissions {
  all(params) {
    return axios.get('permissions/permission/all', { params })
  }
}

export default new Permissions()
