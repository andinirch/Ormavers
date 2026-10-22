const LOGOUT_UNAVAILABLE = 'Logout backend belum tersedia.'

export function logout() {
  return Promise.resolve({
    ok: false,
    unavailable: true,
    message: LOGOUT_UNAVAILABLE,
  })
}
