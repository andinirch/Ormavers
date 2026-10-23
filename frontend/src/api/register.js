import api, { responseData } from './api.js'

const multipartPost = (path, formData) => api.post(path, formData, {
  headers: { 'Content-Type': 'multipart/form-data' },
}).then(responseData)

export const registerBem = (formData) => multipartPost('/register/bem', formData)
export const registerHmjUkm = (formData) => multipartPost('/register/hmj-ukm', formData)
export const registerAnggota = (formData) => multipartPost('/register/anggota', formData)
