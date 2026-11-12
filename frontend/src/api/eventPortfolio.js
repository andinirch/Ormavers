import api, { responseData } from './api.js'

// ORMV-1-27: Daftar event selesai milik organisasi (pembuat / kolaborator diterima).
export const getFinishedEvents = () => api.get('/organisasi/events-selesai').then(responseData)

// ORMV-1-27: Data prefill formulir portofolio dari event.
export const getPortfolioPrefill = (eventId) =>
  api.get(`/organisasi/events-selesai/${eventId}/prefill`).then(responseData)

// ORMV-1-27: Simpan event ke portofolio (dokumentasi berupa file).
export const addEventToPortfolio = (eventId, formData) =>
  api.post(`/organisasi/events-selesai/${eventId}/portofolio`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  }).then(responseData)
