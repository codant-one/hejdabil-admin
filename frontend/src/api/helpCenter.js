import axios from '@axios'

class HelpCenter {

  get(params) {
    return axios.get('/help-center', { params })
  }

  categories() {
    return axios.get('/help-center/categories')
  }

  category(label, params) {
    return axios.get(`/help-center/category/${encodeURIComponent(label)}`, { params })
  }

}

const helpCenter = new HelpCenter()

export default helpCenter
