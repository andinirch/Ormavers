import { Navigate, Route, Routes } from 'react-router-dom'
import AppShell from './layouts/AppShell.jsx'
import RegistrationStatusPage from './pages/RegistrationStatusPage.jsx'
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
import OrganizationProfilePage from './pages/OrganizationProfilePage.jsx'
import VerificationShell from './layouts/VerificationShell.jsx'
import VerifikasiAdministratorPage from './pages/verifikasi/VerifikasiAdministratorPage.jsx'
import VerifikasiBemPageNew from './pages/verifikasi/VerifikasiBemPage.jsx'
import VerifikasiHmjPage from './pages/verifikasi/VerifikasiHmjPage.jsx'
import RequireAuth from './context/RequireAuth.jsx'
import AccessGuard from './context/AccessGuard.jsx'
import ForbiddenPage from './pages/ForbiddenPage.jsx'

function App() {
  return (
    <Routes>
      <Route element={<AppShell />}>
        <Route path="/status-registrasi" element={<RegistrationStatusPage />} />
      </Route>
      <Route element={<AuthLayout />}>
        <Route path="/" element={<Navigate to="/login" replace />} />
        <Route path="/login" element={<LoginPage />} />
        <Route path="/registrasi/bem" element={<RegisterBemPage />} />
        <Route path="/registrasi/hmj-ukm" element={<RegisterHmjUkmPage />} />
        <Route path="/registrasi/anggota" element={<RegisterAnggotaPage />} />
        <Route path="/403" element={<ForbiddenPage />} />
        <Route path="*" element={<NotFoundPage />} />
      </Route>
      <Route element={<RequireAuth />}>
        <Route element={<AdminShell />}>
          <Route element={<AccessGuard roles={['administrator']} />}>
            <Route path="/admin" element={<Navigate to="/admin/verifikasi-bem" replace />} />
            <Route path="/admin/verifikasi-bem" element={<VerifikasiAdministratorPage />} />
            <Route path="/admin/profil" element={<ProfilePage />} />
          </Route>
          <Route element={<AccessGuard roles={['super_admin']} />}>
            <Route path="/superadmin" element={<DashboardPage />} />
            <Route path="/superadmin/pengguna" element={<UsersPage />} />
            <Route path="/admin-panel/activities" element={<ActivityLogPage />} />
          </Route>
          <Route element={<AccessGuard permission="view roles" />}>
            <Route path="/admin-panel/roles" element={<RolesPage />} />
            <Route path="/admin-panel/permission" element={<PermissionsPage />} />
          </Route>
          <Route path="/admin/pengguna" element={<Navigate to="/superadmin/pengguna" replace />} />
          <Route path="/admin/aktivitas" element={<Navigate to="/admin-panel/activities" replace />} />
          <Route path="/superadmin/aktivitas" element={<Navigate to="/admin-panel/activities" replace />} />
          <Route path="/admin/roles" element={<Navigate to="/admin-panel/roles" replace />} />
          <Route path="/admin/permission" element={<Navigate to="/admin-panel/permission" replace />} />
          <Route path="/superadmin/roles" element={<Navigate to="/admin-panel/roles" replace />} />
          <Route path="/superadmin/permission" element={<Navigate to="/admin-panel/permission" replace />} />
          <Route path="/admin/superadmin" element={<Navigate to="/superadmin" replace />} />
        </Route>
        <Route element={<AccessGuard roles={['anggota', 'member']} />}>
          <Route path="/profil" element={<OrganizationProfilePage />} />
        </Route>
        <Route path="/verifikasi" element={<VerificationShell />}>
          <Route index element={<Navigate to="administrator" replace />} />
          <Route element={<AccessGuard roles={['administrator']} />}>
            <Route path="administrator" element={<Navigate to="/admin/verifikasi-bem" replace />} />
          </Route>
          <Route element={<AccessGuard roles={['akun_bem', 'akun_organisasi']} />}>
            <Route path="bem" element={<VerifikasiBemPageNew />} />
            <Route path="profil" element={<OrganizationProfilePage />} />
          </Route>
          <Route element={<AccessGuard roles={['akun_hmj_ukm']} />}>
            <Route path="hmj" element={<VerifikasiHmjPage />} />
            <Route path="profil-hmj" element={<OrganizationProfilePage />} />
          </Route>
        </Route>
      </Route>
    </Routes>

  )
}
export default App
