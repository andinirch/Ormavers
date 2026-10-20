function RejectionReason({ reason }) {
  const text = reason || 'Alasan penolakan belum tersedia.'
  return (
    <div>
      <p className="status-section-label">Alasan Penolakan</p>
      <div className="rejection-box">
        <span className="rejection-box__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" className="rejection-box__svg">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7.5v5M12 16.5h.01" />
          </svg>
        </span>
        <span>{text}</span>
      </div>
    </div>
  )
}

export default RejectionReason
