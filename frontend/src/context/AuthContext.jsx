/* eslint-disable react-refresh/only-export-components */
import { createContext, useContext, useEffect, useState } from 'react'
import { login as loginRequest, logout as logoutRequest, me } from '../api/auth.js'
import { clearToken, getToken, setToken } from '../api/api.js'

const AuthContext = createContext(null)
const LIMITED_ACCESS_KEY = 'ormavers_limited_access'

export function AuthProvider({ children }) {
  const [session, setSession] = useState({ user: null, roles: [], permissions: [], aksesTerbatas: false, loading: true })

  const applySession = (data, preserveLimitedAccess = false) => {
    const next = data?.data ?? data
    const aksesTerbatas = next?.akses_terbatas == null
      ? preserveLimitedAccess
      : Boolean(next.akses_terbatas)
    setSession({
      user: next?.pengguna ?? null,
      roles: next?.roles ?? [],
      permissions: next?.permissions ?? [],
      aksesTerbatas,
      loading: false,
    })
    if (aksesTerbatas) localStorage.setItem(LIMITED_ACCESS_KEY, 'true')
    else localStorage.removeItem(LIMITED_ACCESS_KEY)
    return next
  }

  const login = async (credentials) => {
    const result = await loginRequest(credentials)
    setToken(result.data.token)
    applySession(result.data)
    return result
  }

  const logout = async () => {
    try {
      if (getToken()) await logoutRequest()
    } finally {
      clearToken()
      localStorage.removeItem(LIMITED_ACCESS_KEY)
      setSession({ user: null, roles: [], permissions: [], aksesTerbatas: false, loading: false })
    }
  }

  const refreshUser = async () => {
    try {
      const result = await me()
      applySession(result, Boolean(localStorage.getItem(LIMITED_ACCESS_KEY)))
    } catch {
      // Silent fail - user tetap login dengan data lama
      // Token masih valid, jadi tidak perlu logout
    }
  }

  useEffect(() => {
    const handleUnauthorized = () => {
      clearToken()
      localStorage.removeItem(LIMITED_ACCESS_KEY)
      setSession({ user: null, roles: [], permissions: [], aksesTerbatas: false, loading: false })
    }
    window.addEventListener('ormavers:unauthorized', handleUnauthorized)
    if (!getToken()) {
      queueMicrotask(() => setSession((current) => ({ ...current, loading: false })))
      return () => window.removeEventListener('ormavers:unauthorized', handleUnauthorized)
    }
    const limitedAccess = localStorage.getItem(LIMITED_ACCESS_KEY) === 'true'
    me()
      .then((result) => applySession(result, limitedAccess))
      .catch(() => handleUnauthorized())
    return () => window.removeEventListener('ormavers:unauthorized', handleUnauthorized)
  }, [])

  return <AuthContext.Provider value={{ ...session, isAuthenticated: Boolean(getToken()), login, logout, refreshUser }}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) throw new Error('useAuth harus digunakan di dalam AuthProvider')
  return context
}
