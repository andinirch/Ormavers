import { VerificationPage, DocRows } from '../components/verification/VerificationPage.jsx'
function BemVerificationPage() {
  return <VerificationPage type="bem" title="Daftar registrasi BEM" subtitle="Verifikasi pendaftaran BEM dari seluruh kampus" eyebrow="PANEL KURASI & VALIDASI" listTitle="Permohonan Registrasi BEM" renderRow={(item) => <><span><strong>{item.namaOrganisasi}</strong><small>{item.sublabel || 'Sedang ditinjau'}</small></span><span>{item.kampus}</span><span>{item.tanggal}</span></>} renderDocs={(item, onPreview) => <DocRows item={item} onPreview={onPreview} />} />
}
export default BemVerificationPage
