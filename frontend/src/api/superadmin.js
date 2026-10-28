import api, { responseData } from './api.js'

export const listUsers = (params = {}) => api.get('/superadmin/users', { params }).then(responseData)
export const storeAdministrator = (data) => api.post('/superadmin/administrators', data).then(responseData)
export const updateAdministratorStatus = (id, status_akun) => api.patch(`/superadmin/administrators/${id}/status`, { status_akun }).then(responseData)
