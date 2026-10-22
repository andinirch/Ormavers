import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from './AuthContext.jsx'

function roleName(role) {
  return typeof role === 'string' ? role : role?.name
}

export default function AccessGuard({ roles = [], permission }) {
  const { roles: sessionRoles, permissions, user } = useAuth()
  const location = useLocation()
  const availableRoles = new Set([
    user?.peran,
    ...sessionRoles.map(roleName),
  ].filter(Boolean))
  const hasRole = roles.length === 0 || roles.some((role) => availableRoles.has(role))
  const hasPermission = !permission || permissions.includes(permission)

  if (!hasRole || !hasPermission) {
    return <Navigate to="/403" replace state={{ from: location.pathname }} />
  }

  return <Outlet />
}
