import { useEffect, useState } from 'react'
import AdminIcon from './admin/AdminIcon.jsx'

function PermissionForm({ item, onClose, onSubmit }) {
  const [name, setName] = useState(item?.name ?? '')
  const [message, setMessage] = useState('')
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    const handleKeyDown = (event) => {
      if (event.key === 'Escape') onClose()
    }
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    document.addEventListener('keydown', handleKeyDown)
    return () => {
      document.body.style.overflow = previousOverflow
      document.removeEventListener('keydown', handleKeyDown)
    }
  }, [onClose])

  const handleSubmit = async (event) => {
    event.preventDefault()
    if (!name.trim()) {
      setMessage('Nama permission wajib diisi.')
      return
    }
    setSaving(true)
    setMessage('')
    try {
      await onSubmit({ name: name.trim() })
      onClose()
    } catch (error) {
      setMessage(error.response?.data?.message ?? 'Permission gagal disimpan.')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="admin-dialog-backdrop admin-permission-dialog-backdrop" role="presentation" onMouseDown={(event) => event.target === event.currentTarget && onClose()}>
      <form className="admin-dialog admin-permission-dialog" onSubmit={handleSubmit}>
        <header className="admin-dialog-heading">
          <div className="admin-permission-dialog-title">
            <span className="admin-dialog-icon"><AdminIcon name="shield" /></span>
            <div>
              <span className="admin-permission-dialog-eyebrow">AKSES & KEAMANAN</span>
              <h2>{item ? 'Edit Permission' : 'Tambah Permission'}</h2>
              <p>{item ? 'Perbarui nama permission yang tersedia.' : 'Tambahkan hak akses baru untuk digunakan oleh role.'}</p>
            </div>
          </div>
          <button type="button" className="admin-close" onClick={onClose} aria-label="Tutup">
            <AdminIcon name="close" />
          </button>
        </header>
        <label>
          Nama Permission
          <input value={name} onChange={(event) => setName(event.target.value)} placeholder="Contoh: manage reports" autoFocus />
          <small>Gunakan huruf kecil, angka, spasi, garis bawah, atau garis miring.</small>
        </label>
        {message && <p className="admin-form-error" role="alert">{message}</p>}
        <footer className="admin-dialog-actions">
          <button type="button" className="admin-button admin-button--muted" onClick={onClose}>Batal</button>
          <button type="submit" className="admin-button" disabled={saving}><AdminIcon name="save" size={15} /> {saving ? 'Menyimpan…' : item ? 'Simpan Perubahan' : 'Simpan Permission'}</button>
        </footer>
      </form>
    </div>
  )
}

export default PermissionForm
