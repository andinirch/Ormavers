import { useMemo, useState } from 'react'
import { PageIntro } from './DashboardPage.jsx'
import { useAdminData } from '../../services/useAdminData.jsx'
import AdminIcon from '../../components/admin/AdminIcon.jsx'
import { RoleForm } from '../../components/adminForms.jsx'
import '../../styles/roles-page.css'

const ROLE_LABELS = {
  super_admin: 'super admin',
  akun_hmj_ukm: 'hmj/ukm',
  akun_bem: 'bem',
}

const ROLE_VARIANTS = {
  super_admin: 'super-admin',
  akun_hmj_ukm: 'hmj-ukm',
  akun_bem: 'bem',
}

function roleVariant(name) {
  return ROLE_VARIANTS[name] || name.replace(/[\s_/]+/g, '-').toLowerCase()
}

function roleLabel(name) {
  return ROLE_LABELS[name] || name.replace(/_/g, ' ')
}

function RolesPage() {
  const { roles, deleteRole } = useAdminData()
  const [query, setQuery] = useState('')
  const [editing, setEditing] = useState(null)
  const filtered = useMemo(() => roles.filter((role) => `${role.name} ${role.permissions}`.toLowerCase().includes(query.toLowerCase())), [roles, query])

  return (
    <main className="admin-page admin-roles-page">
      <PageIntro eyebrow="AKSES & KEAMANAN • SUPER ADMIN" title="Role" description="Kelola role dan hak akses (permission) pengguna Ormavers." />
      <div className="admin-summary-grid admin-role-summary">
        <Stat label="TOTAL ROLE AKTIF" value="5" />
        <Stat label="TOTAL HAK AKSES" value="28" />
        <Stat label="ROLE KHUSUS SISTEM" value="1" />
      </div>
      <section className="admin-panel admin-role-panel">
        <div className="admin-panel-heading">
          <div>
            <h2>Daftar Role <span className="admin-count-pill">5 role</span></h2>
            <p>Seluruh role yang terdaftar di sistem</p>
          </div>
          <div className="admin-role-tools">
            <div className="admin-input-wrap admin-role-search">
              <AdminIcon name="search" />
              <input placeholder="Cari nama role / izin..." value={query} onChange={(event) => setQuery(event.target.value)} />
            </div>
          </div>
        </div>
        <div className="admin-table-wrap">
          <table className="admin-role-table">
            <thead>
              <tr>
                <th>#</th>
                <th>ROLE</th>
                <th>PERMISSION</th>
                <th>DIBUAT</th>
                <th>AKSI</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((role, index) => {
                const permissions = role.permissions.split(',').map((permission) => permission.trim()).filter(Boolean)
                const visiblePermissions = permissions.slice(0, 4)
                const remainingPermissions = permissions.length - visiblePermissions.length
                const variant = roleVariant(role.name)
                return (
                  <tr key={role.id}>
                    <td>#{index + 1}</td>
                    <td>
                      <div className="admin-role-cell">
                        <span className={`admin-role-badge admin-role-badge--${variant}`}>● {roleLabel(role.name)}</span>
                      </div>
                    </td>
                    <td>
                      <div className={`admin-chip-list${variant === 'bem' ? ' admin-chip-list--bem' : ''}`}>
                        {visiblePermissions.map((permission) => (
                          <code className="admin-code" key={permission}>{permission}</code>
                        ))}
                        {remainingPermissions > 0 && (
                          <code className="admin-code admin-code--more">+{remainingPermissions} lainnya</code>
                        )}
                        {permissions.length === 0 && (
                          <em className="admin-protected-description">Tidak ada permission</em>
                        )}
                      </div>
                    </td>
                    <td><code className="admin-role-date">01 Okt 2026</code></td>
                    <td>
                      {role.name === 'super_admin' ? (
                        <span className="admin-system-badge admin-role-locked-badge">
                          <AdminIcon name="lock" size={12} /> Terkunci
                        </span>
                      ) : (
                        <div className="admin-icon-actions">
                          <button onClick={() => setEditing(role)} aria-label="Edit role">
                            <AdminIcon name="edit" />
                          </button>
                          <button
                            className="admin-role-delete-action"
                            onClick={() => window.confirm(`Hapus role ${role.name}?`) && deleteRole(role.id)}
                            aria-label="Hapus role"
                            title="Hapus role"
                          >
                            <AdminIcon name="trash" />
                          </button>
                        </div>
                      )}
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
        <footer className="admin-policy-footer">
          <AdminIcon name="lock" size={14} />
          <span>Role <b>super admin</b> dikunci dan tidak dapat diubah atau dihapus untuk menjaga integritas sistem.</span>
        </footer>
      </section>
      {editing && <RoleForm item={editing} onClose={() => setEditing(null)} />}
    </main>
  )
}

function Stat({ label, value }) { return <article className="admin-stat-card admin-role-stat-card"><div><span>{label}</span><strong>{value}</strong></div></article> }
export default RolesPage
