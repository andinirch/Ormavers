import { Route, Routes } from 'react-router-dom'
import NotFoundPage from './pages/NotFoundPage.jsx'
import AdminShell from './layouts/AdminShell.jsx'
import DashboardPage from './pages/admin/DashboardPage.jsx'
import UsersPage from './pages/admin/UsersPage.jsx'
import RolesPage from './pages/admin/RolesPage.jsx'
import ActivityLogPage from './pages/admin/ActivityLogPage.jsx'
import PermissionsPage from './pages/admin/PermissionsPage.jsx'
import ProfilePage from './pages/admin/ProfilePage.jsx'

function App() {
  return <Routes>
    <Route element={<AdminShell />}>
      <Route path="/admin" element={<DashboardPage />} />
      <Route path="/admin/roles" element={<RolesPage />} />
      <Route path="/admin/pengguna" element={<UsersPage />} />
      <Route path="/admin/aktivitas" element={<ActivityLogPage />} />
      <Route path="/admin/permission" element={<PermissionsPage />} />
      <Route path="/admin/profil" element={<ProfilePage />} />
    </Route>
    <Route path="*" element={<NotFoundPage />} />
  </Routes>
}
export default App
