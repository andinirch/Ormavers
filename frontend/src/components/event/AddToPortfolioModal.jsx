import { useEffect, useState } from 'react'
import AdminIcon from '../admin/AdminIcon.jsx'
import { addEventToPortfolio, getFinishedEvents, getPortfolioPrefill } from '../../api/eventPortfolio.js'

const MAX_FILE_BYTES = 5 * 1024 * 1024

function errorMessage(error, fallback) {
  const errors = error.response?.data?.errors
  const first = errors ? Object.values(errors).flat()[0] : null
  return first ?? error.response?.data?.message ?? fallback
}

function AddToPortfolioModal({ open, event, onClose, onDone }) {
  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [selectedId, setSelectedId] = useState(null)
  const [prefillLoading, setPrefillLoading] = useState(false)
  const [form, setForm] = useState({ judul: '', tahun: '', deskripsi: '' })
  const [file, setFile] = useState(null)
  const [fieldError, setFieldError] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [done, setDone] = useState(false)

  useEffect(() => {
    if (!open) return undefined
    let active = true
    const run = async () => {
      setLoading(true)
      setError('')
      setForm({ judul: '', tahun: '', deskripsi: '' })
      setFile(null)
      setFieldError('')
      setDone(false)

      if (event?.id) {
        setSelectedId(event.id)
        setForm({ judul: event.judul ?? '', tahun: String(event.tahun ?? ''), deskripsi: event.deskripsi ?? '' })
        try {
          const result = await getPortfolioPrefill(event.id)
          if (!active) return
          const data = result?.data ?? {}
          setForm({
            judul: data.judul ?? event.judul ?? '',
            tahun: data.tahun ? String(data.tahun) : String(event.tahun ?? ''),
            deskripsi: data.deskripsi ?? event.deskripsi ?? '',
          })
        } catch {
          // fallback ke data dari card
        } finally {
          if (active) setLoading(false)
        }
        return
      }

      setSelectedId(null)
      try {
        const result = await getFinishedEvents()
        if (active) setEvents(Array.isArray(result?.data) ? result.data : [])
      } catch (requestError) {
        if (active) setError(errorMessage(requestError, 'Daftar event selesai gagal dimuat.'))
      } finally {
        if (active) setLoading(false)
      }
    }
    queueMicrotask(run)
    return () => { active = false }
  }, [open, event])

  if (!open) return null

  const selectEvent = async (eventItem) => {
    setSelectedId(eventItem.id)
    setFieldError('')
    setPrefillLoading(true)
    setForm({ judul: eventItem.judul ?? '', tahun: String(eventItem.tahun ?? ''), deskripsi: eventItem.deskripsi ?? '' })
    try {
      const result = await getPortfolioPrefill(eventItem.id)
      const data = result?.data ?? {}
      setForm({
        judul: data.judul ?? eventItem.judul ?? '',
        tahun: data.tahun ? String(data.tahun) : String(eventItem.tahun ?? ''),
        deskripsi: data.deskripsi ?? eventItem.deskripsi ?? '',
      })
    } catch {
      // fallback ke data dari daftar
    } finally {
      setPrefillLoading(false)
    }
  }

  const chooseFile = (event) => {
    const chosen = event.target.files?.[0] ?? null
    event.target.value = ''
    if (!chosen) return
    if (!/^image\/(jpeg|png)$/.test(chosen.type)) { setFieldError('Dokumentasi harus berupa gambar JPG atau PNG.'); return }
    if (chosen.size > MAX_FILE_BYTES) { setFieldError('Ukuran dokumentasi maksimal 5 MB.'); return }
    setFieldError('')
    setFile(chosen)
  }

  const submit = async () => {
    if (!selectedId) { setFieldError('Pilih event terlebih dahulu.'); return }
    if (!file) { setFieldError('Dokumentasi wajib diunggah.'); return }
    setSubmitting(true)
    setFieldError('')
    try {
      const formData = new FormData()
      if (form.judul.trim()) formData.append('judul', form.judul.trim())
      if (form.tahun) formData.append('tahun', form.tahun)
      if (form.deskripsi.trim()) formData.append('deskripsi', form.deskripsi.trim())
      formData.append('dokumentasi', file)
      await addEventToPortfolio(selectedId, formData)
      setDone(true)
      setEvents((current) => current.map((item) => (item.id === selectedId ? { ...item, sudah_masuk_portofolio: true } : item)))
      onDone?.()
    } catch (requestError) {
      setFieldError(errorMessage(requestError, 'Event gagal ditambahkan ke portofolio.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="event-modal" role="presentation" onMouseDown={(event) => { if (event.target === event.currentTarget && !submitting) onClose() }}>
      <div className="event-modal__dialog" role="dialog" aria-modal="true" aria-label="Tambah Event ke Portofolio">
        <header className="event-modal__head">
          <h2>Tambah Event ke Portofolio</h2>
          <button type="button" onClick={onClose} aria-label="Tutup" disabled={submitting}><AdminIcon name="close" size={18} /></button>
        </header>

        <div className="event-modal__body">
          {done && <p className="event-modal__state">Portofolio berhasil ditambahkan.</p>}
          {loading && <p className="event-modal__state">Memuat…</p>}
          {!loading && error && <p className="event-modal__state is-error" role="alert">{error}</p>}
          {!event && !loading && !error && events.length === 0 && (
            <p className="event-modal__state">Belum ada event selesai yang dapat ditambahkan.</p>
          )}

          {!event && !loading && !error && events.length > 0 && (
            <ul className="portfolio-event-list">
              {events.map((item) => {
                const disabled = item.sudah_masuk_portofolio
                const active = selectedId === item.id
                return (
                  <li key={item.id}>
                    <button
                      type="button"
                      className={`portfolio-event-option${active ? ' is-active' : ''}`}
                      onClick={() => selectEvent(item)}
                      disabled={disabled || submitting}
                    >
                      <span className="portfolio-event-option__body">
                        <strong>{item.judul}</strong>
                        <small>{[item.tahun, item.nama_kategori, item.peran_organisasi].filter(Boolean).join(' · ')}</small>
                      </span>
                      {disabled && <em className="portfolio-event-option__flag">Sudah</em>}
                    </button>
                  </li>
                )
              })}
            </ul>
          )}

          {selectedId && (
            <div className="portfolio-event-form">
              <div className="event-form__field">
                <label htmlFor="pf-judul">Judul portofolio</label>
                <input id="pf-judul" className="form-input" value={form.judul} onChange={(event) => setForm((current) => ({ ...current, judul: event.target.value }))} disabled={prefillLoading || submitting} />
              </div>
              <div className="event-form__field">
                <label htmlFor="pf-tahun">Tahun</label>
                <input id="pf-tahun" className="form-input" type="number" value={form.tahun} onChange={(event) => setForm((current) => ({ ...current, tahun: event.target.value }))} disabled={prefillLoading || submitting} />
              </div>
              <div className="event-form__field">
                <label htmlFor="pf-deskripsi">Deskripsi</label>
                <textarea id="pf-deskripsi" className="form-input event-form__textarea" rows={3} value={form.deskripsi} onChange={(event) => setForm((current) => ({ ...current, deskripsi: event.target.value }))} disabled={prefillLoading || submitting} />
              </div>
              <div className="event-form__field">
                <label htmlFor="pf-dok">Dokumentasi</label>
                <label className="event-form__dropzone">
                  <input id="pf-dok" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" onChange={chooseFile} disabled={submitting} />
                  <AdminIcon name="upload-cloud" size={20} />
                  <strong>{file ? file.name : 'Unggah dokumentasi'}</strong>
                  <small>JPG/PNG, maks. 5 MB</small>
                </label>
              </div>
            </div>
          )}

          {fieldError && <p className="event-modal__state is-error" role="alert">{fieldError}</p>}
        </div>

        <footer className="event-modal__foot">
          <button type="button" className="event-secondary-button" onClick={onClose} disabled={submitting}>Tutup</button>
          <button type="button" className="event-primary-button" onClick={submit} disabled={!selectedId || submitting}>
            {submitting ? 'Menyimpan…' : 'Simpan ke Portofolio'}
          </button>
        </footer>
      </div>
    </div>
  )
}

export default AddToPortfolioModal
