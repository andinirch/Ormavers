import { useEffect, useRef, useState } from 'react'
import AdminIcon from '../admin/AdminIcon.jsx'
import FormField from '../form/FormField.jsx'

function EventCategoryDropdown({
  id = 'ev-kategori',
  label = 'Kategori',
  value,
  onChange,
  categories = [],
  placeholder = 'Pilih kategori',
  required = false,
  error,
}) {
  const [isOpen, setIsOpen] = useState(false)
  const containerRef = useRef(null)

  const selectedCategory = categories.find((c) => String(c.id) === String(value))
  const displayLabel = selectedCategory ? selectedCategory.nama_kategori : ''

  useEffect(() => {
    function handleClickOutside(event) {
      if (containerRef.current && !containerRef.current.contains(event.target)) {
        setIsOpen(false)
      }
    }
    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const handleSelect = (selectedId) => {
    onChange({ target: { name: 'id_kategori', value: selectedId ? String(selectedId) : '' } })
    setIsOpen(false)
  }

  return (
    <FormField id={id} label={label} required={required} error={error}>
      <div className="event-category-dropdown" ref={containerRef}>
        <button
          type="button"
          id={id}
          className={`event-category-dropdown__trigger form-input${!displayLabel ? ' is-placeholder' : ''}${isOpen ? ' is-open' : ''}${error ? ' has-error' : ''}`}
          onClick={() => setIsOpen((prev) => !prev)}
          aria-haspopup="listbox"
          aria-expanded={isOpen}
          aria-invalid={Boolean(error)}
        >
          <span className="event-category-dropdown__label">{displayLabel || placeholder}</span>
          <span className={`event-category-dropdown__chevron${isOpen ? ' is-open' : ''}`} aria-hidden="true">
            <AdminIcon name="chevron-down" size={16} />
          </span>
        </button>

        {isOpen && (
          <ul className="event-category-dropdown__menu" role="listbox">
            <li
              role="option"
              aria-selected={!value}
              className={`event-category-dropdown__item${!value ? ' is-selected' : ''}`}
              onClick={() => handleSelect('')}
            >
              {placeholder}
            </li>
            {categories.map((item) => {
              const isSelected = String(item.id) === String(value)
              return (
                <li
                  key={item.id}
                  role="option"
                  aria-selected={isSelected}
                  className={`event-category-dropdown__item${isSelected ? ' is-selected' : ''}`}
                  onClick={() => handleSelect(item.id)}
                >
                  {item.nama_kategori}
                </li>
              )
            })}
          </ul>
        )}
      </div>
    </FormField>
  )
}

export default EventCategoryDropdown
