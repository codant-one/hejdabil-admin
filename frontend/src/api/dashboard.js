import axios from '@axios'

class Dashboard {

    statisticians(params) {
        return axios.get('dashboard/statisticians', {params})
    }
    
    indicators(params) {
        return axios.get('dashboard/indicators', {params})
    }

    profit() {
        return axios.get('dashboard/profit')
    }
}

const dashboard = new Dashboard();

export default dashboard;