import axios from '@axios'

class Help {
  get(params) {
    return axios.get('/help', { params })
  }

  center(params) {
    return axios.get('/help-center', { params })
  }

  create(data) {
    return axios.post('/help', data)
  }

  update(data) {
    return axios.post(`/help/${data.id}`, data.data)
  }

  delete(id) {
    return axios.delete(`/help/${id}`)
  }

  updateState(id) {
    return axios.get(`/help/updateState/${id}`)
  }
}

export default new Help()
