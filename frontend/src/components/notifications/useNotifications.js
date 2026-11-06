import { useState } from 'react'

// Daftar notifikasi diisi dari API backend (belum tersedia); sementara kosong.
export default function useNotifications() {
  const [items, setItems] = useState([])
  const unread = items.filter((item) => !item.read).length
  const markAllRead = () => setItems((current) => current.map((item) => ({ ...item, read: true })))
  return { items, unread, markAllRead }
}
