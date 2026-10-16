import api, { responseData } from './api.js'

export const listAdministrators = () => api.get('/superadmin/administrators').then(responseData)
export const listUsers = () => api.get('/superadmin/users').then(responseData)
export const storeAdministrator = (data) => api.post('/superadmin/administrators', data).then(responseData)
export const deactivateAdministrator = (id) => api.patch(`/superadmin/administrators/${id}/nonaktif`).then(responseData)
