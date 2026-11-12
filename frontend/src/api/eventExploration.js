import api, { responseData } from './api.js'

export const getExploredEvents = (params = {}) => api.get('/events', { params }).then(responseData)
export const getExploredEvent = (id) => api.get(`/events/${id}`).then(responseData)
export const createEventSharelink = (id) => api.post(`/events/${id}/bagikan`).then(responseData)
export const getEventByShareToken = (token) => api.get(`/events/share/${token}`).then(responseData)
export const getEventCategories = () => api.get('/master/kategori-event').then(responseData)
