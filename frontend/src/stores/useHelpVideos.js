import { defineStore } from 'pinia'
import HelpVideos from '@/api/helpVideos'

export const useHelpVideosStores = defineStore('help-videos', {
    state: () => ({
        helpVideos: [],
        loading: false,
        last_page: 1,
        helpVideosTotalCount: 6
    }),
    getters:{
        getHelpVideos(){
            return this.helpVideos
        }
    },
    actions: {
        setLoading(payload){
            this.loading = payload
        },
        fetchHelpVideos(params) {
            this.setLoading(true)
            
            return HelpVideos.get(params)
                .then((response) => {
                    this.helpVideos = response.data.data.helpVideos.data
                    this.last_page = response.data.data.helpVideos.last_page
                    this.helpVideosTotalCount = response.data.data.helpVideosTotalCount
                    return response.data.data
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        addHelpVideo(data) {
            this.setLoading(true)

            return HelpVideos.create(data)
                .then((response) => {
                    this.helpVideos.push(response.data.data.helpVideo)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        showHelpVideo(id) {
            this.setLoading(true)

            return HelpVideos.show(id)
                .then((response) => {
                    if(response.data.success)
                        return Promise.resolve(response.data.data.helpVideo)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
            
        },
        updateHelpVideo(data) {
            this.setLoading(true)
            
            return HelpVideos.update(data)
                .then((response) => {
                    let pos = this.helpVideos.findIndex((item) => item.id === response.data.data.helpVideo.id)
                    this.helpVideos[pos] = response.data.data.helpVideo
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
         
        },
        deleteHelpVideo(id) {
            this.setLoading(true)

            return HelpVideos.delete(id)
                .then((response) => {
                    let index = this.helpVideos.findIndex((item) => item.id === id)
                    this.helpVideos.splice(index, 1)
                    return Promise.resolve(response)
                })
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })  
        },
        updateState(id) {
            this.setLoading(true)

            return HelpVideos.updateState(id)
                .then((response) => Promise.resolve(response))
                .catch(error => Promise.reject(error))
                .finally(() => {
                    this.setLoading(false)
                })
        }
    }
})
