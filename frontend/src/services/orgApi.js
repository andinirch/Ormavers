// TODO(kontrak-be): endpoint, metode HTTP, dan bentuk respons daftar BEM
// terverifikasi belum dikonfirmasi backend. Bentuk `data` di sini adalah
// bentuk normalisasi UI: [{ value, label }].
export async function getVerifiedBem() {
  return { data: [], unavailable: true }
}

// TODO(kontrak-be): endpoint/mechanisme daftar HMJ/UKM terverifikasi
// berdasarkan BEM induk belum dikonfirmasi backend. Pemanggil mengirimkan
// id BEM induk sebagai argumen.
export async function getVerifiedHmjUkm() {
  return { data: [], unavailable: true }
}
