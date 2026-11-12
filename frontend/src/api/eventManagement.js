import api, { responseData } from './api.js'

// ORMV-1-26: Lihat Event Saya (pembuat & kolaborator diterima).
// Backend: GET /organisasi/event (peran:akun_organisasi).
export const getMyEvents = () => api.get('/organisasi/event').then(responseData)

// Master data kategori event (publik). Response: { success, data: [{ id, nama_kategori }] }.
export const getEventCategories = () => api.get('/master/kategori-event').then(responseData)

// Detail event untuk pengelolaan (form edit). ORMV-1-24.
export const getEventDetail = (id) => api.get(`/organisasi/event/${id}`).then(responseData)

// ORMV-1-21: Buat Event (multipart, termasuk poster[] dan poster_utama_index).
export const createEvent = (formData) => api.post('/organisasi/event', formData, {
  headers: { 'Content-Type': 'multipart/form-data' },
}).then(responseData)

// ORMV-1-24: Edit Event. Route backend menerima PUT|PATCH|POST; dipakai POST
// agar multipart (poster) aman tanpa method override.
export const updateEvent = (id, formData) => api.post(`/organisasi/event/${id}`, formData, {
  headers: { 'Content-Type': 'multipart/form-data' },
}).then(responseData)

// ORMV-1-25: Hapus Event (permanen; poster & ajakan terkait ikut terhapus).
export const deleteEvent = (id) => api.delete(`/organisasi/event/${id}`).then(responseData)
