function RejectionReason({ reason }) {
  const text = reason || 'Alasan penolakan belum tersedia.'
  return (
    <div>
      <p className="status-section-label">Alasan Penolakan</p>
      <div className="rejection-box">
        <span className="rejection-box__icon" aria-hidden="true">{'\u26A0\uFE0F'}</span>
        <span>{text}</span>
      </div>
    </div>
  )
}

export default RejectionReason
