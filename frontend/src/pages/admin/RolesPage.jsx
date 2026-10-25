import { useMemo, useState } from 'react'
import { useAuth } from '../../context/AuthContext.jsx'
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
  const { roles, permissions, deleteRole } = useAdminData()
  const { user, roles: sessionRoles, permissions: sessionPermissions } = useAuth()
  const [query, setQuery] = useState('')
  const [editing, setEditing] = useState(null)
  const [creating, setCreating] = useState(false)
  const isSuperAdmin = user?.peran === 'super_admin' || sessionRoles.some((role) => (typeof role === 'string' ? role : role?.name) === 'super_admin')
  const can = (permission) => isSuperAdmin || sessionPermissions.includes(permission)
  const filtered = useMemo(() => roles.filter((role) => `${role.name} ${role.permissions}`.toLowerCase().includes(query.toLowerCase())), [roles, query])

  return (
    <main className="admin-page admin-roles-page">
      <header className="admin-role-heading">
        <span className="admin-eyebrow">{ '\u25CF' } AKSES & KEAMANAN SUPER ADMIN</span>
        <div className="admin-role-title-row">
          <div className="admin-role-title-copy">
            <h1>Role</h1>
            <p>Kelola role dan hak akses (permission) pengguna Ormavers.</p>
          </div>
{can('create roles') && <button className="admin-button" type="button" onClick={() => setCreating(true)}>
             <AdminIcon name="plus" size={16} /> Tambah Role
           </button>}
        </div>
      </header>
      <div className="admin-summary-grid admin-role-summary">
<Stat label="TOTAL ROLE AKTIF" value={roles.length} />
         <Stat label="TOTAL HAK AKSES" value={permissions.length} />
         <Stat label="ROLE KHUSUS SISTEM" value={roles.filter((role) => role.name === 'super_admin').length} />
      </div>
      <section className="admin-panel admin-role-panel">
        <div className="admin-panel-heading">
          <div>
            <h2>Daftar Role <span className="admin-count-pill">{roles.length} role</span></h2>
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
                        <span className={`admin-role-badge admin-role-badge--${variant}`}>{'\u25CF'} {roleLabel(role.name)}</span>
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
{can('edit roles') && <button onClick={() => setEditing(role)} aria-label="Edit role">
                             <AdminIcon name="edit" />
                           </button>}
                           {can('delete roles') && <button
                             className="admin-role-delete-action"
                            onClick={() => window.confirm(`Hapus role ${role.name}?`) && deleteRole(role.id)}
                            aria-label="Hapus role"
                            title="Hapus role"
                          >
                            <AdminIcon name="trash" />
                           </button>}
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
      {creating && <RoleForm item={null} onClose={() => setCreating(false)} />}
      {editing && <RoleForm item={editing} onClose={() => setEditing(null)} />}
    </main>
  )
}

function Stat({ label, value }) { return <article className="admin-stat-card admin-role-stat-card"><div><span>{label}</span><strong>{value}</strong></div></article> }
export default RolesPage
