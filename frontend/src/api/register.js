import api, { responseData } from './api.js'

export const registerBem = (formData) => api.post('/register/bem', formData, {
  headers: { 'Content-Type': 'multipart/form-data' },
}).then(responseData)
