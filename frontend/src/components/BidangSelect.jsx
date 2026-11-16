import { useEffect, useRef, useState } from 'react'
import AdminIcon from './admin/AdminIcon.jsx'
import '../styles/bidang-select.css'

function BidangSelect({ value, options, onChange, placeholder = 'Semua bidang', disabled = false }) {
  const [isOpen, setIsOpen] = useState(false)
  const [search, setSearch] = useState('')
  const containerRef = useRef(null)

  const selectedOption = options.find((o) => String(o.id_bidang) === String(value))
  const displayLabel = selectedOption ? selectedOption.nama_bidang : placeholder

  const filtered = options.filter((o) =>
    String(o.nama_bidang ?? '').toLowerCase().includes(search.toLowerCase())
  )

  useEffect(() => {
    const handler = (e) => {
      if (containerRef.current && !containerRef.current.contains(e.target)) {
        setIsOpen(false)
        setSearch('')
      }
    }
    document.addEventListener('mousedown', handler)
    return () => document.removeEventListener('mousedown', handler)
  }, [])

  useEffect(() => {
    const handler = (e) => {
      if (e.key === 'Escape') {
        setIsOpen(false)
        setSearch('')
      }
    }
    document.addEventListener('keydown', handler)
    return () => document.removeEventListener('keydown', handler)
  }, [])

  const handleSelect = (id) => {
    onChange(id)
    setIsOpen(false)
    setSearch('')
  }

  return (
    <div className="org-bidang-dropdown" ref={containerRef}>
      <button
        type="button"
        className={`org-bidang-trigger${!value ? ' is-placeholder' : ''}${isOpen ? ' is-open' : ''}`}
        onClick={() => !disabled && setIsOpen((v) => !v)}
        disabled={disabled}
        aria-haspopup="listbox"
        aria-expanded={isOpen}
      >
        <span>{displayLabel}</span>
        <AdminIcon name="chevron-down" size={16} />
      </button>

      {isOpen && (
        <div className="org-bidang-panel">
          <div className="org-bidang-search">
            <AdminIcon name="search" size={14} />
            <input
              type="text"
              placeholder="Cari bidang..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              autoFocus
            />
          </div>
          <ul className="org-bidang-list" role="listbox">
            <li
              role="option"
              aria-selected={!value}
              className={`org-bidang-item${!value ? ' is-selected' : ''}`}
              onClick={() => handleSelect('')}
            >
              {placeholder}
            </li>
            {filtered.map((opt) => {
              const isSelected = String(opt.id_bidang) === String(value)
              return (
                <li
                  key={opt.id_bidang}
                  role="option"
                  aria-selected={isSelected}
                  className={`org-bidang-item${isSelected ? ' is-selected' : ''}`}
                  onClick={() => handleSelect(opt.id_bidang)}
                >
                  {opt.nama_bidang}
                </li>
              )
            })}
            {filtered.length === 0 && (
              <li className="org-bidang-item org-bidang-empty">Tidak ditemukan</li>
            )}
          </ul>
        </div>
      )}
    </div>
  )
}

export default BidangSelect