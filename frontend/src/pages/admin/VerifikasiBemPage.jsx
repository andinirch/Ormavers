import { useEffect, useState } from 'react'
import { PageIntro } from './DashboardPage.jsx'
import { approveBemRegistration, getBemDocument, listBemRegistrations, rejectBemRegistration, showBemRegistration } from '../../api/verifikasi.js'

function VerifikasiBemPage() {
  const [items, setItems] = useState([])
  const [selected, setSelected] = useState(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)

  const load = async () => {
    try {
      const result = await listBemRegistrations()
      setItems(result.data?.pendaftaran ?? [])
    } catch (requestError) {
      setError(requestError.response?.data?.message ?? 'Data verifikasi tidak dapat dimuat.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => { queueMicrotask(load) }, [])

  const openDetail = async (id) => {
    try {
      const result = await showBemRegistration(id)
      setSelected(result.data?.pendaftaran ?? null)
      setError('')
    } catch (requestError) {
      setError(requestError.response?.data?.message ?? 'Detail pendaftaran tidak dapat dimuat.')
    }
  }

  const action = async (type) => {
    if (!selected) return
    try {
      if (type === 'approve') await approveBemRegistration(selected.id_pendaftaran_organisasi)
      else {
        const alasan = window.prompt('Alasan penolakan')
        if (!alasan) return
        await rejectBemRegistration(selected.id_pendaftaran_organisasi, alasan)
      }
      setSelected(null)
      await load()
    } catch (requestError) {
      setError(requestError.response?.data?.message ?? 'Aksi verifikasi gagal.')
    }
  }

  const openDocument = async (jenis) => {
    if (!selected) return
    const response = await getBemDocument(selected.id_pendaftaran_organisasi, jenis)
    const url = URL.createObjectURL(response.data)
    window.open(url, '_blank', 'noopener,noreferrer')
    window.setTimeout(() => URL.revokeObjectURL(url), 60000)
  }

  return <main className="admin-page">
    <PageIntro eyebrow="VERIFIKASI • ADMINISTRATOR" title="Verifikasi BEM" description="Periksa data dan dokumen pendaftaran BEM sebelum menyetujui akun organisasi." />
    {error && <p className="admin-form-error">{error}</p>}
    <section className="admin-panel">
      {loading ? <div className="admin-empty">Memuat pendaftaran...</div> : items.length === 0 ? <div className="admin-empty"><strong>Tidak ada pendaftaran menunggu</strong><span>Semua pendaftaran BEM sudah diproses.</span></div> : <div className="admin-table-wrap"><table><thead><tr><th>ORGANISASI</th><th>INSTANSI</th><th>PENANGGUNG JAWAB</th><th>TANGGAL</th><th>AKSI</th></tr></thead><tbody>{items.map((item) => <tr key={item.id_pendaftaran_organisasi}><td>{item.nama_organisasi}</td><td>{item.nama_instansi}</td><td>{item.nama_penanggung_jawab}</td><td>{new Date(item.tanggal_pendaftaran).toLocaleDateString('id-ID')}</td><td><button className="admin-button" onClick={() => openDetail(item.id_pendaftaran_organisasi)}>Detail</button></td></tr>)}</tbody></table></div>}
    </section>
    {selected && <div className="admin-dialog-backdrop" role="presentation"><section className="admin-dialog"><header className="admin-dialog-heading"><div><h2>{selected.nama_organisasi}</h2><p>{selected.nama_instansi}</p></div><button type="button" className="admin-close" onClick={() => setSelected(null)}>×</button></header><p><strong>Penanggung jawab:</strong> {selected.penanggung_jawab?.nama} ({selected.penanggung_jawab?.nim})</p><p><strong>WhatsApp:</strong> {selected.penanggung_jawab?.nomor_whatsapp}</p><div className="admin-dialog-actions"><button className="admin-button admin-button--muted" onClick={() => openDocument('ktm')}>Buka KTM</button><button className="admin-button admin-button--muted" onClick={() => openDocument('sk-organisasi')}>Buka SK Organisasi</button><button className="admin-button admin-button--muted" onClick={() => openDocument('sk-kepengurusan')}>Buka SK Kepengurusan</button></div><footer className="admin-dialog-actions"><button className="admin-button admin-button--muted" onClick={() => action('reject')}>Tolak</button><button className="admin-button" onClick={() => action('approve')}>Setujui</button></footer></section></div>}
  </main>
}

export default VerifikasiBemPage
