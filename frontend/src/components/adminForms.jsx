import { useState } from 'react'
import { useAdminData } from '../services/useAdminData.jsx'

function FormDialog({ type, item, onClose }) {
  const { roles, organizations, createUser, updateUser, createRole, updateRole } = useAdminData()
  const isUser = type === 'user'
  const [form, setForm] = useState(isUser ? { name: item?.name || '', roleId: item?.roleId || roles[0]?.id || '', organizationId: item?.organizationId || organizations[0]?.id || '' } : { name: item?.name || '', permissions: item?.permissions || '' })
  const [error, setError] = useState('')
  const change = (key, value) => setForm((current) => ({ ...current, [key]: value }))
  const submit = (event) => {
    event.preventDefault()
    if (!form.name.trim()) return setError('Name wajib diisi.')
    if (isUser && !form.roleId) return setError('Role wajib dipilih.')
    if (item) {
      if (isUser) updateUser(item.id, form)
      else updateRole(item.id, form)
    } else if (isUser) createUser(form)
    else createRole(form)
    onClose()
  }
  return <div className="admin-dialog-backdrop" role="presentation" onMouseDown={(event) => event.target === event.currentTarget && onClose()}>
    <form className="admin-dialog" onSubmit={submit}>
      <div className="admin-dialog-heading"><h2>{item ? 'Edit' : 'Create'} {isUser ? 'Pengguna' : 'Role'}</h2><button type="button" className="admin-close" onClick={onClose} aria-label="Tutup">×</button></div>
      <label>Name<input value={form.name} onChange={(event) => change('name', event.target.value)} /></label>
      {isUser ? <><label>Role<select value={form.roleId} onChange={(event) => change('roleId', event.target.value)}>{roles.map((role) => <option key={role.id} value={role.id}>{role.name}</option>)}</select></label><label>Organisasi<select value={form.organizationId} onChange={(event) => change('organizationId', event.target.value)}>{organizations.map((organization) => <option key={organization.id} value={organization.id}>{organization.name}</option>)}</select></label></> : <label>Permission<textarea value={form.permissions} onChange={(event) => change('permissions', event.target.value)} rows="4" /></label>}
      {error && <p className="admin-form-error" role="alert">{error}</p>}
      <div className="admin-dialog-actions"><button type="button" className="admin-button admin-button--outline" onClick={onClose}>Cancel</button><button className="admin-button">Save</button></div>
    </form>
  </div>
}

export function UserForm(props) { return <FormDialog {...props} type="user" /> }
export function RoleForm(props) { return <FormDialog {...props} type="role" /> }
