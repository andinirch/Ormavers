const config = {
  menunggu: { label: 'Menunggu', icon: '\u23F3', className: 'status-pill--menunggu' },
  disetujui: { label: 'Disetujui', icon: '\u2705', className: 'status-pill--disetujui' },
  ditolak: { label: 'Ditolak', icon: '\u274C', className: 'status-pill--ditolak' },
}

function StatusPill({ status }) {
  const c = config[status]
  if (!c) return null
  return (
    <span className={`status-pill ${c.className}`}>
      <span className="status-pill__icon" aria-hidden="true">{c.icon}</span>
      {c.label}
    </span>
  )
}

export default StatusPill
