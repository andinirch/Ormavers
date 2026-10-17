import api, { responseData } from './api.js'

export const login = (credentials) => api.post('/auth/login', credentials).then(responseData)
export const logout = () => api.post('/auth/logout').then(responseData)
export const me = () => api.get('/auth/me').then(responseData)
