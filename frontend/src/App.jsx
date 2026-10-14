import { Navigate, Route, Routes } from 'react-router-dom'
import NotFoundPage from './pages/NotFoundPage.jsx'
import VerificationShell from './layouts/VerificationShell.jsx'
import AdminShell from './layouts/AdminShell.jsx'
import DashboardPage from './pages/admin/DashboardPage.jsx'
import UsersPage from './pages/admin/UsersPage.jsx'
import RolesPage from './pages/admin/RolesPage.jsx'
import BemVerificationPage from './pages/BemVerificationPage.jsx'
import BemOrganizationVerificationPage from './pages/BemOrganizationVerificationPage.jsx'
import HmjUkmVerificationPage from './pages/HmjUkmVerificationPage.jsx'

function App() {
  return <Routes>
    <Route element={<AdminShell />}>
      <Route path="/admin" element={<DashboardPage />} />
      <Route path="/admin/roles" element={<RolesPage />} />
      <Route path="/admin/pengguna" element={<UsersPage />} />
    </Route>
    <Route element={<VerificationShell />}>
      <Route index element={<Navigate to="/verifikasi/administrator" replace />} />
      <Route path="/verifikasi/administrator" element={<BemVerificationPage />} />
      <Route path="/verifikasi/bem" element={<BemOrganizationVerificationPage />} />
      <Route path="/verifikasi/hmj-ukm" element={<HmjUkmVerificationPage />} />
    </Route>
    <Route path="*" element={<NotFoundPage />} />
  </Routes>
}
export default App
