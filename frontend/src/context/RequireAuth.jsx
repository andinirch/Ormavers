import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from './AuthContext.jsx'

export default function RequireAuth() {
  const { isAuthenticated, loading } = useAuth()
  if (loading) return null
  return isAuthenticated ? <Outlet /> : <Navigate to="/login" replace />
}
