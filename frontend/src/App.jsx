import { Route, Routes } from 'react-router-dom'
import NotFoundPage from './pages/NotFoundPage.jsx'
import AdminShell from './layouts/AdminShell.jsx'
import DashboardPage from './pages/admin/DashboardPage.jsx'
import UsersPage from './pages/admin/UsersPage.jsx'
import RolesPage from './pages/admin/RolesPage.jsx'

function App() {
  return <Routes>
    <Route element={<AdminShell />}>
      <Route path="/admin" element={<DashboardPage />} />
      <Route path="/admin/roles" element={<RolesPage />} />
      <Route path="/admin/pengguna" element={<UsersPage />} />
    </Route>
    <Route path="*" element={<NotFoundPage />} />
  </Routes>
}
export default App
