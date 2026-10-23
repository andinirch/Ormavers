import api, { responseData } from './api.js'

export const getSummary = () => api.get('/admin-panel/ringkasan').then(responseData)
export const getPermissions = () => api.get('/admin-panel/permissions').then(responseData)
export const getRoles = () => api.get('/admin-panel/roles').then(responseData)
export const getRole = (id) => api.get(`/admin-panel/roles/${id}`).then(responseData)
export const createRole = (data) => api.post('/admin-panel/roles', data).then(responseData)
export const updateRole = (id, data) => api.put(`/admin-panel/roles/${id}`, data).then(responseData)
export const deleteRole = (id) => api.delete(`/admin-panel/roles/${id}`).then(responseData)
export const getActivities = (signal, params = {}) => api.get('/admin-panel/activities', { signal, params }).then(responseData)
export const getActivityFilterOptions = () => api.get('/admin-panel/activities/filter-options').then(responseData)
