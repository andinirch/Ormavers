import { VerificationPage, DocRows } from '../components/verification/VerificationPage.jsx'
function MemberVerificationPage() {
  return <VerificationPage type="member" member title="Daftar permintaan anggota" subtitle="Verifikasi permintaan keanggotaan di organisasi kamu" eyebrow="Portal Verifikasi  /  Keanggotaan Organisasi" listTitle="Permintaan Keanggotaan" renderRow={(item) => <><span className="verification-member"><b>{item.initials}</b><strong>{item.namaLengkap}</strong></span><span>{item.nim}</span><span>{item.tanggal}</span></>} renderDocs={(item, onPreview) => <DocRows item={item} onPreview={onPreview} member />} roleNote="Aktivasi keanggotaan dan pengiriman notifikasi WhatsApp masih menunggu integrasi backend." />
}
export default MemberVerificationPage
