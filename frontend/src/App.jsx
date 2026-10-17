import { Navigate, Route, Routes } from 'react-router-dom'
import AuthLayout from './components/AuthLayout.jsx'
import LoginPage from './pages/LoginPage.jsx'
import RegisterBemPage from './pages/RegisterBemPage.jsx'
import RegisterHmjUkmPage from './pages/RegisterHmjUkmPage.jsx'
import RegisterAnggotaPage from './pages/RegisterAnggotaPage.jsx'
import NotFoundPage from './pages/NotFoundPage.jsx'
import AdminShell from './layouts/AdminShell.jsx'
import DashboardPage from './pages/admin/DashboardPage.jsx'
import UsersPage from './pages/admin/UsersPage.jsx'
import RolesPage from './pages/admin/RolesPage.jsx'
import ActivityLogPage from './pages/admin/ActivityLogPage.jsx'
import PermissionsPage from './pages/admin/PermissionsPage.jsx'
import ProfilePage from './pages/admin/ProfilePage.jsx'
import VerifikasiBemPage from './pages/admin/VerifikasiBemPage.jsx'
import RequireAuth from './context/RequireAuth.jsx'

function App() {
  return (
    <Routes>
      <Route element={<AuthLayout />}>
        <Route path="/" element={<Navigate to="/login" replace />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/registrasi/bem" element={<RegisterBemPage />} />
        <Route path="/registrasi/hmj-ukm" element={<RegisterHmjUkmPage />} />
        <Route path="/registrasi/anggota" element={<RegisterAnggotaPage />} />
        <Route path="*" element={<NotFoundPage />} />
      </Route>
      <Route element={<RequireAuth />}>
        <Route element={<AdminShell />}>
      <Route path="/admin" element={<DashboardPage />} />
      <Route path="/admin/roles" element={<RolesPage />} />
      <Route path="/admin/pengguna" element={<UsersPage />} />
      <Route path="/admin/aktivitas" element={<ActivityLogPage />} />
      <Route path="/admin/permission" element={<PermissionsPage />} />
      <Route path="/admin/verifikasi-bem" element={<VerifikasiBemPage />} />
      <Route path="/admin/profil" element={<ProfilePage />} />
        </Route>
      </Route>
    </Routes>

  )
}
export default App
