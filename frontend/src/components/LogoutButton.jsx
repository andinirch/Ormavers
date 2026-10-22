import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'
import AdminIcon from './admin/AdminIcon.jsx'
import '../styles/logout-button.css'

export default function LogoutButton() {
  const navigate = useNavigate()
  const { logout } = useAuth()
  const [loading, setLoading] = useState(false)

  const handleLogout = async () => {
    if (loading) return
    setLoading(true)
    try {
      await logout()
      navigate('/login', { replace: true })
    } finally {
      setLoading(false)
    }
  }

  return (
    <button className="platform-logout" type="button" onClick={handleLogout} disabled={loading}>
      <AdminIcon name="logout" />
      <span>Keluar</span>
    </button>
  )
}
