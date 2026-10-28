import api, { responseData } from './api.js'

export const getOrganizationDescription = () => api.get('/organisasi/deskripsi').then(responseData)
export const updateOrganizationDescription = (deskripsi) => api.put('/organisasi/deskripsi', { deskripsi }).then(responseData)
export const listOrganizationContacts = () => api.get('/organisasi/kontak').then(responseData)
export const createOrganizationContact = (data) => api.post('/organisasi/kontak', data).then(responseData)
export const updateOrganizationContact = (id, data) => api.put(`/organisasi/kontak/${id}`, data).then(responseData)
export const deleteOrganizationContact = (id) => api.delete(`/organisasi/kontak/${id}`).then(responseData)
export const listOrganizationPortfolios = () => api.get('/organisasi/portofolio').then(responseData)
export const createOrganizationPortfolio = (data) => api.post('/organisasi/portofolio', data).then(responseData)
export const updateOrganizationPortfolio = (id, data) => {
  if (data instanceof FormData) {
    data.set('_method', 'PUT')
    return api.post(`/organisasi/portofolio/${id}`, data).then(responseData)
  }
  return api.put(`/organisasi/portofolio/${id}`, data).then(responseData)
}
export const deleteOrganizationPortfolio = (id) => api.delete(`/organisasi/portofolio/${id}`).then(responseData)
export const getBidangOrganisasi = () => api.get('/master/bidang-organisasi').then(responseData)
