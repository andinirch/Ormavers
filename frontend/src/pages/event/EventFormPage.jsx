import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import Swal from 'sweetalert2'
import PageHeader from '../../components/PageHeader.jsx'
import EventForm from '../../components/event/EventForm.jsx'
import { createEvent, getEventCategories, getEventDetail, updateEvent } from '../../api/eventManagement.js'
import '../../styles/event-management.css'

function errorMessage(error, fallback) {
  const errors = error.response?.data?.errors
  const first = errors ? Object.values(errors).flat()[0] : null
  return first ?? error.response?.data?.message ?? fallback
}

function toDateInput(value) {
  if (!value) return ''
  return String(value).slice(0, 10)
}

function toDateTimeInput(value) {
  if (!value) return ''
  return String(value).replace(' ', 'T').slice(0, 16)
}

function EventFormPage() {
  const navigate = useNavigate()
  const { eventId } = useParams()
  const isEdit = Boolean(eventId)

  const [categories, setCategories] = useState([])
  const [initialValues, setInitialValues] = useState(null)
  const [existingPosters, setExistingPosters] = useState([])
  const [canEdit, setCanEdit] = useState(true)
  const [loading, setLoading] = useState(isEdit)
  const [loadError, setLoadError] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    const run = async () => {
      setLoadError('')
      try {
        const categoriesResult = await getEventCategories()
        const list = Array.isArray(categoriesResult?.data) ? categoriesResult.data : []
        if (!active) return
        setCategories(list)

        if (isEdit) {
          setLoading(true)
          const detailResult = await getEventDetail(eventId)
          if (!active) return
          const detail = detailResult?.data ?? {}
          const lainnya = list.find((item) => String(item.nama_kategori).toLowerCase() === 'lainnya')
          setCanEdit((detail.peran_organisasi ?? 'pembuat') === 'pembuat')
          setInitialValues({
            judul: detail.judul ?? '',
            deskripsi: detail.deskripsi ?? '',
            tanggal_event: toDateInput(detail.tanggal_event),
            id_kategori: detail.id_kategori != null
              ? String(detail.id_kategori)
              : (detail.tag && lainnya ? String(lainnya.id) : ''),
            tag: detail.tag ?? '',
            tautan_pendaftaran: detail.tautan_pendaftaran ?? '',
            periode_mulai: toDateTimeInput(detail.periode_mulai),
            periode_selesai: toDateTimeInput(detail.periode_selesai),
            jangkauan_publikasi: detail.jangkauan_publikasi ?? 'internal',
          })
          setExistingPosters(Array.isArray(detail.posters) ? detail.posters : [])
        }
      } catch (requestError) {
        if (active) setLoadError(errorMessage(requestError, 'Data event gagal dimuat.'))
      } finally {
        if (active) setLoading(false)
      }
    }
    queueMicrotask(run)
    return () => { active = false }
  }, [eventId, isEdit])

  const handleSubmit = async (formData) => {
    setSubmitting(true)
    setError('')
    try {
      const result = isEdit ? await updateEvent(eventId, formData) : await createEvent(formData)
      await Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: result?.message ?? (isEdit ? 'Event berhasil diperbarui.' : 'Event berhasil dibuat.'),
        confirmButtonColor: '#2563eb',
      })
      navigate('/verifikasi/kelola-event', { replace: true })
    } catch (requestError) {
      setError(errorMessage(requestError, isEdit ? 'Event gagal diperbarui.' : 'Event gagal disimpan.'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main className="event-management-page">
      <PageHeader
        title={isEdit ? 'Edit Event' : 'Buat Event'}
        subtitle={isEdit ? 'Perbarui data event, poster, dan jangkauan publikasi.' : 'Lengkapi data event, poster, dan jangkauan publikasi.'}
        breadcrumb={[
          { label: 'Kelola Event', to: '/verifikasi/kelola-event' },
          isEdit ? 'Edit Event' : 'Buat Event',
        ]}
        icon="calendar"
      />

      {isEdit && loading && <div className="event-state">Memuat data event…</div>}
      {isEdit && !loading && loadError && <div className="event-state event-state--error" role="alert">{loadError}</div>}
      {isEdit && !loading && !loadError && !canEdit && (
        <div className="event-state event-state--error" role="alert">Hanya pembuat event yang dapat mengedit event ini.</div>
      )}

      {(!isEdit || (!loading && !loadError && canEdit)) && (
        <EventForm
          mode={isEdit ? 'edit' : 'create'}
          eventId={eventId}
          categories={categories}
          initialValues={initialValues ?? undefined}
          existingPosters={existingPosters}
          submitting={submitting}
          error={error}
          onSubmit={handleSubmit}
          onCancel={() => navigate('/verifikasi/kelola-event')}
        />
      )}
    </main>
  )
}

export default EventFormPage
