import { useEffect, useRef, useState } from 'react'
import '../../styles/date-time-field.css'

const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
const BULAN_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
const HARI = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']

const pad = (n) => String(n).padStart(2, '0')

function nowParts() {
  const d = new Date()
  return { y: d.getFullYear(), m: d.getMonth() + 1, d: d.getDate(), h: d.getHours(), i: d.getMinutes() }
}

function parseValue(value) {
  const match = /^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/.exec(value || '')
  if (!match) return null
  return { y: +match[1], m: +match[2], d: +match[3], h: match[4] ? +match[4] : 0, i: match[5] ? +match[5] : 0 }
}

function toValue(p) {
  return `${p.y}-${pad(p.m)}-${pad(p.d)}T${pad(p.h)}:${pad(p.i)}`
}

function toDisplay(value) {
  const p = parseValue(value)
  if (!p) return ''
  return `${p.d} ${BULAN_SHORT[p.m - 1]} ${p.y}, ${pad(p.h)}.${pad(p.i)} WIB`
}

function isPastDay(y, m, d) {
  const t = nowParts()
  return new Date(y, m - 1, d) < new Date(t.y, t.m - 1, t.d)
}


function TimeSelect({ label, value, max, onPick }) {
  const [open, setOpen] = useState(false)
  const items = Array.from({ length: max + 1 }, (_, n) => n)
  return (
    <div className="dtf__tsel">
      <span className="dtf__time-label">{label}</span>
      <button type="button" className="dtf__tbtn" onClick={() => setOpen((o) => !o)}>
        {pad(value)} <span aria-hidden="true">▾</span>
      </button>
      {open && (
        <div className="dtf__tlist">
          {items.map((n) => (
            <button key={n} type="button" className={`dtf__titem${n === value ? ' is-active' : ''}`}
              onClick={() => { onPick(n); setOpen(false) }}>{pad(n)}</button>
          ))}
        </div>
      )}
    </div>
  )
}

export default function DateTimeField({ id, name, value, onChange, placeholder = 'Pilih tanggal & jam', hasError = false, disallowPast = false }) {
  const wrapRef = useRef(null)
  const [open, setOpen] = useState(false)
  const current = parseValue(value)
  const [view, setView] = useState(() => {
    const base = current || nowParts()
    return { y: base.y, m: base.m }
  })

  useEffect(() => {
    if (!open) return undefined
    const handler = (event) => {
      if (wrapRef.current && !wrapRef.current.contains(event.target)) setOpen(false)
    }
    document.addEventListener('mousedown', handler)
    return () => document.removeEventListener('mousedown', handler)
  }, [open])

  const emit = (next) => onChange({ target: { name, value: next ? toValue(next) : '' } })

  const togglePanel = () => {
    const base = current || nowParts()
    setView({ y: base.y, m: base.m })
    setOpen((o) => !o)
  }

  const pickDay = (d) => {
    const time = current || { h: 9, i: 0 }
    emit({ y: view.y, m: view.m, d, h: time.h, i: time.i })
  }

  const changeTime = (field, val) => {
    const base = current || nowParts()
    emit({ ...base, [field]: +val })
  }

  const shiftMonth = (delta) => {
    setView((v) => {
      let m = v.m + delta
      let y = v.y
      if (m < 1) { m = 12; y -= 1 }
      if (m > 12) { m = 1; y += 1 }
      return { y, m }
    })
  }

  const t = nowParts()
  const firstOffset = (new Date(view.y, view.m - 1, 1).getDay() + 6) % 7
  const totalDays = new Date(view.y, view.m, 0).getDate()
  const cells = [...Array(firstOffset).fill(null), ...Array.from({ length: totalDays }, (_, k) => k + 1)]

  return (
    <div className={`dtf${hasError ? ' has-error' : ''}`} ref={wrapRef}>
      <button type="button" id={id} className="dtf__btn" onClick={togglePanel}>
        <span className={current ? '' : 'dtf__placeholder'}>{current ? toDisplay(value) : placeholder}</span>
        <span aria-hidden="true">▾</span>
      </button>

      {open && (
        <div className="dtf__panel" role="dialog">
          <div className="dtf__head">
            <button type="button" className="dtf__nav" onClick={() => shiftMonth(-1)} aria-label="Bulan sebelumnya">‹</button>
            <span>{BULAN[view.m - 1]} {view.y}</span>
            <button type="button" className="dtf__nav" onClick={() => shiftMonth(1)} aria-label="Bulan berikutnya">›</button>
          </div>

          <div className="dtf__grid">
            {HARI.map((h) => <span key={h} className="dtf__wd">{h}</span>)}
            {cells.map((d, idx) => {
              if (d === null) return <span key={`e${idx}`} />
              const selected = current && current.y === view.y && current.m === view.m && current.d === d
              const isToday = t.y === view.y && t.m === view.m && t.d === d
              const disabled = disallowPast && isPastDay(view.y, view.m, d)
              const cls = ['dtf__day', selected ? 'is-selected' : '', isToday ? 'is-today' : ''].join(' ').trim()
              return (
                <button key={d} type="button" className={cls} disabled={disabled} onClick={() => pickDay(d)}>{d}</button>
              )
            })}
          </div>

          <div className="dtf__time">
            <TimeSelect label="Jam" value={current ? current.h : 9} max={23} onPick={(v) => changeTime('h', v)} />
            <span className="dtf__colon">:</span>
            <TimeSelect label="Menit" value={current ? current.i : 0} max={59} step={1} onPick={(v) => changeTime('i', v)} />
            <span className="dtf__wib">WIB</span>
          </div>

          <div className="dtf__foot">
            <button type="button" className="dtf__ghost" onClick={() => { emit(null); setOpen(false) }}>Hapus</button>
            <button type="button" className="dtf__ghost" onClick={() => { emit(nowParts()); setView({ y: t.y, m: t.m }) }}>Hari ini</button>
            <button type="button" className="dtf__primary" onClick={() => setOpen(false)}>Selesai</button>
          </div>
        </div>
      )}
    </div>
  )
}
