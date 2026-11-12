import api, { responseData } from './api.js'

// ORMV-1-22 & ORMV-1-23: daftar organisasi yang tersedia untuk diajak/diundang.
// jenis: 'kolaborator' | 'undangan'.
export const getAvailableOrganizations = (eventId, jenis, params = {}) =>
  api.get(`/organisasi/event/${eventId}/organisasi-tersedia`, { params: { jenis, ...params } }).then(responseData)

// ORMV-1-22: Kirim ajakan kolaborasi.
export const inviteCollaborators = (eventId, organisasiIds) =>
  api.post(`/organisasi/event/${eventId}/ajak-kolaborasi`, { organisasi_ids: organisasiIds }).then(responseData)

// ORMV-1-23: Kirim undangan organisasi.
export const inviteGuests = (eventId, organisasiIds) =>
  api.post(`/organisasi/event/${eventId}/undang`, { organisasi_ids: organisasiIds }).then(responseData)

// ORMV-1-22: Batalkan ajakan kolaborasi yang belum dijawab.
export const cancelCollaboration = (eventId, ajakanId) =>
  api.post(`/organisasi/event/${eventId}/kolaborasi/${ajakanId}/batalkan`).then(responseData)

// ORMV-1-23: Batalkan undangan yang belum dijawab.
export const cancelInvitation = (eventId, ajakanId) =>
  api.post(`/organisasi/event/${eventId}/undangan/${ajakanId}/batalkan`).then(responseData)

// Rekapitulasi ajakan/undangan yang dikirim pembuat event.
export const getEventInvitations = (eventId, jenis) =>
  api.get(`/organisasi/event/${eventId}/ajakan`, { params: jenis ? { jenis } : undefined }).then(responseData)

// ORMV-1-28: Ajakan & Undangan yang diterima organisasi (kartu masuk).
export const getIncomingInvitations = (params = {}) =>
  api.get('/organisasi/ajakan-masuk', { params }).then(responseData)

// ORMV-1-29: Detail satu ajakan/undangan masuk (popup).
export const getIncomingInvitationDetail = (idAjakan) =>
  api.get(`/organisasi/ajakan-masuk/${idAjakan}`).then(responseData)

// ORMV-1-29: Jawab ajakan/undangan (terima/tolak + alasan bila menolak).
export const answerInvitation = (idAjakan, keputusan, alasanPenolakan) =>
  api.post(`/organisasi/ajakan-masuk/${idAjakan}/jawab`, {
    keputusan,
    ...(alasanPenolakan ? { alasan_penolakan: alasanPenolakan } : {}),
  }).then(responseData)
