import router from '@/router'
import axios from 'axios'
import { useAlertStore } from '@/stores/useAlerts.js'
import { themeConfig } from '@themeConfig'

const axiosIns = axios.create({
  baseURL: themeConfig.settings.urlbase,
})

axiosIns.interceptors.request.use(
  config => {
    const token = localStorage.getItem('accessToken')
    if(token){
      config.headers.Authorization = `Bearer ${token}`
    } 
    
    return config
  })

axiosIns.interceptors.response.use(response => {
  return response
}, error => {
  const alertStore = useAlertStore()
  const { config, response: { status }, response: { data } } = error
  const originalRequest = config

  if (status === 401) {
      
    localStorage.removeItem('user_data')
    localStorage.removeItem('userAbilities')
    localStorage.removeItem('accessToken')

    alertStore.setAlert('Din session har gått ut eller stängts från ett annat fönster. Logga in igen.', 'error')
    
    router.push({ name: 'login' } )
    
  }
  
  return Promise.reject(data)
})

export default axiosIns
