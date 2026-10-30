const config = {
  menunggu: { label: 'Menunggu', className: 'status-pill--menunggu' },
  disetujui: { label: 'Disetujui', className: 'status-pill--disetujui' },
  ditolak: { label: 'Ditolak', className: 'status-pill--ditolak' },
}

function StatusIcon({ status }) {
  if (status === 'menunggu') {
    return (
      <svg viewBox="0 0 16 16" className="status-pill__svg" aria-hidden="true">
        <circle cx="8" cy="8" r="5.5" />
        <path d="M8 4.5v3.7l2.3 1.4" />
      </svg>
    )
  }

  if (status === 'disetujui') {
    return (
      <svg viewBox="0 0 16 16" className="status-pill__svg" aria-hidden="true">
        <circle cx="8" cy="8" r="5.5" />
        <path d="m5.2 8 1.8 1.8 3.8-4" />
      </svg>
    )
  }

  return (
    <svg viewBox="0 0 16 16" className="status-pill__svg" aria-hidden="true">
      <circle cx="8" cy="8" r="5.5" />
      <path d="m6 6 4 4m0-4-4 4" />
    </svg>
  )
}

function StatusPill({ status }) {
  const c = config[status]
  if (!c) return null
  return (
    <span className={`status-pill ${c.className}`}>
      <StatusIcon status={status} />
      {c.label}
    </span>
  )
}

export default StatusPill
