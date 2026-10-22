import api, { responseData } from './api.js'

export const listBemRegistrations = () => api.get('/admin/registrations/bem').then(responseData)
export const showBemRegistration = (id) => api.get(`/admin/registrations/bem/${id}`).then(responseData)
export const getBemDocument = (id, jenis) => api.get(`/admin/registrations/bem/${id}/dokumen/${jenis}`, { responseType: 'blob' })
export const approveBemRegistration = (id) => api.post(`/admin/registrations/bem/${id}/approve`).then(responseData)
export const rejectBemRegistration = (id, alasan) => api.post(`/admin/registrations/bem/${id}/reject`, { alasan }).then(responseData)
