import { useEffect, useState } from 'react'
import { AdminDataContext } from './AdminDataContext.jsx'
import { getPermissions, createPermission, updatePermission, deletePermission, getRoles, createRole as createRoleRequest, updateRole as updateRoleRequest, deleteRole as deleteRoleRequest } from '../api/adminPanel.js'
import { listUsers, storeAdministrator, updateAdministratorStatus } from '../api/superadmin.js'

const ROLE_BAWAAN = ['super_admin', 'administrator', 'akun_bem', 'akun_hmj_ukm', 'anggota']

const normalizeRole = (role) => ({
  ...role,
  permissions: Array.isArray(role.permissions) ? role.permissions.join(', ') : (role.permissions || ''),
  isProtected: role.name === 'super_admin',
  isBawaan: ROLE_BAWAAN.includes(role.name),
})

const normalizeUser = (item) => {
   const name = item.name ?? item.nama ?? 'Pengguna'
   return {
     ...item,
     id: item.id ?? item.id_pengguna,
     name,
     email: item.email ?? item.username,
     role: item.role ?? item.peran,
     roleId: item.roleId ?? item.peran,
     status: item.status ?? item.status_akun ?? 'aktif',
     organizationId: item.organisasi_id ?? null,
     organizationName: item.organisasi,
     initials: name.split(/\s+/).map((part) => part[0]).join('').slice(0, 2).toUpperCase(),
     lastLoginAt: item.lastLoginAt ?? item.login_terakhir,
   }
 }

export function AdminDataProvider({ children }) {
   const [roles, setRoles] = useState([])
   const [users, setUsers] = useState([])
   const [usersMeta, setUsersMeta] = useState({ total: 0, per_page: 10, current_page: 1, last_page: 1 })
   const [usersSummary, setUsersSummary] = useState({})
   const [permissions, setPermissions] = useState([])
   const [loading, setLoading] = useState(true)

   const refresh = async (userParams = {}) => {
     const [roleResult, userResult, permissionResult] = await Promise.allSettled([getRoles(), listUsers(userParams), getPermissions()])
     if (roleResult.status === 'fulfilled') setRoles((roleResult.value.data?.roles ?? []).map(normalizeRole))
     if (userResult.status === 'fulfilled') {
       setUsers((userResult.value.data?.users ?? []).map(normalizeUser))
       setUsersMeta(userResult.value.data?.meta ?? {})
       setUsersSummary(userResult.value.data?.ringkasan ?? {})
     }
     if (permissionResult.status === 'fulfilled') setPermissions(permissionResult.value.data?.permissions ?? [])
     setLoading(false)
   }

   useEffect(() => { queueMicrotask(refresh) }, [])

   const value = {
     users,
     usersMeta,
     usersSummary,
     roles,
     permissions,
     organizations: [],
     loading,
     refreshUsers: refresh,
    createRole: async (input) => {
      const result = await createRoleRequest({
        ...input,
        permissions: input.permissions.split(',').map((item) => item.trim()).filter(Boolean),
      })
      await refresh()
      return result
    },
    updateRole: async (id, input) => {
      const result = await updateRoleRequest(id, {
        ...input,
        permissions: input.permissions.split(',').map((item) => item.trim()).filter(Boolean),
      })
      await refresh()
      return result
    },
    deleteRole: async (id) => {
      const result = await deleteRoleRequest(id)
      await refresh()
      return result
    },
    createPermission: async (data) => {
      const result = await createPermission(data)
      await refresh()
      return result
    },
    updatePermission: async (id, data) => {
      const result = await updatePermission(id, data)
      await refresh()
      return result
    },
    deletePermission: async (id) => {
      const result = await deletePermission(id)
      await refresh()
      return result
    },
     createUser: async (input) => {
       const result = await storeAdministrator({
         nama: input.name,
         username: input.username,
         password: input.password,
       })
       await refresh()
       return result
     },
      updateUser: async (id, status) => {
        const result = await updateAdministratorStatus(id, status)
        await refresh()
        return result
      },
  }

  return <AdminDataContext.Provider value={value}>{children}</AdminDataContext.Provider>
}
