import { useMemo, useRef, useState } from 'react'
import TextInput from '../form/TextInput.jsx'
import EventCategoryDropdown from './EventCategoryDropdown.jsx'
import FormField from '../form/FormField.jsx'
import SubmitButton from '../form/SubmitButton.jsx'
import FormMessage from '../form/FormMessage.jsx'
import AdminIcon from '../admin/AdminIcon.jsx'
import EventOrganizationPicker from './EventOrganizationPicker.jsx'
import DateTimeField from './DateTimeField.jsx'
import EventSentInvitations from './EventSentInvitations.jsx'
import { assetUrl } from '../../api/api.js'

const MAX_POSTERS = 3
const MAX_POSTER_BYTES = 2 * 1024 * 1024
const MAX_DESKRIPSI = 2000

const JANGKAUAN_OPTIONS = [
  { value: 'internal', label: 'Internal Organisasi' },
  { value: 'satu_universitas', label: 'Satu universitas' },
  { value: 'multi_universitas', label: 'Multi-universitas' },
]

const EMPTY_VALUES = {
  judul: '',
  deskripsi: '',
  tanggal_event: '',
  id_kategori: '',
  tag: '',
  tautan_pendaftaran: '',
  periode_mulai: '',
  periode_selesai: '',
  jangkauan_publikasi: 'internal',
}

function normalizeDateTime(value) {
  if (!value) return ''
  const cleaned = value.replace('T', ' ')
  return cleaned.length === 16 ? `${cleaned}:00` : cleaned
}

function posterSrc(poster) {
  const raw = poster?.url || poster?.path_file || ''
  if (!raw) return ''
  if (/^(https?:|data:|blob:)/i.test(raw) || raw.startsWith('/storage/')) return assetUrl(raw)
  return assetUrl(`/storage/${String(raw).replace(/^\/+/, '')}`)
}




function EventForm({
  mode = 'create',
  eventId,
  categories,
  initialValues,
  existingPosters = [],
  submitting,
  error,
  onSubmit,
  onCancel,
}) {
  const isEdit = mode === 'edit'
  const [values, setValues] = useState(() => ({ ...EMPTY_VALUES, ...(initialValues ?? {}) }))
  const [newPosters, setNewPosters] = useState([])
  const [removedIds, setRemovedIds] = useState([])
  const [primaryNewIndex, setPrimaryNewIndex] = useState(0)
  const [primaryExistingId, setPrimaryExistingId] = useState(() => {
    const utama = existingPosters.find((item) => item.is_utama)
    return utama?.id ?? existingPosters[0]?.id ?? null
  })
  const [errors, setErrors] = useState({})
  const initialTanggal = useRef(values.tanggal_event)
  const initialPeriodeMulai = useRef(values.periode_mulai)
  const initialPeriodeSelesai = useRef(values.periode_selesai)
  const [participants, setParticipants] = useState([])




  const lainnyaId = useMemo(() => {
    const other = (categories ?? []).find((item) => String(item.nama_kategori).toLowerCase() === 'lainnya')
    return other ? String(other.id) : null
  }, [categories])

  const isCustomCategory = Boolean(lainnyaId) && String(values.id_kategori) === lainnyaId

  const keptExisting = existingPosters.filter((item) => !removedIds.includes(item.id))
  const totalPosters = keptExisting.length + newPosters.length
  const emptySlots = Math.max(0, MAX_POSTERS - totalPosters)

  const update = (event) => {
    const { name, value } = event.target
    setValues((current) => ({ ...current, [name]: value }))
    setErrors((current) => ({ ...current, [name]: undefined }))
  }

  const addPosterFile = (file) => {
    if (!file) return
    if (keptExisting.length + newPosters.length >= MAX_POSTERS) {
      setErrors((c) => ({ ...c, poster: `Maksimal ${MAX_POSTERS} poster.` }))
      return
    }
    if (!/^image\/(jpeg|jpg)$/.test(file.type)) {
      setErrors((c) => ({ ...c, poster: 'Poster harus berformat JPG atau JPEG.' }))
      return
    }
    if (file.size > MAX_POSTER_BYTES) {
      setErrors((c) => ({ ...c, poster: 'Ukuran poster maksimal 2 MB per file.' }))
      return
    }
    setNewPosters((current) => [...current, file])
    setErrors((c) => ({ ...c, poster: undefined }))
  }

  const removeNewPoster = (index) => {
    setNewPosters((current) => current.filter((_, i) => i !== index))
    setPrimaryNewIndex((current) => (current >= index && current > 0 ? current - 1 : current))
  }

  const removeExisting = (id) => {
    setRemovedIds((current) => [...current, id])
  }

  const validate = () => {
    const next = {}
    if (!values.judul.trim()) next.judul = 'Judul event wajib diisi.'
    if (!values.deskripsi.trim()) next.deskripsi = 'Deskripsi event wajib diisi.'
    if (!values.tanggal_event) next.tanggal_event = 'Tanggal event wajib diisi.'
    else if (values.tanggal_event !== initialTanggal.current && values.tanggal_event.replace('T', ' ').length === 16 && new Date(values.tanggal_event.replace(' ', 'T')) < new Date()) next.tanggal_event = 'Tanggal dan jam event tidak boleh sebelum waktu sekarang.'
    if (!values.id_kategori) next.id_kategori = 'Kategori event wajib dipilih.'
    else if (isCustomCategory && !values.tag.trim()) next.tag = 'Tag wajib diisi untuk kategori Lainnya.'
    if (!values.tautan_pendaftaran.trim()) next.tautan_pendaftaran = 'Tautan pendaftaran wajib diisi.'
    else if (!/^https?:\/\/.+/i.test(values.tautan_pendaftaran.trim())) next.tautan_pendaftaran = 'Tautan pendaftaran harus berupa URL yang valid.'
    if (values.periode_mulai && values.periode_selesai && values.periode_selesai <= values.periode_mulai) {
      next.periode_selesai = 'Periode selesai harus setelah periode mulai.'
    }
    if (values.periode_selesai && values.tanggal_event && values.periode_selesai > values.tanggal_event) {
      next.periode_selesai = 'Periode selesai harus sebelum atau sama dengan tanggal event.'
    }
    if (totalPosters === 0) next.poster = 'Minimal unggah 1 poster.'
    return next
  }

  const handleSubmit = (event) => {
    event.preventDefault()
    const nextErrors = validate()
    setErrors(nextErrors)
    if (Object.keys(nextErrors).length > 0) return

    const formData = new FormData()
    formData.append('judul', values.judul.trim())
    formData.append('deskripsi', values.deskripsi.trim())
    if (values.tanggal_event !== initialTanggal.current) formData.append('tanggal_event', normalizeDateTime(values.tanggal_event))
    formData.append('tautan_pendaftaran', values.tautan_pendaftaran.trim())
    if (values.periode_mulai && values.periode_mulai !== initialPeriodeMulai.current) formData.append('periode_mulai', normalizeDateTime(values.periode_mulai))
    if (values.periode_selesai && values.periode_selesai !== initialPeriodeSelesai.current) formData.append('periode_selesai', normalizeDateTime(values.periode_selesai))
    formData.append('jangkauan_publikasi', values.jangkauan_publikasi)
    if (isCustomCategory) {
      formData.append('tag', values.tag.trim())
    } else {
      formData.append('id_kategori', values.id_kategori)
    }

    newPosters.forEach((file) => formData.append('poster[]', file))

    if (isEdit) {
      removedIds.forEach((id) => formData.append('hapus_poster_id[]', String(id)))
      if (primaryExistingId != null && !removedIds.includes(primaryExistingId)) {
        formData.append('poster_utama_id', String(primaryExistingId))
      }
    }
    formData.append('poster_utama_index', String(primaryNewIndex))

    participants.forEach((item, index) => {
      formData.append(`organisasi_terlibat[${index}][id_organisasi]`, String(item.id_organisasi))
      formData.append(`organisasi_terlibat[${index}][jenis]`, item.jenis)
    })

    onSubmit(formData)
  }

  return (
    <form className="event-form" onSubmit={handleSubmit} noValidate>
      <section className="event-form__card card">
        <div className="event-form__group">
          <h2>Informasi Event</h2>

          <div className="event-form__row">
            <TextInput id="ev-judul" name="judul" label="Judul event" value={values.judul} onChange={update} placeholder="Contoh: Seminar Nasional AI 2026" required error={errors.judul} />
            <EventCategoryDropdown id="ev-kategori" name="id_kategori" label="Kategori" value={values.id_kategori} onChange={update} categories={categories} placeholder="Pilih kategori" required error={errors.id_kategori} />
          </div>

          {isCustomCategory && (
            <TextInput id="ev-tag" name="tag" label="Tag kategori" value={values.tag} onChange={update} placeholder="Tulis tag kategori" required error={errors.tag} />
          )}

          <FormField id="ev-deskripsi" label="Deskripsi" required error={errors.deskripsi}>
            <textarea id="ev-deskripsi" name="deskripsi" className="form-input event-form__textarea" rows={5} maxLength={MAX_DESKRIPSI} value={values.deskripsi} onChange={update} placeholder="Jelaskan ringkasan, tujuan, dan peserta event..." />
            <span className="event-form__counter">{values.deskripsi.length}/{MAX_DESKRIPSI} karakter</span>
          </FormField>

          <div className="event-form__row3">
            <FormField id="ev-tanggal" label="Tanggal event" required error={errors.tanggal_event}>
              <DateTimeField id="ev-tanggal" name="tanggal_event" value={values.tanggal_event} onChange={update} hasError={Boolean(errors.tanggal_event)} disallowPast placeholder="Pilih tanggal & jam" />
            </FormField>

            <FormField id="ev-periode" label="Periode pendaftaran" required error={errors.periode_mulai || errors.periode_selesai}>
              <div className="dtf__range">
                <DateTimeField id="ev-periode" name="periode_mulai" value={values.periode_mulai} onChange={update} placeholder="Mulai" />
                <DateTimeField id="ev-periode-selesai" name="periode_selesai" value={values.periode_selesai} onChange={update} placeholder="Selesai" />
              </div>
            </FormField>

            <TextInput id="ev-tautan" name="tautan_pendaftaran" type="url" label="Tautan pendaftaran" value={values.tautan_pendaftaran} onChange={update} placeholder="https://forms.gle/contoh-link" required error={errors.tautan_pendaftaran} />
          </div>
        </div>

        <div className="event-form__group">
          <h2>Poster</h2>
          <p className="event-form__section-desc">JPG/JPEG, maksimal 2 MB per file, maksimal 3 poster. Tandai satu sebagai poster utama.</p>

          <div className="event-form__poster-grid">
            {keptExisting.map((poster) => (
              <div className="event-form__poster-slot is-filled" key={`existing-${poster.id}`}>
                {posterSrc(poster)
                  ? <img src={posterSrc(poster)} alt={poster.nama_file_asli ?? 'Poster'} />
                  : <span className="event-form__poster-empty"><AdminIcon name="image" size={22} /></span>}
                <label className="event-form__poster-utama">
                  <input
                    type="radio"
                    name="poster_utama_existing"
                    checked={primaryExistingId === poster.id}
                    onChange={() => setPrimaryExistingId(poster.id)}
                  />
                  <span>Utama</span>
                </label>
                <button type="button" className="event-form__poster-remove" onClick={() => removeExisting(poster.id)} aria-label="Hapus poster">
                  <AdminIcon name="trash" size={13} />
                </button>
              </div>
            ))}

            {newPosters.map((file, index) => (
              <div className="event-form__poster-slot is-filled" key={`new-${file.name}-${index}`}>
                <span className="event-form__poster-file"><AdminIcon name="image" size={22} />{file.name}</span>
                <label className="event-form__poster-utama">
                  <input type="radio" name="poster_utama" checked={primaryNewIndex === index} onChange={() => setPrimaryNewIndex(index)} />
                  <span>Utama</span>
                </label>
                <button type="button" className="event-form__poster-remove" onClick={() => removeNewPoster(index)} aria-label="Hapus poster">
                  <AdminIcon name="trash" size={13} />
                </button>
              </div>
            ))}

            {Array.from({ length: emptySlots }).map((_, index) => (
              <label className="event-form__poster-slot" key={`empty-${index}`}>
                <input
                  type="file"
                  accept=".jpg,.jpeg,image/jpeg"
                  onChange={(event) => {
                    const file = event.target.files?.[0]
                    event.target.value = ''
                    addPosterFile(file)
                  }}
                />
                <AdminIcon name="upload-cloud" size={20} />
                <strong>+ Poster</strong>
                <small>JPG/JPEG · maks 2 MB</small>
              </label>
            ))}
          </div>

          {errors.poster && <p className="event-form__error" role="alert">{errors.poster}</p>}
        </div>

        <div className="event-form__group">
          <h2>Jangkauan Publikasi</h2>
          <div className="event-form__jangkauan" role="radiogroup" aria-label="Jangkauan publikasi">
            {JANGKAUAN_OPTIONS.map((option) => {
              const active = values.jangkauan_publikasi === option.value
              return (
                <button
                  type="button"
                  key={option.value}
                  role="radio"
                  aria-checked={active}
                  className={`event-form__jangkauan-option${active ? ' is-active' : ''}`}
                  onClick={() => setValues((current) => ({ ...current, jangkauan_publikasi: option.value }))}
                >
                  {active && <AdminIcon name="check-circle" size={16} />}
                  <span>{option.label}</span>
                </button>
              )
            })}
          </div>
        </div>

        <EventOrganizationPicker selected={participants} onChange={setParticipants} />

        {eventId && <EventSentInvitations eventId={eventId} />}

        {error && <FormMessage variant="error">{error}</FormMessage>}

        <div className="event-form__actions">
          <button type="button" className="event-secondary-button event-form__cancel" onClick={onCancel} disabled={submitting}>Batal</button>
          <SubmitButton loading={submitting} loadingText="Menyimpan…">{isEdit ? 'Simpan perubahan' : 'Simpan Event'}</SubmitButton>
        </div>
      </section>
    </form>
  )
}

export default EventForm
