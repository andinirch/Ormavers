import { useEffect, useState } from 'react'
import { AdminDataContext } from './AdminDataContext.jsx'
import { getPermissions, getRoles, createRole as createRoleRequest, updateRole as updateRoleRequest, deleteRole as deleteRoleRequest } from '../api/adminPanel.js'
import { listUsers, storeAdministrator, deactivateAdministrator } from '../api/superadmin.js'

const normalizeRole = (role) => ({
  ...role,
  permissions: Array.isArray(role.permissions) ? role.permissions.join(', ') : (role.permissions || ''),
  isProtected: ['super_admin', 'administrator', 'akun_bem', 'akun_hmj_ukm', 'anggota'].includes(role.name),
})

const normalizeAdministrator = (item) => {
  const name = item.name ?? item.nama ?? 'Pengguna'
  return {
    ...item,
    id: item.id ?? item.id_pengguna,
    name,
    email: item.email ?? item.username,
    role: item.role ?? item.peran,
    roleId: item.roleId ?? item.peran,
    status: item.status ?? item.status_akun ?? 'aktif',
    initials: name.split(/\s+/).map((part) => part[0]).join('').slice(0, 2).toUpperCase(),
    lastLoginAt: item.lastLoginAt ?? item.login_terakhir,
  }
}

export function AdminDataProvider({ children }) {
  const [roles, setRoles] = useState([])
  const [users, setUsers] = useState([])
  const [permissions, setPermissions] = useState([])
  const [loading, setLoading] = useState(true)

  const refresh = async () => {
    const [roleResult, userResult, permissionResult] = await Promise.allSettled([getRoles(), listUsers(), getPermissions()])
    if (roleResult.status === 'fulfilled') setRoles((roleResult.value.data?.roles ?? []).map(normalizeRole))
    if (userResult.status === 'fulfilled') setUsers((userResult.value.data?.users ?? []).map(normalizeAdministrator))
    if (permissionResult.status === 'fulfilled') setPermissions(permissionResult.value.data?.permissions ?? [])
    setLoading(false)
  }

  useEffect(() => { queueMicrotask(refresh) }, [])

  const value = {
    users,
    roles,
    permissions,
    organizations: [],
    loading,
    createRole: async (input) => { await createRoleRequest({ ...input, permissions: input.permissions.split(',').map((item) => item.trim()).filter(Boolean) }); await refresh() },
    updateRole: async (id, input) => { await updateRoleRequest(id, { ...input, permissions: input.permissions.split(',').map((item) => item.trim()).filter(Boolean) }); await refresh() },
    deleteRole: async (id) => { await deleteRoleRequest(id); await refresh() },
    createUser: async (input) => { await storeAdministrator({ nama: input.name, username: input.username, password: input.password }); await refresh() },
    updateUser: async () => {},
    deleteUser: async (id) => { await deactivateAdministrator(id); await refresh() },
  }

  return <AdminDataContext.Provider value={value}>{children}</AdminDataContext.Provider>
}
