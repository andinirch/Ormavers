import { VerificationPage, DocRows } from '../components/verification/VerificationPage.jsx'
function HmjUkmVerificationPage() {
  return <VerificationPage type="hmj" title="Daftar registrasi HMJ/UKM" subtitle="Verifikasi pendaftaran HMJ/UKM yang memilih BEM kamu sebagai induk" eyebrow="BIRO ADMINISTRASI & ORGANISASI • BEM FAKULTAS TEKNIK" listTitle="Permohonan Registrasi HMJ/UKM" note="Pendaftaran yang disetujui akan resmi terdaftar di bawah binaan Badan Eksekutif Mahasiswa Fakultas Teknik dan memiliki akses integrasi anggaran kemahasiswaan universitas." renderRow={(item) => <><span className="verification-org"><b>{item.initials}</b><strong>{item.namaOrganisasi}<small>ID: {item.kode}</small></strong></span><span><em>{item.jenis}</em></span><span>{item.tanggal}</span></>} renderDocs={(item, onPreview) => <DocRows item={item} onPreview={onPreview} />} />
}
export default HmjUkmVerificationPage
