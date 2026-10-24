import { useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { useAdminData } from '../../services/useAdminData.jsx'
import AdminIcon from '../../components/admin/AdminIcon.jsx'
import PermissionForm from '../../components/PermissionForm.jsx'
import '../../styles/permission-page.css'

const MODULES = {
  'Pengguna & Role': ['view users', 'create users', 'edit users', 'delete users', 'view roles', 'create roles', 'edit roles', 'delete roles'],
  Verifikasi: ['verify bem', 'verify hmj/ukm', 'verify members'],
  'Profil & Keanggotaan': ['view profile', 'view registration status', 'view members'],
}

function moduleForPermission(permission) {
  return Object.entries(MODULES).find(([, permissions]) => permissions.includes(permission))?.[0] || 'Lainnya'
}

function PermissionsPage() {
  const { roles } = useAdminData()
  const [query, setQuery] = useState('')
  const [module, setModule] = useState('')
  const [appliedModule, setAppliedModule] = useState('')
  const [creating, setCreating] = useState(false)
  const [deleteMessage, setDeleteMessage] = useState('')
  const allPermissions = useMemo(() => [...new Set(roles.flatMap((role) => role.permissions.split(',').map((permission) => permission.trim()).filter(Boolean)))], [roles])
  const permissions = useMemo(() => allPermissions.filter((permission) => {
    const matchesQuery = permission.toLowerCase().includes(query.toLowerCase())
    const matchesModule = !appliedModule || moduleForPermission(permission) === appliedModule
    return matchesQuery && matchesModule
  }), [allPermissions, appliedModule, query])

  const resetFilters = () => {
    setQuery('')
    setModule('')
    setAppliedModule('')
  }

  const requestDelete = (permission) => {
    if (window.confirm(`Hapus permission ${permission}?`)) {
      setDeleteMessage(`API hapus permission belum tersedia. Permission "${permission}" belum dihapus.`)
    }
  }

  return <main className="admin-page admin-permission-page"><header className="admin-page-intro admin-permission-intro"><span className="admin-eyebrow">{'\u25CF'} AKSES & KEAMANAN {'\u2022'} SUPER ADMIN</span><div className="admin-permission-title-row"><h1>Permission</h1><button className="admin-button" type="button" onClick={() => setCreating(true)}><AdminIcon name="plus" size={16} /> Tambah Permission</button></div><p>Daftar hak akses sistem dan role yang menggunakannya.</p></header><section className="admin-filter-card admin-permission-filter"><div className="admin-input-wrap"><AdminIcon name="search" /><input placeholder="Cari permission..." value={query} onChange={(event) => setQuery(event.target.value)} /></div><div className="admin-permission-select"><select className="admin-permission-select__control" value={module} onChange={(event) => setModule(event.target.value)}><option value="">Semua modul</option>{Object.keys(MODULES).map((item) => <option key={item} value={item}>{item}</option>)}</select><span className="admin-select-chevron"><AdminIcon name="chevron-down" size={16} /></span></div><button className="admin-button admin-permission-apply" onClick={() => setAppliedModule(module)}><AdminIcon name="filter-funnel" size={18} />Terapkan</button><button className="admin-button admin-button--muted" onClick={resetFilters}><AdminIcon name="rotate-cw" size={18} />Reset</button></section><section className="admin-panel admin-permission-panel"><div className="admin-info-banner"><p><AdminIcon name="info" size={16} />Permission bersifat tetap dari sistem. Atur penggunaannya melalui halaman <Link to="/admin-panel/roles" className="admin-info-link">Role</Link>.</p><span>MODE BACA SAJA</span></div><div className="admin-table-wrap"><table className="admin-permission-table"><colgroup><col className="admin-permission-col-number" /><col className="admin-permission-col-name" /><col className="admin-permission-col-action" /><col /></colgroup><thead><tr><th>#</th><th>PERMISSION</th><th>AKSI</th><th /></tr></thead><tbody>{permissions.map((permission, index) => { const permissionModule = moduleForPermission(permission); return <tr key={permission}><td>{index + 1}</td><td><code className={`admin-code admin-permission-code${permissionModule === 'Verifikasi' ? ' admin-permission-code--verification' : ''}`}>{permission}</code></td><td><button className="admin-permission-delete" type="button" onClick={() => requestDelete(permission)} aria-label={`Hapus permission ${permission}`}><AdminIcon name="trash" size={16} /></button></td><td /></tr> })}</tbody></table></div><footer className="admin-policy-footer"><span>Menampilkan <b>{permissions.length}</b> dari <b>{allPermissions.length}</b> hak akses total</span></footer></section>{deleteMessage && <p className="admin-permission-preview-message" role="status">{deleteMessage}</p>}{creating && <PermissionForm onClose={() => setCreating(false)} />}</main>
}

export default PermissionsPage
