import axios from '@axios'

class Tasks {

    create(data) {
        return axios.post('/tasks', data)
    }

    update(data) {
        return axios.post(`/tasks/${data.id}`, data.data)
    }

    delete(id){
        return axios.delete(`/tasks/${id}`)
    }

    comment(data) {
        return axios.post('/tasks/comment', data)
    }
    
}

const tasks = new Tasks();

export default tasks;