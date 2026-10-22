import { useState } from 'react'
import { useAdminData } from '../services/useAdminData.jsx'
import AdminIcon from './admin/AdminIcon.jsx'

const ROLE_BAWAAN = ['super_admin', 'administrator', 'akun_bem', 'akun_hmj_ukm', 'anggota']
const PERMISSION_MODULES = [
  { name: 'Pengguna & Role', icon: 'users', permissions: ['view users', 'create users', 'edit users', 'delete users', 'view roles', 'create roles', 'edit roles', 'delete roles'] },
  { name: 'Verifikasi', icon: 'check', permissions: ['verify bem', 'verify hmj/ukm', 'verify members'] },
  { name: 'Profil & Keanggotaan', icon: 'profile', permissions: ['view profile', 'view registration status', 'view members'] },
]

function RoleForm({ item, onClose }) {
  const { createRole, updateRole, permissions = [] } = useAdminData()
  const [name, setName] = useState(item?.name || '')
  const [selected, setSelected] = useState(() => new Set(item?.permissions?.split(',').map((permission) => permission.trim()) || []))
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const availablePermissions = permissions.map((permission) => permission.name ?? permission)
  const groupedPermissions = PERMISSION_MODULES.map((module) => ({ ...module, permissions: module.permissions.filter((permission) => availablePermissions.includes(permission)) })).filter((module) => module.permissions.length > 0)
  const ungroupedPermissions = availablePermissions.filter((permission) => !PERMISSION_MODULES.some((module) => module.permissions.includes(permission)))
  if (ungroupedPermissions.length) groupedPermissions.push({ name: 'Lainnya', icon: 'sliders', permissions: ungroupedPermissions })
  const isBawaan = ROLE_BAWAAN.includes(item?.name)
  const toggle = (permission) => setSelected((current) => { const next = new Set(current); next.has(permission) ? next.delete(permission) : next.add(permission); return next })
  const selectAll = (groupPermissions) => setSelected((current) => { const next = new Set(current); const all = groupPermissions.every((permission) => next.has(permission)); groupPermissions.forEach((permission) => all ? next.delete(permission) : next.add(permission)); return next })
  const submit = async (event) => {
    event.preventDefault()
    if (submitting) return
    if (!name.trim()) return setError('Nama role wajib diisi.')
    setError('')
    setSubmitting(true)
    try {
      if (item) {
        await updateRole(item.id, { name: name.trim(), permissions: [...selected].join(', ') })
      } else {
        await createRole({ name: name.trim(), permissions: [...selected].join(', ') })
      }
      onClose()
    } catch (err) {
      setError(err?.response?.data?.message || err?.message || 'Gagal menyimpan role.')
    } finally {
      setSubmitting(false)
    }
  }
  return (
    <div className="admin-dialog-backdrop admin-role-dialog-backdrop" role="presentation" onMouseDown={(event) => event.target === event.currentTarget && !submitting && onClose()}>
      <form className="admin-dialog admin-role-dialog" onSubmit={submit}>
        <header className="admin-dialog-heading">
          <div className="admin-role-dialog-title">
            <span className="admin-dialog-icon"><AdminIcon name="shield" /></span>
            <div>
              <h2>{item ? 'Edit Role' : 'Tambah Role'}</h2>
              <p>Tentukan nama role dan permission yang diizinkan untuk hak akses entitas ini.</p>
            </div>
          </div>
          <button type="button" className="admin-close" onClick={onClose} disabled={submitting} aria-label="Tutup">
            <AdminIcon name="close" />
          </button>
        </header>
        <label>
          Nama Role
          <input value={name} disabled={isBawaan || submitting} onChange={(event) => setName(event.target.value)} />
          <small><AdminIcon name="info" size={14} /> {isBawaan ? 'Nama role bawaan tidak dapat diubah.' : <>Gunakan format huruf kecil tanpa spasi khusus (contoh: <code>bem</code>, <code>hmj/ukm</code>).</>}</small>
        </label>
        <div className="admin-permission-editor-heading">
          <strong><AdminIcon name="sliders" size={16} /> Hak Akses (Permission)</strong>
          <span>Pilih wewenang fungsional</span>
        </div>
        <div className="admin-permission-groups">
          {groupedPermissions.map(({ name: module, icon, permissions: groupPermissions }) => (
            <section key={module}>
              <header>
                <strong><AdminIcon name={icon} size={14} /> MODUL: {module.toUpperCase()}</strong>
                <button type="button" disabled={submitting} onClick={() => selectAll(groupPermissions)}>Pilih semua</button>
              </header>
              <div>
                {groupPermissions.map((permission) => (
                  <label className={selected.has(permission) ? 'is-selected' : ''} key={permission}>
                    <input type="checkbox" disabled={submitting} checked={selected.has(permission)} onChange={() => toggle(permission)} />
                    <code>{permission}</code>
                    {selected.has(permission) && <b>AKTIF</b>}
                  </label>
                ))}
              </div>
            </section>
          ))}
        </div>
        {error && <p className="admin-form-error">{error}</p>}
        <footer className="admin-dialog-actions">
          <span className="admin-count-pill"><AdminIcon name="check" size={12} /> {selected.size} permission dipilih</span>
          <button type="button" className="admin-button admin-button--muted" onClick={onClose} disabled={submitting}>Batal</button>
          <button className="admin-button" disabled={submitting}>
            <AdminIcon name="save" size={15} /> {submitting ? 'Menyimpan...' : 'Simpan Role'}
          </button>
        </footer>
      </form>
    </div>
  )
}

function UserForm({ item, onClose }) {
  const { roles, organizations, createUser, updateUser } = useAdminData()
  const [form, setForm] = useState({
    name: item?.name || '',
    username: '',
    password: '',
    roleId: roles.find((role) => role.name === item?.role)?.id || item?.roleId || roles[0]?.id || '',
    organizationId: item?.organizationId || '',
  })
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const change = (key, value) => setForm((current) => ({ ...current, [key]: value }))

  const submit = async (event) => {
    event.preventDefault()
    if (submitting) return
    setError('')
    setSubmitting(true)
    try {
      if (item) {
        await updateUser(item.id, form)
      } else {
        await createUser({ name: form.name, username: form.username, password: form.password })
      }
      onClose()
    } catch (err) {
      setError(err?.response?.data?.message || err?.message || 'Gagal menyimpan data pengguna.')
    } finally {
      setSubmitting(false)
    }
  }

  const organizationName = organizations.find((organization) => organization.id === form.organizationId)?.name || item?.organizationName || item?.organization || '—'

  return (
    <div className="admin-dialog-backdrop" role="presentation" onMouseDown={(event) => event.target === event.currentTarget && !submitting && onClose()}>
      <form className="admin-dialog" onSubmit={submit}>
        <div className="admin-dialog-heading">
          <h2>{item ? 'Edit Pengguna' : 'Tambah Administrator'}</h2>
          <button type="button" className="admin-close" onClick={onClose} disabled={submitting}>
            <AdminIcon name="close" />
          </button>
        </div>
        {error && <p className="admin-form-error" style={{ margin: '0 1.5rem 0.75rem' }}>{error}</p>}
        <label>
          Pengguna
          <input value={form.name} placeholder={!item ? 'Masukkan nama Administrator' : undefined} disabled={submitting} onChange={(event) => change('name', event.target.value)} required />
        </label>
        {!item && (
          <>
            <label>
              Username
              <input value={form.username} placeholder="Masukkan username" disabled={submitting} onChange={(event) => change('username', event.target.value)} required />
            </label>
            <label>
              Password
              <input type="password" value={form.password} placeholder="Masukkan password" disabled={submitting} onChange={(event) => change('password', event.target.value)} required />
            </label>
          </>
        )}
        {item && (
          <label>
            Role
            <span className="admin-user-role-control">
              <select className="admin-user-role-select" value={form.roleId} disabled={submitting} onChange={(event) => change('roleId', event.target.value)}>
                {roles.map((role) => (
                  <option key={role.id} value={role.id}>
                    {role.name}
                  </option>
                ))}
              </select>
              <AdminIcon name="chevron-down" size={16} />
            </span>
          </label>
        )}
        {item && (
          <label>
            Nama Organisasi
            <input value={organizationName} readOnly />
          </label>
        )}
        <div className="admin-dialog-actions">
          <button type="button" className="admin-button admin-button--muted" onClick={onClose} disabled={submitting}>
            Batal
          </button>
          <button className="admin-button" disabled={submitting}>
            {submitting ? 'Menyimpan...' : 'Simpan'}
          </button>
        </div>
      </form>
    </div>
  )
}

export { UserForm, RoleForm }
