import { VerificationPage, DocRows } from '../components/verification/VerificationPage.jsx'
import { verificationContexts } from '../services/verification/verificationApi.js'
function BemVerificationPage() {
  return <VerificationPage type="bem" context={verificationContexts.administrator} title="Daftar registrasi BEM" subtitle="Verifikasi pendaftaran BEM dari seluruh kampus" eyebrow="PANEL KURASI & VALIDASI" listTitle="Permohonan Registrasi BEM" renderRow={(item) => <><span><strong>{item.namaOrganisasi}</strong><small>{item.sublabel || 'Sedang ditinjau'}</small></span><span>{item.kampus}</span><span>{item.tanggal}</span></>} renderDocs={(item, onPreview) => <DocRows item={item} onPreview={onPreview} />} />
}
export default BemVerificationPage
