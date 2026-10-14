import { useSearchParams } from 'react-router-dom'
import { VerificationPage, DocRows } from '../components/verification/VerificationPage.jsx'
import { verificationContexts } from '../services/verification/verificationApi.js'

function BemOrganizationVerificationPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const section = searchParams.get('bagian') === 'anggota' ? 'member' : 'hmj'
  const context = verificationContexts.bemUmg
  const selectSection = (nextSection) => {
    setSearchParams({ bagian: nextSection === 'member' ? 'anggota' : 'hmj' })
  }
  const isMember = section === 'member'

  return <div className="verification-page">
    <div className="verification-page__heading">
      <div className="verification-eyebrow">Portal Verifikasi  /  {context.organizationName}</div>
      <h1>{isMember ? 'Daftar permintaan anggota' : 'Daftar registrasi HMJ/UKM'}</h1>
      <p>{isMember ? 'Verifikasi permintaan keanggotaan di organisasi kamu' : 'Verifikasi pendaftaran HMJ/UKM yang memilih BEM kamu sebagai induk'}</p>
    </div>
    <div className="verification-context-tabs" role="tablist" aria-label="Jenis verifikasi BEM">
      <button type="button" className={section === 'hmj' ? 'is-active' : ''} onClick={() => selectSection('hmj')} role="tab" aria-selected={section === 'hmj'}>Registrasi HMJ/UKM</button>
      <button type="button" className={section === 'member' ? 'is-active' : ''} onClick={() => selectSection('member')} role="tab" aria-selected={section === 'member'}>Permintaan Anggota</button>
    </div>
    {isMember ? <VerificationPage type="member" context={context} member listTitle="Permintaan Keanggotaan" eyebrow="Portal Verifikasi  /  Keanggotaan Organisasi" title="" subtitle="" renderRow={(item) => <><span className="verification-member"><b>{item.initials}</b><strong>{item.namaLengkap}</strong></span><span>{item.nim}</span><span>{item.tanggal}</span></>} renderDocs={(item, onPreview) => <DocRows item={item} onPreview={onPreview} member />} roleNote="Aktivasi keanggotaan dan pengiriman notifikasi WhatsApp masih menunggu integrasi backend." hidePageHeading /> : <VerificationPage type="hmj" context={context} listTitle="Permohonan Registrasi HMJ/UKM" eyebrow="BIRO ADMINISTRASI & ORGANISASI" title="" subtitle="" renderRow={(item) => <><span className="verification-org"><b>{item.initials}</b><strong>{item.namaOrganisasi}<small>ID: {item.kode}</small></strong></span><span><em>{item.jenis}</em></span><span>{item.tanggal}</span></>} renderDocs={(item, onPreview) => <DocRows item={item} onPreview={onPreview} />} note="Data relasi organisasi HMJ/UKM yang belum dipetakan tidak dapat diproses." hidePageHeading />}
  </div>
}

export default BemOrganizationVerificationPage
