import { useEffect, useId, useMemo, useRef, useState } from 'react'

export type SearchableOption = {
  label: string
  value: string
}

type SearchableSchoolSelectProps = {
  label: string
  options: SearchableOption[]
  value: string
  placeholder: string
  error?: string
  disabled?: boolean
  /** Nilai `value` untuk opsi "Lainnya" — dipisah dan ditampilkan di bawah daftar. */
  customOptionValue: string
  onChange: (value: string) => void
}

/** Combobox untuk daftar panjang: ketik untuk menyaring, daftar di-scroll terbatas (bukan select bawaan browser). */
export function SearchableSchoolSelect({
  label,
  options,
  value,
  placeholder,
  error,
  disabled,
  customOptionValue,
  onChange,
}: SearchableSchoolSelectProps) {
  const id = useId()
  const listId = `${id}-list`
  const rootRef = useRef<HTMLDivElement>(null)
  const searchInputRef = useRef<HTMLInputElement>(null)
  const [isOpen, setIsOpen] = useState(false)
  const [searchQuery, setSearchQuery] = useState('')

  const { regularOptions, customOption } = useMemo(() => {
    const custom = options.find((option) => option.value === customOptionValue) ?? null
    const rest = options.filter((option) => option.value !== customOptionValue)

    return { regularOptions: rest, customOption: custom }
  }, [options, customOptionValue])

  const filteredRegular = useMemo(() => {
    const q = searchQuery.trim().toLowerCase()
    if (q === '') {
      return regularOptions
    }

    return regularOptions.filter((option) => option.label.toLowerCase().includes(q))
  }, [regularOptions, searchQuery])

  const showCustomPinned = customOption !== null

  const selectedLabel = useMemo(() => options.find((option) => option.value === value)?.label ?? '', [options, value])

  useEffect(() => {
    function handlePointerDown(event: MouseEvent) {
      if (!rootRef.current?.contains(event.target as Node)) {
        setIsOpen(false)
      }
    }

    document.addEventListener('mousedown', handlePointerDown)

    return () => document.removeEventListener('mousedown', handlePointerDown)
  }, [])

  useEffect(() => {
    if (isOpen) {
      searchInputRef.current?.focus()
    }
  }, [isOpen])

  function choose(optionValue: string) {
    onChange(optionValue)
    setIsOpen(false)
    setSearchQuery('')
  }

  const totalRegular = regularOptions.length
  const shownRegular = filteredRegular.length

  return (
    <div ref={rootRef} className="relative flex flex-col gap-2 font-bold text-[#111111]">
      <span id={`${id}-label`}>{label}</span>
      <p className="-mt-1 text-xs font-semibold leading-5 text-[#111111]/50">Ketik untuk menyaring nama sekolah — tidak perlu scroll seluruh daftar.</p>

      <button
        type="button"
        disabled={disabled}
        aria-invalid={Boolean(error)}
        aria-expanded={isOpen}
        aria-haspopup="listbox"
        aria-controls={listId}
        aria-labelledby={`${id}-label`}
        onClick={() => {
          if (!disabled) {
            setIsOpen((open) => !open)
          }
        }}
        className="flex min-h-[52px] w-full items-center rounded-full border border-black/15 bg-white px-5 py-3 text-left font-semibold outline-none transition focus:border-[#ed3833] focus:ring-4 focus:ring-[#ed3833]/10 disabled:cursor-not-allowed disabled:bg-black/[0.04] disabled:text-[#111111]/45 aria-[invalid=true]:border-[#ed3833]"
      >
        <span className={selectedLabel ? 'text-[#111111]' : 'text-[#111111]/45'}>{selectedLabel || placeholder}</span>
      </button>

      {isOpen && !disabled ? (
        <div
          id={listId}
          role="listbox"
          aria-labelledby={`${id}-label`}
          className="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-[22px] border border-black/10 bg-white shadow-[0_20px_60px_rgba(0,0,0,0.12)]"
        >
          <div className="border-b border-black/8 p-3">
            <input
              ref={searchInputRef}
              type="search"
              value={searchQuery}
              autoComplete="off"
              placeholder="Cari nama sekolah…"
              aria-label="Cari nama sekolah"
              onChange={(event) => setSearchQuery(event.target.value)}
              onKeyDown={(event) => {
                if (event.key === 'Escape') {
                  setIsOpen(false)
                }
              }}
              className="w-full rounded-full border border-black/12 px-4 py-3 text-sm font-semibold outline-none focus:border-[#ed3833] focus:ring-2 focus:ring-[#ed3833]/15"
            />
            {totalRegular > 0 ? (
              <p className="mt-2 px-1 text-xs font-semibold text-[#111111]/45">
                Menampilkan {shownRegular} dari {totalRegular} sekolah
                {searchQuery.trim() ? ` untuk “${searchQuery.trim()}”` : ''}
              </p>
            ) : null}
          </div>

          <ul className="max-h-[min(280px,40vh)] overflow-y-auto overscroll-contain py-1">
            {filteredRegular.length === 0 ? (
              <li className="px-4 py-3 text-sm font-semibold text-[#111111]/45">
                {totalRegular === 0
                  ? 'Belum ada entri sekolah untuk disaring. Pilih Lainnya di bawah jika nama tidak tercantum.'
                  : 'Tidak ada yang cocok. Ubah kata kunci atau pilih Lainnya di bawah.'}
              </li>
            ) : (
              filteredRegular.map((option) => (
                <li key={option.value} role="presentation">
                  <button
                    type="button"
                    role="option"
                    aria-selected={value === option.value}
                    onClick={() => choose(option.value)}
                    className={`flex w-full px-4 py-3 text-left text-sm font-semibold transition hover:bg-[#fff7f7] ${value === option.value ? 'bg-[#fff7f7] text-[#ed3833]' : 'text-[#111111]'}`}
                  >
                    {option.label}
                  </button>
                </li>
              ))
            )}
          </ul>

          {showCustomPinned && customOption ? (
            <div className="border-t border-black/10 bg-[#f7f7fd] p-2">
              <button
                type="button"
                role="option"
                aria-selected={value === customOption.value}
                onClick={() => choose(customOption.value)}
                className={`w-full rounded-2xl px-4 py-3 text-left text-sm font-black transition hover:bg-white ${value === customOption.value ? 'bg-white text-[#ed3833] ring-2 ring-[#ed3833]/25' : 'text-[#111111]'}`}
              >
                {customOption.label}
              </button>
            </div>
          ) : null}
        </div>
      ) : null}

      {error ? <span className="text-sm font-bold text-[#ed3833]">{error}</span> : null}
    </div>
  )
}
