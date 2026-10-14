import { useLocation } from 'react-router-dom'
import { VerificationPage, DocRows } from '../components/verification/VerificationPage.jsx'
import { verificationContexts } from '../services/verification/verificationApi.js'

function HmjUkmVerificationPage() {
  const { search } = useLocation()
  const organizationKey = new URLSearchParams(search).get('organisasi')
  const context = organizationKey === 'ukm-robotika' ? verificationContexts.ukmRobotika : verificationContexts.hmif

  return <VerificationPage type="member" context={context} member title="Daftar permintaan anggota" subtitle="Verifikasi permintaan keanggotaan di organisasi kamu" eyebrow="Portal Verifikasi  /  Keanggotaan Organisasi" listTitle="Permintaan Keanggotaan" renderRow={(item) => <><span className="verification-member"><b>{item.initials}</b><strong>{item.namaLengkap}</strong></span><span>{item.nim}</span><span>{item.tanggal}</span></>} renderDocs={(item, onPreview) => <DocRows item={item} onPreview={onPreview} member />} roleNote="Aktivasi keanggotaan dan pengiriman notifikasi WhatsApp masih menunggu integrasi backend." />
}

export default HmjUkmVerificationPage
