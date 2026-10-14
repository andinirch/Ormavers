import { NavLink, Outlet } from 'react-router-dom'
import { AdminDataProvider } from '../services/adminData.jsx'
import '../styles/admin.css'

function AdminShell() {
  return <AdminDataProvider>
    <div className="admin-shell">
      <header className="admin-navbar">
        <NavLink to="/admin" className="admin-brand" aria-label="Ormavers Dashboard"><span className="admin-brand-mark" /></NavLink>
        <nav className="admin-nav" aria-label="Navigasi admin">
          <NavLink to="/admin" end>Dashboard</NavLink>
          <NavLink to="/admin/roles">Roles</NavLink>
          <NavLink to="/admin/pengguna">Pengguna</NavLink>
        </nav>
        <div className="admin-account">Super Admin (super admin)<span aria-hidden="true">▾</span></div>
      </header>
      <Outlet />
    </div>
  </AdminDataProvider>
}

export default AdminShell
