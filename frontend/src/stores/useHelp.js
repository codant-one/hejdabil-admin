import { defineStore } from 'pinia'
import Help from '@/api/help'

export const useHelpStores = defineStore('help', {
  state: () => ({
    videos: [],
    modules: [],
    states: [],
    last_page: 1,
    videosTotalCount: 0,
    loading: false,
  }),
  getters: {
    getVideos: state => state.videos,
    getModules: state => state.modules,
  },
  actions: {
    async fetchVideos(params, center = false) {
      this.loading = true
      try {
        const response = await (center ? Help.center(params) : Help.get(params))
        const data = response.data.data

        this.videos = data.videos.data
        this.modules = data.modules
        this.states = data.states
        this.last_page = data.videos.last_page
        this.videosTotalCount = data.videosTotalCount

        return data
      } finally {
        this.loading = false
      }
    },
    addVideo: data => Help.create(data),
    updateVideo: data => Help.update(data),
    deleteVideo: id => Help.delete(id),
    updateState: id => Help.updateState(id),
  },
})
