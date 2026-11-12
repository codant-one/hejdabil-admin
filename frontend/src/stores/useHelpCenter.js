import { defineStore } from 'pinia'
import HelpCenter from '@/api/helpCenter'

export const useHelpCenterStores = defineStore('help-center', {
  state: () => ({
    helpCenter: [],
    helpVideo: null,
    relatedHelpVideos: [],
    categories: {},
    loading: false,
    last_page: 1,
    helpCenterTotalCount: 6,
  }),
  getters:{
    getHelpCenter(){
      return this.helpCenter
    },
    getCategories() {
      return this.categories
    },
  },
  actions: {
    fetchVideo(slug) {
      this.setLoading(true)

      return HelpCenter.video(slug)
        .then(response => {
          this.helpVideo = response.data.data.helpVideo
          this.relatedHelpVideos = response.data.data.relatedHelpVideos

          return response.data.data
        })
        .finally(() => {
          this.setLoading(false)
        })
    },
    fetchCategory(label, params) {
      this.setLoading(true)

      return HelpCenter.category(label, params)
        .then(response => {
          this.helpCenter = response.data.data.helpVideos
          this.categories = response.data.data.categories
          this.helpCenterTotalCount = response.data.data.helpVideosTotalCount
          
          return response.data.data
        })
        .finally(() => {
          this.setLoading(false)
        })
    },
    setLoading(payload){
      this.loading = payload
    },
    fetchHelpCenter(params) {
      this.setLoading(true)
            
      return HelpCenter.get(params)
        .then(response => {
          this.helpCenter = response.data.data.helpCenter.data
          this.last_page = response.data.data.helpCenter.last_page
          this.helpCenterTotalCount = response.data.data.helpCenterTotalCount
          
          return response.data.data
        })
        .finally(() => {
          this.setLoading(false)
        })
            
    },
    fetchCategories() {
      this.setLoading(true)

      return HelpCenter.categories()
        .then(response => {
          if(response.data.success) {
            this.categories = response.data.data.categories
            
            return response.data.data.categories
          }
        })
        .finally(() => {
          this.setLoading(false)
        })
            
    },
  },
})
