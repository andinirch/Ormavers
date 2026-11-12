/* eslint-disable react-hooks/set-state-in-effect */
import { useCallback, useEffect, useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext.jsx'
import { getNotifications, markAllNotificationsRead, markNotificationRead } from '../../api/notifications.js'

const titles = { ajakan: 'Ajakan kolaborasi baru', undangan: 'Undangan event', jawaban: 'Undangan diterima', perubahan_event: 'Event diperbarui', penghapusan_event: 'Event dihapus', pengingat_event: 'Pengingat event H-7' }
const icons = { ajakan: 'users', undangan: 'mail', jawaban: 'check-circle', perubahan_event: 'calendar', penghapusan_event: 'trash', pengingat_event: 'clock' }

function relativeTime(value) {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000))
  if (seconds < 60) return `${seconds || 1} detik lalu`
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes} menit lalu`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours} jam lalu`
  return `${Math.floor(hours / 24)} hari lalu`
}

function group(value) {
  const date = new Date(value)
  return date.toDateString() === new Date().toDateString() ? 'today' : 'earlier'
}

function resolveTargetUrl(item) {
  const target = item.target_url || ''
  if (target.startsWith('/events/')) return target.replace('/events/', '/jelajah-event/')
  if (target === '/organisasi/event' || target.startsWith('/organisasi/event/')) return '/verifikasi/kelola-event'
  if (target.startsWith('/organisasi/ajakan-masuk')) return '/verifikasi/kelola-event'
  return target
}

function normalize(item) {
  return {
    id: item.id ?? item.id_notifikasi,
    icon: icons[item.jenis] || 'bell',
    title: titles[item.jenis] || 'Pembaruan event',
    text: item.pesan || '',
    time: relativeTime(item.dibuat_pada),
    read: Boolean(item.sudah_baca),
    group: group(item.dibuat_pada),
    targetUrl: resolveTargetUrl(item),
  }
}

export default function useNotifications() {
  const navigate = useNavigate()
  const { pathname } = useLocation()
  const { user, roles } = useAuth()
  const [items, setItems] = useState([])
  const [unreadFromApi, setUnreadFromApi] = useState(0)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const roleNames = roles.map((role) => typeof role === 'string' ? role : role?.name).filter(Boolean)
  const organizationRole = user?.peran === 'akun_organisasi' || roleNames.includes('akun_bem') || roleNames.includes('akun_hmj_ukm')
  const supported = organizationRole && (pathname.startsWith('/verifikasi') || pathname.startsWith('/jelajah-event')) && !pathname.includes('/administrator') && !pathname.includes('/admin')

  const load = useCallback(async () => {
    if (!supported) {
      setItems([])
      setUnreadFromApi(0)
      setError('')
      return
    }
    setLoading(true)
    setError('')
    try {
      const result = await getNotifications()
      const payload = result?.data || result?.notifikasi || {}
      const list = Array.isArray(payload) ? payload : payload.notifikasi || []
      setItems(list.map(normalize))
      setUnreadFromApi(Number.isFinite(payload.unread_count) ? payload.unread_count : list.filter((item) => !item.sudah_baca).length)
    } catch (requestError) {
      setItems([])
      setUnreadFromApi(0)
      setError(requestError.response?.data?.message || 'Notifikasi gagal dimuat. Periksa endpoint backend.')
    } finally {
      setLoading(false)
    }
  }, [supported])

  useEffect(() => { load() }, [load])

  const markRead = async (item) => {
    if (!item.read) {
      setItems((current) => current.map((entry) => entry.id === item.id ? { ...entry, read: true } : entry))
      setUnreadFromApi((count) => Math.max(0, count - 1))
      try { await markNotificationRead(item.id) } catch { load() }
    }
    if (item.targetUrl) navigate(item.targetUrl)
  }

  const markAllRead = async () => {
    setItems((current) => current.map((item) => ({ ...item, read: true })))
    setUnreadFromApi(0)
    try { await markAllNotificationsRead() } catch { load() }
  }

  return { items, unread: unreadFromApi, loading, error, markRead, markAllRead, refresh: load }
}
