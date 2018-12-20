var API_BASE = 'http://erp.lyfz.net' // 线上地址
// var API_BASE = 'http://192.168.0.117:8000'
// var API_BASE = 'http://127.0.0.1:8000'

const http = axios.create({
    baseURL: API_BASE,
    timeout: 5000,
})

http.interceptors.response.use(
    function (response) {
        return new Promise(function (resolve, reject) {
            if (!response.data.errcode) {
                resolve(response.data)
            } else {
                vue_app.$message.error(response.data.errmsg)
                reject(new Error('error'))
            }
        })
    }
)

http.interceptors.request.use(function(config) {
    if (store.state.user.token) {
        config.headers['Authorization'] = 'Bearer ' + store.state.user.token
    }
    return config
}, function(error) {
    Promise.reject(error)
})