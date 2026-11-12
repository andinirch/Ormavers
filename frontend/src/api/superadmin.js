import api, { responseData } from './api.js'

export const listUsers = (params = {}) => api.get('/superadmin/users', { params }).then(responseData)
export const createUser = (data) => api.post('/superadmin/users', data).then(responseData)
export const updateUser = (id, data) => api.put(`/superadmin/users/${id}`, data).then(responseData)
export const listAdministrators = (params = {}) => api.get('/superadmin/administrators', { params }).then(responseData)
export const storeAdministrator = (data) => api.post('/superadmin/administrators', data).then(responseData)
export const deactivateAdministrator = (id) => api.patch(`/superadmin/administrators/${id}/nonaktif`).then(responseData)
export const updateAdministratorStatus = (id, status_akun) => api.patch(`/superadmin/administrators/${id}/status`, { status_akun }).then(responseData)
