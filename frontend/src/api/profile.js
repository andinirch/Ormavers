import api, { responseData } from './api.js'

export const getProfile = () => api.get('/profil').then(responseData)
export const updateProfile = (data) => api.put('/profil', data).then(responseData)
export const updatePassword = (data) => api.put('/profil/kata-sandi', data).then(responseData)
export const uploadProfilePhoto = (file) => {
  const formData = new FormData()
  formData.append('foto', file)
  return api.post('/profil/foto', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }).then(responseData)
}
