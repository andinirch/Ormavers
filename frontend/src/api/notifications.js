import api, { responseData } from './api.js'

export const getNotifications = (params = {}) => api.get('/organisasi/notifikasi', { params }).then(responseData)
export const getUnreadNotificationCount = () => api.get('/organisasi/notifikasi/unread-count').then(responseData)
export const markNotificationRead = (id) => api.post(`/organisasi/notifikasi/${id}/baca`).then(responseData)
export const markAllNotificationsRead = () => api.post('/organisasi/notifikasi/baca-semua').then(responseData)
