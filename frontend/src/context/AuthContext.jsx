/* eslint-disable react-refresh/only-export-components */
import { createContext, useContext, useEffect, useState } from 'react'
import { login as loginRequest, logout as logoutRequest, me } from '../api/auth.js'
import { clearToken, getToken, setToken } from '../api/api.js'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [session, setSession] = useState({ user: null, roles: [], permissions: [], loading: true })

  const applySession = (data) => {
    const next = data?.data ?? data
    setSession({
      user: next?.pengguna ?? null,
      roles: next?.roles ?? [],
      permissions: next?.permissions ?? [],
      loading: false,
    })
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
      setSession({ user: null, roles: [], permissions: [], loading: false })
    }
  }

  useEffect(() => {
    const handleUnauthorized = () => {
      clearToken()
      setSession({ user: null, roles: [], permissions: [], loading: false })
    }
    window.addEventListener('ormavers:unauthorized', handleUnauthorized)
    if (!getToken()) {
      queueMicrotask(() => setSession((current) => ({ ...current, loading: false })))
      return () => window.removeEventListener('ormavers:unauthorized', handleUnauthorized)
    }
    me().then((result) => applySession(result)).catch(() => handleUnauthorized())
    return () => window.removeEventListener('ormavers:unauthorized', handleUnauthorized)
  }, [])

  return <AuthContext.Provider value={{ ...session, isAuthenticated: Boolean(getToken()), login, logout }}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) throw new Error('useAuth harus digunakan di dalam AuthProvider')
  return context
}
