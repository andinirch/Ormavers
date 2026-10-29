import { useEffect, useMemo, useState } from 'react'
import Swal from 'sweetalert2'
import AdminIcon from '../components/admin/AdminIcon.jsx'
import ImageWithFallback from '../components/ImageWithFallback.jsx'
import { assetUrl } from '../api/api.js'

function normalizePortfolioItems(items) {
  return items.map((item) => ({ ...item, dokumentasi: assetUrl(item.dokumentasi) }))
}
import { listOrganizationPortfolios, createOrganizationPortfolio, updateOrganizationPortfolio, deleteOrganizationPortfolio } from '../api/organizationManagement.js'
import '../styles/organization-portfolio.css'

const MAX_FILE_BYTES = 5 * 1024 * 1024
const EMPTY_FORM = { judul: '', tahun: '', deskripsi: '' }

function BackIcon() {
  return <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="M19 12H5" /><path d="m12 19-7-7 7-7" /></svg>
}

function toList(result) {
  const data = result?.data
  if (Array.isArray(data)) return data
  if (Array.isArray(data?.portofolio)) return data.portofolio
  return []
}

function errorMessage(error, fallback) {
  const errors = error.response?.data?.errors
  const first = errors ? Object.values(errors).flat()[0] : null
  return first ?? error.response?.data?.message ?? fallback
}

function Thumbnail({ url, title }) {
  const imageUrl = assetUrl(url)
  const isImage = Boolean(imageUrl)
  
  // Fallback component untuk gambar yang gagal load
  const fallbackComponent = (
    <div style={{ 
      display: 'flex', 
      alignItems: 'center', 
      justifyContent: 'center',
      width: '100%',
      height: '100%',
      backgroundColor: '#f3f4f6',
      color: '#9ca3af'
    }}>
      <AdminIcon name="image" size={24} />
    </div>
  )
  
  return <div className="organization-portfolio-thumb">
    {isImage
      ? (
        <>
          <ImageWithFallback 
             src={imageUrl}
             alt={title}
            fallback={fallbackComponent}
            style={{ width: '100%', height: '100%', objectFit: 'cover' }}
          />
          <em><AdminIcon name="image" size={11} /> Foto</em>
        </>
      )
      : <span><AdminIcon name="image" size={24} /></span>}
  </div>
}

function OrganizationPortfolioPage() {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [year, setYear] = useState('')
  const [form, setForm] = useState(EMPTY_FORM)
  const [file, setFile] = useState(null)
  const [editing, setEditing] = useState(null)
  const [fieldError, setFieldError] = useState('')
  const [saving, setSaving] = useState(false)
  const [deletingId, setDeletingId] = useState(null)

  const loadData = async () => {
    setLoading(true)
    setError('')
    try {
      setItems(normalizePortfolioItems(toList(await listOrganizationPortfolios())))
    } catch (requestError) {
      setError(errorMessage(requestError, 'Portofolio gagal dimuat.'))
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    queueMicrotask(() => { loadData() })
  }, [])

  const years = useMemo(() => [...new Set(items.map((item) => item.tahun))].sort((a, b) => b - a), [items])
  const groups = useMemo(() => years
    .filter((value) => !year || String(value) === year)
    .map((value) => ({ year: value, items: items.filter((item) => item.tahun === value) })), [items, years, year])

  const yearOptions = useMemo(() => {
    const current = new Date().getFullYear() + 1
    return Array.from({ length: current - 2000 + 1 }, (_, index) => current - index)
  }, [])

  const resetForm = () => {
    setForm(EMPTY_FORM)
    setFile(null)
    setEditing(null)
    setFieldError('')
  }

  const updateField = (event) => setForm((current) => ({ ...current, [event.target.name]: event.target.value }))

  const chooseFile = (event) => {
    const chosen = event.target.files?.[0] ?? null
    event.target.value = ''
    if (!chosen) return
    if (!/^image\/(jpeg|png)$/.test(chosen.type)) { setFieldError('Dokumentasi harus berupa gambar JPG atau PNG.'); return }
    if (chosen.size > MAX_FILE_BYTES) { setFieldError('Ukuran dokumentasi maksimal 5 MB.'); return }
    setFieldError('')
    setFile(chosen)
  }

  const startEdit = (item) => {
    setEditing(item)
    setForm({ judul: item.judul ?? '', tahun: String(item.tahun ?? ''), deskripsi: item.deskripsi ?? '' })
    setFile(null)
    setFieldError('')
  }

  const submit = async (event) => {
    event.preventDefault()
    if (!form.judul.trim() || !form.tahun || !form.deskripsi.trim()) { setFieldError('Nama kegiatan, tahun, dan deskripsi wajib diisi.'); return }
    if (!editing && !file) { setFieldError('Dokumentasi wajib diunggah.'); return }
    setSaving(true)
    setFieldError('')
    try {
      const base = { judul: form.judul.trim(), tahun: form.tahun, deskripsi: form.deskripsi.trim() }
      let payload
      if (file) {
        payload = new FormData()
        Object.entries(base).forEach(([key, value]) => payload.append(key, value))
        payload.append('dokumentasi', file)
      } else {
        payload = { ...base, dokumentasi: editing.dokumentasi }
      }
      const result = editing
        ? await updateOrganizationPortfolio(editing.id_portofolio, payload)
        : await createOrganizationPortfolio(payload)
      const list = toList(result)
      if (list.length) setItems(normalizePortfolioItems(list)); else await loadData()
      resetForm()
      await Swal.fire({ icon: 'success', title: 'Berhasil', text: result?.message ?? 'Portofolio berhasil disimpan.', confirmButtonColor: '#2563eb' })
    } catch (requestError) {
      setFieldError(errorMessage(requestError, 'Portofolio gagal disimpan.'))
    } finally {
      setSaving(false)
    }
  }

  const remove = async (item) => {
    const confirmed = await Swal.fire({
      iconHtml: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16" /><path d="M10 11v6M14 11v6" /><path d="M6 7l1 14h10l1-14M9 7V4h6v3" /></svg>',
      title: 'Hapus portofolio?',
      text: 'Portofolio yang dihapus tidak dapat dipulihkan.',
      showCancelButton: true,
      confirmButtonText: 'Hapus',
      cancelButtonText: 'Batal',
      reverseButtons: true,
      buttonsStyling: false,
      width: 360,
      customClass: {
        popup: 'portfolio-delete-popup',
        icon: 'portfolio-delete-icon',
        title: 'portfolio-delete-title',
        htmlContainer: 'portfolio-delete-text',
        actions: 'portfolio-delete-actions',
        cancelButton: 'portfolio-delete-cancel',
        confirmButton: 'portfolio-delete-confirm',
      },
    })
    if (!confirmed.isConfirmed) return
    setDeletingId(item.id_portofolio)
    try {
      await deleteOrganizationPortfolio(item.id_portofolio)
      setItems((current) => current.filter((entry) => entry.id_portofolio !== item.id_portofolio))
      if (editing?.id_portofolio === item.id_portofolio) resetForm()
    } catch (requestError) {
      await Swal.fire({ icon: 'error', title: 'Gagal menghapus', text: errorMessage(requestError, 'Portofolio gagal dihapus.'), confirmButtonColor: '#2563eb' })
    } finally {
      setDeletingId(null)
    }
  }

  return <main className="organization-portfolio-page">
    <header className="organization-portfolio-heading">
      <a className="organization-portfolio-back" href="/verifikasi/profil-organisasi" aria-label="Kembali ke profil organisasi"><BackIcon /></a>
      <div>
        <h1>Portofolio organisasi</h1>
        <p>Riwayat kegiatan dan pencapaian yang tampil di profil organisasi, disusun per tahun.</p>
      </div>
    </header>

    <div className="organization-portfolio-layout">
      <section className="organization-portfolio-main">
        <div className="organization-portfolio-filter">
          <span><AdminIcon name="filter" size={16} /> Filter Periode</span>
          <select value={year} onChange={(event) => setYear(event.target.value)} aria-label="Filter periode" disabled={!years.length}>
            <option value="">Semua tahun</option>
            {years.map((value) => <option key={value} value={value}>{value}</option>)}
          </select>
        </div>

        {loading && <div className="organization-portfolio-state">Memuat portofolio...</div>}
        {!loading && error && <div className="organization-portfolio-state organization-portfolio-state--error" role="alert">{error}</div>}
        {!loading && !error && !groups.length && <div className="organization-portfolio-state">Belum ada portofolio yang tersedia.</div>}

        {!loading && !error && groups.map((group) => <div className="organization-portfolio-year" key={group.year}>
          <div className="organization-portfolio-year__head">
            <AdminIcon name="calendar" size={16} />
            <h2>{group.year}</h2>
            <span>{group.items.length} Kegiatan</span>
          </div>
          {group.items.map((item) => <article className="organization-portfolio-card" key={item.id_portofolio}>
            <Thumbnail url={item.dokumentasi} title={item.judul} />
            <div className="organization-portfolio-card__body">
              <h3>{item.judul}</h3>
              <span className="organization-portfolio-card__year">Tahun {item.tahun}</span>
              <p>{item.deskripsi}</p>
              <div className="organization-portfolio-card__actions">
                <button type="button" onClick={() => startEdit(item)} disabled={saving}><AdminIcon name="edit" size={13} /> Ubah</button>
                <button type="button" className="is-danger" onClick={() => remove(item)} disabled={deletingId === item.id_portofolio}><AdminIcon name="trash" size={13} /> Hapus</button>
              </div>
            </div>
          </article>)}
        </div>)}
      </section>

      <aside className="organization-portfolio-side">
        <form className="portfolio-form" onSubmit={submit} noValidate>
          <header className="portfolio-form-header">
            <span className="portfolio-form-header__icon"><AdminIcon name="plus-circle" size={18} /></span>
            <div>
              <h2 className="portfolio-form-title">{editing ? 'Ubah portofolio' : 'Tambah portofolio'}</h2>
              <p className="portfolio-form-subtitle">{editing ? 'Perbarui data lalu simpan perubahan.' : 'Pilih Ubah pada daftar untuk mengedit.'}</p>
            </div>
          </header>

          <div className="portfolio-form-field">
            <label htmlFor="portfolio-judul">Nama kegiatan atau pencapaian</label>
            <input id="portfolio-judul" className="portfolio-form-input" name="judul" value={form.judul} onChange={updateField} maxLength={200} placeholder="Contoh: Seminar Nasional AI 2026" />
          </div>

          <div className="portfolio-form-field">
            <label htmlFor="portfolio-tahun">Tahun pelaksanaan</label>
            <div className="portfolio-form-select">
              <select id="portfolio-tahun" className="portfolio-form-input" name="tahun" value={form.tahun} onChange={updateField}>
                <option value="">Pilih tahun</option>
                {yearOptions.map((value) => <option key={value} value={value}>{value}</option>)}
              </select>
              <span className="portfolio-form-select__chevron" aria-hidden="true"><AdminIcon name="chevron-down" size={16} /></span>
            </div>
          </div>

          <div className="portfolio-form-field">
            <label htmlFor="portfolio-deskripsi">Deskripsi kegiatan</label>
            <textarea id="portfolio-deskripsi" className="portfolio-form-textarea" name="deskripsi" rows={5} value={form.deskripsi} onChange={updateField} placeholder="Ceritakan ringkasan kegiatan, tujuan, dampak, dan partisipan..." />
          </div>

          <div className="portfolio-form-field">
            <span className="portfolio-form-label">Dokumentasi</span>
            <label className="portfolio-form-dropzone">
              <input type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" onChange={chooseFile} />
              <span className="portfolio-form-dropzone__icon"><AdminIcon name="upload-cloud" size={20} /></span>
              <strong>{file ? file.name : editing ? 'Unggah gambar baru (opsional)' : 'Unggah gambar'}</strong>
              <small>Format JPG atau PNG maks. 5MB</small>
            </label>
          </div>

          {fieldError && <p className="portfolio-form-error" role="alert">{fieldError}</p>}

          <button type="submit" className="portfolio-form-submit" disabled={saving}>
            <AdminIcon name="save" size={16} /> {saving ? 'Menyimpan...' : editing ? 'Simpan perubahan' : 'Simpan portofolio'}
          </button>
          {editing && <button type="button" className="portfolio-form-cancel" onClick={resetForm} disabled={saving}>Batal</button>}
        </form>
      </aside>
    </div>
  </main>
}

export default OrganizationPortfolioPage
