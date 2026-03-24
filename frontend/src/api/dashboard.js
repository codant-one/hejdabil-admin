import axios from '@axios'

class Dashboard {

    statisticians(params) {
        return axios.get('dashboard/statisticians', {params})
    }
    
}

const dashboard = new Dashboard();

export default dashboard;