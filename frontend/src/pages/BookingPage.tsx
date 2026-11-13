import { useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { AppNavbar } from '../components/AppNavbar'
import { SearchableSchoolSelect } from '../components/SearchableSchoolSelect'
import { useHut500Data } from '../hooks/useHut500Data'
import {
  createRegistration,
  fetchMosques,
  fetchSchoolNameMatches,
  getSchoolOptions,
  saveLastRegistration,
  type ApiMosque,
  type ApiOption,
  type InstitutionType,
  type SchoolNameMatch,
} from '../lib/api'
import { getEditionBenefits, toEdition } from '../lib/editions'
import { isValidIndonesianPhone, normalizeIndonesianPhone } from '../lib/phone'
import { formatRupiah, getBasePrice } from '../lib/payment'

type EducationLevel = 'SD' | 'SMP' | 'SMA' | 'UMUM'

type FormValues = {
  name: string
  phone_number: string
  school_name: string
  mosque_id: string
  custom_school_name: string
  email: string
  district_id: string
  education_level: EducationLevel
  institution_type: InstitutionType
  edition: string
  nik: string
  address: string
}

type FormErrorKey = keyof FormValues | 'address_confirmation'
type FormErrors = Partial<Record<FormErrorKey, string>>
type SchoolMatchState = {
  query: string
  districtId: string
  educationLevel: EducationLevel
  matches: SchoolNameMatch[]
}

const initialValues: FormValues = {
  name: '',
  phone_number: '',
  school_name: '',
  mosque_id: '',
  custom_school_name: '',
  email: '',
  district_id: '',
  education_level: 'SMA',
  institution_type: 'SEKOLAH',
  edition: 'reguler',
  nik: '',
  address: '',
}

const institutionChoices: { value: InstitutionType; label: string }[] = [
  { value: 'SEKOLAH', label: 'Sekolah' },
  { value: 'MASJID', label: 'Masjid' },
  { value: 'KOMUNITAS', label: 'Komunitas' },
  { value: 'UMUM', label: 'Umum / Lembaga' },
]

function institutionNameLabel(institutionType: InstitutionType): string {
  switch (institutionType) {
    case 'MASJID':
      return 'Nama Masjid'
    case 'KOMUNITAS':
      return 'Nama Komunitas'
    case 'UMUM':
      return 'Nama Lembaga / Instansi'
    default:
      return 'Instansi / Perguruan Tinggi'
  }
}

function institutionNamePlaceholder(institutionType: InstitutionType): string {
  switch (institutionType) {
    case 'MASJID':
      return 'Contoh: Masjid Al-Azhar Kebayoran'
    case 'KOMUNITAS':
      return 'Contoh: Komunitas Literasi Jakarta'
    case 'UMUM':
      return 'Contoh: Yayasan / perusahaan / instansi'
    default:
      return 'Cari sekolah atau instansi Anda'
  }
}

function institutionPersonalDocsLabel(institutionType: InstitutionType): string {
  switch (institutionType) {
    case 'MASJID':
      return 'masjid'
    case 'KOMUNITAS':
      return 'komunitas'
    case 'UMUM':
      return 'lembaga atau instansi'
    default:
      return 'sekolah'
  }
}

function requiresPersonalDocs(values: Pick<FormValues, 'institution_type'>) {
  // NIK & alamat wajib bagi peserta non-sekolah (masjid, komunitas, umum),
  // karena verifikasi tidak bisa melalui institusi sekolah formal.
  return values.institution_type !== 'SEKOLAH'
}

const fallbackEditionChoices = [
  {
    value: 'kampung',
    title: 'Mushaf Kampung',
    description: 'Untuk warga kampung, dihimpun per kecamatan dalam satu mushaf bersama.',
  },
  {
    value: 'reguler',
    title: 'Reguler',
    description: 'Untuk peserta umum dengan alur pendaftaran standar.',
  },
  {
    value: 'vip',
    title: 'VIP',
    description: 'Untuk peserta prioritas dengan fasilitas akses khusus.',
  },
]

const customSchoolValue = '__custom_school__'

export function BookingPage() {
  const navigate = useNavigate()
  const { districts, isLoading, error, priceCategories, priceBySlug } = useHut500Data()
  const [values, setValues] = useState<FormValues>(initialValues)
  const [errors, setErrors] = useState<FormErrors>({})
  const [hasSubmitted, setHasSubmitted] = useState(false)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [submitMessage, setSubmitMessage] = useState('')
  const [hasConfirmedAddressReview, setHasConfirmedAddressReview] = useState(false)
  const [hasChosenEdition, setHasChosenEdition] = useState(false)
  const [isEditionModalOpen, setIsEditionModalOpen] = useState(true)
  const [schoolOptions, setSchoolOptions] = useState<ApiOption[]>([])
  const [isLoadingSchools, setIsLoadingSchools] = useState(false)
  const [schoolLoadError, setSchoolLoadError] = useState('')
  const [mosqueOptions, setMosqueOptions] = useState<ApiMosque[]>([])
  const [selectedMosque, setSelectedMosque] = useState<ApiMosque | null>(null)
  const [mosqueSearchQuery, setMosqueSearchQuery] = useState('')
  const [isLoadingMosques, setIsLoadingMosques] = useState(false)
  const [mosqueLoadError, setMosqueLoadError] = useState('')
  const [customSchoolMatchState, setCustomSchoolMatchState] = useState<SchoolMatchState>({
    query: '',
    districtId: '',
    educationLevel: initialValues.education_level,
    matches: [],
  })
  const [didYouMeanDismissed, setDidYouMeanDismissed] = useState(false)

  // PERBAIKAN: Mencegah Glitch dengan menggabungkan deskripsi
  const editionChoices = useMemo(() => {
    if (priceCategories.length > 0) {
      return priceCategories.map((c) => {
        const fallback = fallbackEditionChoices.find((f) => f.value === c.slug)
        return {
          value: c.slug,
          title: c.name,
          description: fallback
            ? `${fallback.description} Tarif pendaftaran: ${formatRupiah(c.amount)}.`
            : `Tarif pendaftaran ${formatRupiah(c.amount)}.`,
        }
      })
    }
    return fallbackEditionChoices
  }, [priceCategories])

  const basePrice =
    priceBySlug[values.edition] ??
    getBasePrice(toEdition(values.edition))
  const selectedEdition = editionChoices.find((option) => option.value === values.edition) ?? editionChoices[0]
  
  const schoolSelectOptions = useMemo(
    () => [
      ...schoolOptions,
      {
        label: 'Lainnya / belum ada di daftar',
        value: customSchoolValue,
      },
    ],
    [schoolOptions],
  )

  const mosqueSelectOptions = useMemo(() => {
    const list =
      selectedMosque && !mosqueOptions.some((mosque) => mosque.id === selectedMosque.id)
        ? [selectedMosque, ...mosqueOptions]
        : mosqueOptions

    return [
      ...list.map((mosque) => ({
        label: `${mosque.name}${mosque.district_name ? ` — ${mosque.district_name}` : mosque.city ? ` — ${mosque.city}` : ''}`,
        value: String(mosque.id),
      })),
      {
        label: 'Lainnya / belum ada di daftar DMI',
        value: customSchoolValue,
      },
    ]
  }, [mosqueOptions, selectedMosque])

  const mosqueFieldResetKey = `${values.institution_type}-${values.district_id}`
  const mosqueSelectValue =
    values.mosque_id || (values.school_name === customSchoolValue ? customSchoolValue : '')
  const schoolFieldResetKey = `${values.district_id}-${values.education_level}-${schoolOptions.length}-${isLoadingSchools ? '1' : '0'}`
  const canChooseSchool = values.education_level === 'UMUM' || Boolean(values.district_id)
  const schoolCatalogHint = schoolEmptyCatalogHint(values, isLoadingSchools, schoolLoadError, schoolOptions.length)
  const needsPersonalDocs = requiresPersonalDocs(values)
  const trimmedAddress = values.address.trim()
  const activeSchoolMatchQuery =
    values.school_name === customSchoolValue && !didYouMeanDismissed
      ? values.custom_school_name.trim()
      : ''
  const shouldFetchSchoolMatches = activeSchoolMatchQuery.length >= 3
  const visibleCustomSchoolMatches =
    shouldFetchSchoolMatches &&
    customSchoolMatchState.query === activeSchoolMatchQuery &&
    customSchoolMatchState.districtId === values.district_id &&
    customSchoolMatchState.educationLevel === values.education_level
      ? customSchoolMatchState.matches
      : []

  useEffect(() => {
    let isMounted = true

    async function loadSchoolOptions() {
      setSchoolLoadError('')
      setSchoolOptions([])

      if (values.institution_type !== 'SEKOLAH' || !values.district_id) {
        return
      }

      setIsLoadingSchools(true)

      try {
        const options = await getSchoolOptions(values.district_id, values.education_level)

        if (!isMounted) {
          return
        }

        setSchoolOptions(options)
      } catch (error) {
        if (!isMounted) {
          return
        }

        setSchoolLoadError(error instanceof Error ? error.message : 'Pilihan institusi tidak dapat dimuat saat ini.')
      } finally {
        if (isMounted) {
          setIsLoadingSchools(false)
        }
      }
    }

    void loadSchoolOptions()

    return () => {
      isMounted = false
    }
  }, [values.district_id, values.education_level, values.institution_type])

  useEffect(() => {
    let isMounted = true
    const controller = new AbortController()

    if (values.institution_type !== 'MASJID' || !values.district_id) {
      return () => controller.abort()
    }

    const query = mosqueSearchQuery.trim()
    const timer = window.setTimeout(() => {
      setMosqueLoadError('')
      setIsLoadingMosques(true)

      void fetchMosques({
        query: query.length >= 2 ? query : undefined,
        districtId: values.district_id,
        limit: 25,
        signal: controller.signal,
      })
        .then((options) => {
          if (isMounted) {
            setMosqueOptions(options)
          }
        })
        .catch((error) => {
          if (error instanceof DOMException && error.name === 'AbortError') {
            return
          }

          if (isMounted) {
            setMosqueOptions([])
            setMosqueLoadError(
              error instanceof Error ? error.message : 'Data masjid tidak dapat dimuat saat ini.',
            )
          }
        })
        .finally(() => {
          if (isMounted) {
            setIsLoadingMosques(false)
          }
        })
    }, 300)

    return () => {
      isMounted = false
      window.clearTimeout(timer)
      controller.abort()
    }
  }, [mosqueSearchQuery, values.district_id, values.institution_type])

  // Fuzzy did-you-mean: hanya aktif saat user memilih "Lainnya" dan mengetik
  // nama bebas. Endpoint backend sudah menormalisasi & threshold di server side.
  useEffect(() => {
    if (!shouldFetchSchoolMatches) {
      return
    }

    const query = activeSchoolMatchQuery
    const controller = new AbortController()
    const timer = window.setTimeout(() => {
      void fetchSchoolNameMatches({
        query,
        educationLevel: values.education_level,
        districtId: values.district_id || undefined,
        signal: controller.signal,
      })
        .then((matches) => {
          setCustomSchoolMatchState({
            query,
            districtId: values.district_id,
            educationLevel: values.education_level,
            matches,
          })
        })
        .catch((err) => {
          if (err instanceof DOMException && err.name === 'AbortError') {
            return
          }
          setCustomSchoolMatchState({
            query,
            districtId: values.district_id,
            educationLevel: values.education_level,
            matches: [],
          })
        })
    }, 350)

    return () => {
      window.clearTimeout(timer)
      controller.abort()
    }
  }, [
    activeSchoolMatchQuery,
    values.district_id,
    values.education_level,
    shouldFetchSchoolMatches,
  ])

  function chooseEdition(edition: string) {
    const nextValues = { ...values, edition }

    if (!requiresPersonalDocs(nextValues)) {
      nextValues.nik = ''
      nextValues.address = ''
    }

    setHasConfirmedAddressReview(false)
    setValues(nextValues)
    setHasChosenEdition(true)
    setIsEditionModalOpen(false)

    if (hasSubmitted) {
      setErrors(buildFormErrors(nextValues, priceBySlug, false))
    }
  }

  function updateValue(name: keyof FormValues, value: string) {
    const nextValues = { ...values, [name]: value } as FormValues
    let nextAddressConfirmation = hasConfirmedAddressReview

    if (name === 'institution_type') {
      const nextInstitutionType = value as InstitutionType
      nextValues.education_level = nextInstitutionType === 'SEKOLAH' ? 'SMA' : 'UMUM'
    }

    if (name === 'district_id' || name === 'education_level' || name === 'institution_type') {
      nextValues.school_name = ''
      nextValues.mosque_id = ''
      nextValues.custom_school_name = ''
      setDidYouMeanDismissed(false)
    }

    if (name === 'district_id' || name === 'institution_type') {
      setSelectedMosque(null)
      setMosqueSearchQuery('')
      setMosqueOptions([])
      setMosqueLoadError('')
      setIsLoadingMosques(false)
    }

    if (name === 'school_name' && value !== customSchoolValue) {
      setDidYouMeanDismissed(false)
    }

    if (name === 'custom_school_name') {
      setDidYouMeanDismissed(false)
    }

    if (name === 'nik') {
      nextValues.nik = value.replace(/\D/g, '').slice(0, 16)
    }

    if (name === 'address') {
      nextAddressConfirmation = false
    }

    if ((name === 'education_level' || name === 'institution_type') && !requiresPersonalDocs(nextValues)) {
      nextValues.nik = ''
      nextValues.address = ''
      nextAddressConfirmation = false
    }

    setHasConfirmedAddressReview(nextAddressConfirmation)
    setValues(nextValues)

    if (hasSubmitted) {
      setErrors(buildFormErrors(nextValues, priceBySlug, nextAddressConfirmation))
    }
  }

  function updateMosqueSelection(next: string) {
    const requestedMosque =
      mosqueOptions.find((mosque) => String(mosque.id) === next) ??
      (selectedMosque && String(selectedMosque.id) === next ? selectedMosque : null)
    const isCustom = next === customSchoolValue
    const nextValues: FormValues = {
      ...values,
      mosque_id: isCustom ? '' : requestedMosque ? String(requestedMosque.id) : '',
      school_name: isCustom ? customSchoolValue : requestedMosque?.name ?? '',
      custom_school_name: isCustom ? values.custom_school_name : '',
    }

    setSelectedMosque(requestedMosque)
    setDidYouMeanDismissed(false)
    setValues(nextValues)

    if (hasSubmitted) {
      setErrors(buildFormErrors(nextValues, priceBySlug, hasConfirmedAddressReview))
    }
  }

  function updateAddressReviewConfirmation(checked: boolean) {
    setHasConfirmedAddressReview(checked)

    if (hasSubmitted) {
      setErrors(buildFormErrors(values, priceBySlug, checked))
    }
  }

  function adoptSchoolSuggestion(value: string) {
    setValues((current) => ({
      ...current,
      school_name: value,
      custom_school_name: '',
    }))
    setCustomSchoolMatchState({
      query: '',
      districtId: '',
      educationLevel: values.education_level,
      matches: [],
    })
    setDidYouMeanDismissed(false)

    if (hasSubmitted) {
      setErrors(
        validate(
          { ...values, school_name: value, custom_school_name: '' },
          priceBySlug,
        ),
      )
    }
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setHasSubmitted(true)
    setSubmitMessage('')

    if (!hasChosenEdition) {
      setIsEditionModalOpen(true)
      setSubmitMessage('Pilih kategori pendaftaran terlebih dahulu.')
      return
    }

    const nextErrors = buildFormErrors(values, priceBySlug, hasConfirmedAddressReview)
    setErrors(nextErrors)

    if (Object.keys(nextErrors).length > 0) {
      return
    }

    setIsSubmitting(true)

    try {
      const includePersonalDocs = requiresPersonalDocs(values)

      const response = await createRegistration({
        district_id: Number(values.district_id),
        education_level: values.education_level,
        institution_type: values.institution_type,
        mosque_id:
          values.institution_type === 'MASJID' && values.mosque_id
            ? Number(values.mosque_id)
            : undefined,
        edition: values.edition,
        name: values.name.trim(),
        phone_number: normalizeIndonesianPhone(values.phone_number),
        school_name: resolveSchoolName(values),
        exclude_from_school_suggestions:
          values.institution_type === 'SEKOLAH' && values.school_name === customSchoolValue,
        email: values.email.trim() || undefined,
        nik: includePersonalDocs ? values.nik.trim() : undefined,
        address: includePersonalDocs ? values.address.trim() : undefined,
      })

      saveLastRegistration(response.data)
      navigate('/booking/finished', { state: response.data })
    } catch (error) {
      setSubmitMessage(error instanceof Error ? error.message : 'Pendaftaran belum dapat diproses. Silakan coba lagi.')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <>
      <AppNavbar />
      <main className="min-h-[calc(100vh-73px)] bg-[#f4f0ea] px-5 py-6">
        <section className="mx-auto w-full max-w-[920px]">
          <header className="relative overflow-hidden rounded-[32px] bg-ink p-5 text-white shadow-[0_30px_80px_-52px_rgba(0,0,0,0.8)] sm:p-7 lg:p-8">
            <div
              aria-hidden="true"
              className="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-[radial-gradient(circle,rgba(216,180,90,0.24),transparent_68%)] blur-2xl"
            />
            <img
              src="/assets/images/logos/logo-jejaka500.png"
              alt=""
              width={512}
              height={512}
              loading="lazy"
              decoding="async"
              draggable={false}
              className="seal-watermark seal-watermark-engraved pointer-events-none absolute -bottom-20 -right-16 h-56 w-56 rounded-full object-cover opacity-[0.16]"
            />
            <div className="relative z-10 flex items-center justify-between text-[10px] font-black uppercase tracking-[0.18em] text-white/45">
              <p>Area Pendaftaran</p>
              <p>JEJAKA500</p>
            </div>
            <div className="relative z-10 mt-10 max-w-[700px]">
              <p className="mb-4 w-fit rounded-full bg-white/10 px-4 py-2 text-sm font-black text-white">Langkah 1 dari 2</p>
              <h1 className="text-[40px] font-black leading-[0.94] tracking-[-0.06em] sm:text-[62px]">
                Tentukan Langkah Anda.
              </h1>
              <p className="mt-5 max-w-[640px] text-base font-medium leading-7 text-white/62">
                Pilih Mushaf Kampung untuk warga kampung per kecamatan, Reguler untuk pendaftar umum, atau VIP dengan perlengkapan tambahan.
              </p>
            </div>
          </header>

          {error ? (
            <p className="mt-6 rounded-2xl bg-[#fff7f7] px-5 py-4 text-sm font-bold text-[#ed3833]">
              Data pendaftaran belum dapat dimuat. {error}
            </p>
          ) : null}

          {hasChosenEdition ? (
            <form onSubmit={handleSubmit} noValidate className="mt-5 rounded-[32px] border border-black/10 bg-white p-5 shadow-[0_24px_90px_rgba(0,0,0,0.06)] sm:p-7">
              <div className="grid gap-4 rounded-[26px] bg-[#111111] p-5 text-white sm:grid-cols-[1fr_auto] sm:items-center">
                <div>
                  <p className="text-sm font-black uppercase tracking-[0.16em] text-white/45">Jalur Pilihan</p>
                  <h2 className="mt-2 text-[28px] font-black leading-[0.95] tracking-[-0.05em]">{selectedEdition.title}</h2>
                  <p className="mt-3 text-sm font-semibold leading-6 text-white/58">{selectedEdition.description}</p>
                  <EditionBenefitList edition={selectedEdition.value} className="mt-4 text-white/80" markerClassName="bg-white/70" />
                </div>
                <div className="flex flex-col gap-3 sm:items-end">
                  <p className="text-[28px] font-black tracking-[-0.05em]">{formatRupiah(basePrice)}</p>
                  <button type="button" onClick={() => setIsEditionModalOpen(true)} className="rounded-full bg-white px-5 py-3 text-sm font-black text-[#ed3833] transition hover:bg-[#fff7f7]">
                    Ubah Kategori
                  </button>
                </div>
              </div>

              <div className="mt-7">
                <p className="text-sm font-black uppercase tracking-[0.16em] text-[#ed3833]">Data Profil</p>
                <h3 className="mt-3 text-[30px] font-black leading-[36px] tracking-[-0.05em] text-[#111111] sm:text-[40px] sm:leading-[46px]">
                  Lengkapi identitas peserta.
                </h3>
              </div>

              <div className="mt-8 grid grid-cols-1 gap-4">
                <Field label="Nama Lengkap" name="name" value={values.name} error={errors.name} placeholder="Sesuai kartu identitas" onChange={updateValue} />
                <Field label="Nomor WhatsApp" name="phone_number" value={values.phone_number} error={errors.phone_number} placeholder="Contoh: 081234567890" type="tel" onChange={updateValue} />
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <Select label="Kecamatan Aktif" name="district_id" value={values.district_id} error={errors.district_id} placeholder={isLoading ? 'Memuat data...' : 'Pilih area pendaftaran'} options={districts.map((district) => ({ label: district.name, value: String(district.id) }))} onChange={updateValue} />
                  <Select label="Jenis Institusi" name="institution_type" value={values.institution_type} error={errors.institution_type} options={institutionChoices.map((choice) => ({ label: choice.label, value: choice.value }))} onChange={updateValue} />
                </div>
                {values.institution_type === 'SEKOLAH' ? (
                  <>
                    <Select label="Jenjang / Kategori" name="education_level" value={values.education_level} error={errors.education_level} placeholder="Pilih jenjang pendidikan" options={['SD', 'SMP', 'SMA'].map((level) => ({ label: level, value: level }))} onChange={updateValue} />
                    <SearchableSchoolSelect
                      key={schoolFieldResetKey}
                      label="Instansi / Perguruan Tinggi"
                      options={canChooseSchool ? schoolSelectOptions : []}
                      value={values.school_name}
                      placeholder={schoolSelectPlaceholder(values, isLoadingSchools, schoolLoadError)}
                      error={errors.school_name}
                      disabled={!canChooseSchool || isLoadingSchools}
                      customOptionValue={customSchoolValue}
                      onChange={(next) => updateValue('school_name', next)}
                    />
                    {schoolLoadError ? <p className="rounded-2xl bg-[#fff7f7] p-4 text-sm font-bold leading-6 text-[#ed3833]">{schoolLoadError}</p> : null}
                    {schoolCatalogHint ? <p className="rounded-2xl bg-[#f7f7fd] p-4 text-sm font-semibold leading-6 text-[#111111]/65">{schoolCatalogHint}</p> : null}
                    {values.school_name === customSchoolValue ? (
                      <div className="flex flex-col gap-3">
                        <Field label="Nama Instansi Spesifik" name="custom_school_name" value={values.custom_school_name} error={errors.custom_school_name} placeholder="Tuliskan nama instansi atau sekolah Anda" onChange={updateValue} />
                        {visibleCustomSchoolMatches.length > 0 && !didYouMeanDismissed ? (
                          <div className="rounded-[20px] border border-[#ed3833]/25 bg-[#fff7f7]/70 p-4">
                            <div className="flex items-start justify-between gap-3">
                              <div>
                                <p className="text-xs font-black uppercase tracking-[0.18em] text-[#ed3833]">Saran Penulisan</p>
                                <p className="mt-1 text-sm font-semibold leading-6 text-[#111111]">
                                  Apakah maksud Anda salah satu sekolah berikut? Pilih agar peserta dari sekolah yang sama otomatis tergabung dalam satu gelombang.
                                </p>
                              </div>
                              <button
                                type="button"
                                onClick={() => setDidYouMeanDismissed(true)}
                                className="shrink-0 rounded-full border border-black/10 px-3 py-2 text-[11px] font-black uppercase tracking-[0.14em] text-[#111111]/65 transition hover:border-[#ed3833] hover:text-[#ed3833]"
                              >
                                Nama Saya Benar
                              </button>
                            </div>
                            <div className="mt-4 flex flex-wrap gap-2">
                              {visibleCustomSchoolMatches.map((match) => (
                                <button
                                  key={`${match.value}-${match.score ?? ''}`}
                                  type="button"
                                  onClick={() => adoptSchoolSuggestion(match.value)}
                                  className="rounded-full border border-[#ed3833]/40 bg-white px-4 py-2 text-sm font-bold text-[#ed3833] transition hover:bg-[#ed3833] hover:text-white"
                                >
                                  {match.label}
                                </button>
                              ))}
                            </div>
                          </div>
                        ) : null}
                      </div>
                    ) : null}
                  </>
                ) : values.institution_type === 'MASJID' ? (
                  <>
                    <SearchableSchoolSelect
                      key={mosqueFieldResetKey}
                      label="Nama Masjid (data DMI)"
                      options={mosqueSelectOptions}
                      value={mosqueSelectValue}
                      placeholder={
                        !values.district_id
                          ? 'Pilih kecamatan aktif dahulu'
                          : isLoadingMosques
                            ? 'Memuat data masjid...'
                            : 'Cari nama masjid'
                      }
                      error={errors.school_name}
                      disabled={!values.district_id}
                      customOptionValue={customSchoolValue}
                      entityLabel="masjid"
                      helperText="Ketik nama masjid untuk mencari katalog DMI. Pilih 'Lainnya' jika masjid belum terdaftar."
                      searchPlaceholder="Cari nama masjid…"
                      emptyText="Belum ada masjid yang cocok di katalog DMI. Pilih 'Lainnya' lalu ketik nama masjid Anda."
                      remote
                      onSearch={setMosqueSearchQuery}
                      onChange={updateMosqueSelection}
                    />
                    {mosqueLoadError ? (
                      <p className="rounded-2xl bg-[#fff7f7] p-4 text-sm font-bold leading-6 text-[#ed3833]">
                        {mosqueLoadError}
                      </p>
                    ) : null}
                    {values.school_name === customSchoolValue ? (
                      <Field
                        label="Nama Masjid"
                        name="custom_school_name"
                        value={values.custom_school_name}
                        error={errors.custom_school_name}
                        placeholder="Tuliskan nama masjid Anda"
                        onChange={updateValue}
                      />
                    ) : null}
                  </>
                ) : (
                  <Field
                    label={institutionNameLabel(values.institution_type)}
                    name="school_name"
                    value={values.school_name}
                    error={errors.school_name}
                    placeholder={institutionNamePlaceholder(values.institution_type)}
                    onChange={updateValue}
                  />
                )}
                <Field label="Alamat Email (Opsional)" name="email" value={values.email} error={errors.email} placeholder="Surat elektronik aktif" type="email" onChange={updateValue} />

                {needsPersonalDocs ? (
                  <div className="mt-2 rounded-[26px] border border-[#ed3833]/30 bg-[#fff7f7]/60 p-5">
                    <div className="flex items-start justify-between gap-3">
                      <div>
                        <p className="text-xs font-black uppercase tracking-[0.16em] text-[#ed3833]">Data Identitas Pribadi</p>
                        <h4 className="mt-2 text-[22px] font-black leading-tight tracking-[-0.04em] text-[#111111] sm:text-[26px]">
                          Verifikasi identitas peserta non-sekolah.
                        </h4>
                        <p className="mt-2 text-sm font-semibold leading-6 text-[#111111]/62">
                          Data ini diperlukan karena Anda mendaftar sebagai {institutionPersonalDocsLabel(values.institution_type)}, sehingga verifikasi tidak melalui institusi sekolah formal.
                        </p>
                      </div>
                    </div>

                    <div className="mt-5 grid grid-cols-1 gap-4">
                      <Field
                        label="NIK (Nomor Induk Kependudukan)"
                        name="nik"
                        value={values.nik}
                        error={errors.nik}
                        placeholder="16 digit sesuai KTP"
                        type="text"
                        inputMode="numeric"
                        maxLength={16}
                        onChange={updateValue}
                      />
                      <Textarea
                        label="Alamat Lengkap"
                        name="address"
                        value={values.address}
                        error={errors.address}
                        placeholder="Jalan, RT/RW, kelurahan, kecamatan, kota, kode pos"
                        rows={3}
                        maxLength={500}
                        onChange={updateValue}
                      />
                    </div>

                    <div className="mt-5 rounded-[22px] border border-[#ed3833]/20 bg-white p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.7)]">
                      <p className="text-xs font-black uppercase tracking-[0.16em] text-[#ed3833]">Periksa Alamat Pengiriman</p>
                      <p className="mt-2 text-sm font-semibold leading-6 text-[#111111]/70">
                        Alamat ini akan dipakai untuk pengiriman. Periksa kembali nama jalan, RT/RW, kelurahan, kecamatan, kota, dan kode pos sebelum melanjutkan.
                      </p>

                      <div className="mt-4 rounded-[18px] bg-[#faf8f4] p-4">
                        <p className="text-[11px] font-black uppercase tracking-[0.14em] text-[#111111]/42">Alamat yang akan disimpan</p>
                        <p className={`mt-2 text-sm font-semibold leading-6 ${trimmedAddress ? 'text-[#111111]' : 'text-[#111111]/42'}`}>
                          {trimmedAddress || 'Alamat lengkap Anda akan muncul di sini setelah diisi.'}
                        </p>
                      </div>

                      <ul className="mt-4 grid gap-2 text-sm font-semibold leading-6 text-[#111111]/72">
                        <li>Nama penerima dan detail lokasi harus cukup jelas untuk kurir.</li>
                        <li>Gunakan alamat yang benar-benar aktif untuk menerima pengiriman.</li>
                        <li>Jika ada kesalahan penulisan setelah formulir dikirim, segera hubungi layanan bantuan dengan kode pendaftaran.</li>
                      </ul>

                      <label className="mt-4 flex items-start gap-3 rounded-[18px] border border-black/8 bg-[#fff7f7] p-4 text-sm font-bold leading-6 text-[#111111]">
                        <input
                          type="checkbox"
                          checked={hasConfirmedAddressReview}
                          onChange={(event) => updateAddressReviewConfirmation(event.target.checked)}
                          className="mt-1 h-5 w-5 rounded border border-black/20 accent-[#ed3833]"
                        />
                        <span>Saya sudah memeriksa alamat pengiriman dan memastikan data yang saya tulis lengkap serta benar.</span>
                      </label>
                      {errors.address_confirmation ? <p className="mt-3 text-sm font-bold text-[#ed3833]">{errors.address_confirmation}</p> : null}
                    </div>
                  </div>
                ) : null}
              </div>

              <button type="submit" disabled={isSubmitting} className="mt-8 flex w-full justify-center rounded-full bg-[#ed3833] px-6 py-4 font-black text-white transition hover:bg-[#111111] disabled:cursor-not-allowed disabled:opacity-60">
                {isSubmitting ? 'Memproses pendaftaran...' : 'Konfirmasi Pendaftaran'}
              </button>
              {submitMessage ? <p className="mt-4 rounded-2xl bg-[#fff7f7] p-4 text-sm font-bold leading-6 text-[#ed3833]">{submitMessage}</p> : null}
              {Object.keys(errors).length > 0 ? <p className="mt-4 rounded-2xl bg-[#fff7f7] p-4 text-sm font-bold leading-6 text-[#ed3833]">Ada isian yang perlu diperiksa. Periksa kembali kolom yang ditandai merah.</p> : null}
            </form>
          ) : (
            <section className="mt-6 rounded-[38px] border border-black/10 bg-white p-6 text-center shadow-[0_24px_90px_rgba(0,0,0,0.06)] sm:p-8">
              <p className="text-sm font-black uppercase tracking-[0.16em] text-[#ed3833]">Tahap Awal</p>
              <h2 className="mx-auto mt-3 max-w-[620px] text-[36px] font-black leading-[0.96] tracking-[-0.055em] text-[#111111] sm:text-[54px]">
                Pilih jalur partisipasi untuk melanjutkan.
              </h2>
              <button type="button" onClick={() => setIsEditionModalOpen(true)} className="mt-8 rounded-full bg-[#ed3833] px-7 py-4 font-black text-white transition hover:bg-[#111111]">
                Buka Katalog Kategori
              </button>
            </section>
          )}
        </section>
      </main>

      {/* Modal Kategori */}
      {isEditionModalOpen ? (
        <div className="fixed inset-0 z-[80] flex items-start justify-center overflow-y-auto bg-[#111111]/70 px-5 py-8 backdrop-blur-md lg:items-center">
          <section className="w-full max-w-[1080px] rounded-[32px] bg-white p-5 shadow-[0_30px_120px_rgba(0,0,0,0.28)] sm:p-7">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
              <div>
                <p className="text-sm font-black uppercase tracking-[0.16em] text-[#ed3833]">Pilih Kategori</p>
                <h2 className="mt-3 text-[34px] font-black leading-[0.96] tracking-[-0.06em] text-[#111111] sm:text-[50px]">
                  Tentukan Pilihan Anda
                </h2>
                <p className="mt-4 max-w-[560px] text-base font-medium leading-7 text-[#111111]/62">
                  Tentukan pilihan Anda untuk memastikan kuota tersedia.
                </p>
              </div>
              <button type="button" onClick={() => setIsEditionModalOpen(false)} className="rounded-full border border-black/10 px-5 py-3 text-sm font-black text-[#111111] transition hover:border-[#ed3833] hover:text-[#ed3833]">
                Tutup
              </button>
            </div>

            <div className="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
              {isLoading ? (
                <div className="col-span-full py-8 text-center">
                   <p className="motion-safe:animate-pulse font-bold text-[#111111]/50">Memuat pilihan kategori...</p>
                </div>
              ) : (
                editionChoices.map((option) => {
                  const isSelected = values.edition === option.value
  
                  return (
                    <button
                      key={option.value}
                      type="button"
                      onClick={() => chooseEdition(option.value)}
                      aria-pressed={isSelected}
                      className={`group flex flex-col rounded-[26px] border p-5 text-left transition hover:-translate-y-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#ed3833] focus-visible:ring-offset-2 motion-reduce:transition-none motion-reduce:hover:translate-y-0 ${
                        isSelected ? 'border-[#ed3833] bg-[#fff7f7]' : 'border-black/10 bg-white hover:border-[#ed3833]'
                      }`}
                    >
                      <div className="flex items-start justify-between gap-4">
                        <div>
                          <p className="text-sm font-black uppercase tracking-[0.16em] text-[#ed3833]">{option.title}</p>
                          <p className="mt-4 text-[34px] font-black tracking-[-0.06em] text-[#111111]">
                            {formatRupiah(priceBySlug[option.value] ?? getBasePrice(toEdition(option.value)))}
                          </p>
                        </div>
                        <span className={`flex h-11 w-11 items-center justify-center rounded-full text-lg font-black transition ${isSelected ? 'bg-[#ed3833] text-white' : 'bg-[#111111] text-white group-hover:bg-[#ed3833]'}`}>
                          →
                        </span>
                      </div>
                      <p className="mt-5 text-sm font-semibold leading-6 text-[#111111]/58">{option.description}</p>
                      <EditionBenefitList edition={option.value} className="mt-5 border-t border-black/10 pt-4 text-[#111111]/75" />
                    </button>
                  )
                })
              )}
            </div>
          </section>
        </div>
      ) : null}
    </>
  )
}

function EditionBenefitList({
  edition,
  className = '',
  markerClassName = 'bg-[#ed3833]',
}: {
  edition: string
  className?: string
  markerClassName?: string
}) {
  const benefits = getEditionBenefits(edition)
  if (benefits.length === 0) return null

  return (
    <div className={className}>
      <p className="text-xs font-black uppercase tracking-[0.14em] opacity-70">Yang Anda dapatkan</p>
      <ul className="mt-3 space-y-2 text-sm font-semibold leading-6">
        {benefits.map((benefit) => (
          <li key={benefit} className="flex gap-3">
            <span aria-hidden="true" className={`mt-[9px] h-1.5 w-1.5 shrink-0 rounded-full ${markerClassName}`} />
            <span>{benefit}</span>
          </li>
        ))}
      </ul>
    </div>
  )
}

// ... [Fungsi Helper di Bawah Tidak Berubah]

function schoolSelectPlaceholder(values: FormValues, isLoadingSchools: boolean, loadError: string) {
  if (isLoadingSchools) {
    return 'Memuat daftar instansi...'
  }

  if (values.education_level !== 'UMUM' && !values.district_id) {
    return 'Tentukan kecamatan aktif dahulu'
  }

  if (loadError) {
    return 'Data belum dapat dimuat — silakan coba lagi'
  }

  return values.education_level === 'UMUM' ? 'Pilih universitas atau perusahaan' : 'Cari sekolah Anda'
}

function schoolEmptyCatalogHint(values: FormValues, isLoadingSchools: boolean, loadError: string, optionCount: number): string {
  if (loadError || isLoadingSchools || !values.education_level) {
    return ''
  }

  if (values.education_level === 'UMUM') {
    return optionCount > 0 ? '' : 'Daftar perguruan tinggi belum tersedia. Silakan pilih "Lainnya" lalu ketik nama instansi Anda.'
  }

  if (!values.district_id) {
    return ''
  }

  return optionCount > 0 ? '' : 'Daftar institusi belum tersedia untuk wilayah ini. Pilih "Lainnya" lalu ketik nama instansi Anda.'
}

function resolveSchoolName(values: FormValues) {
  return values.school_name === customSchoolValue ? values.custom_school_name.trim() : values.school_name.trim()
}

function buildFormErrors(
  values: FormValues,
  priceBySlug: Record<string, number>,
  hasConfirmedAddressReview: boolean,
): FormErrors {
  const nextErrors = validate(values, priceBySlug)

  if (requiresPersonalDocs(values) && !hasConfirmedAddressReview) {
    nextErrors.address_confirmation = 'Centang konfirmasi setelah memeriksa alamat pengiriman.'
  }

  return nextErrors
}

function validate(values: FormValues, priceBySlug: Record<string, number>): FormErrors {
  const nextErrors: FormErrors = {}

  if (!values.name.trim()) {
    nextErrors.name = 'Nama lengkap wajib diisi.'
  }

  if (!values.phone_number.trim()) {
    nextErrors.phone_number = 'Kontak WhatsApp diperlukan.'
  } else if (!isValidIndonesianPhone(values.phone_number)) {
    nextErrors.phone_number = 'Nomor WhatsApp tidak valid. Contoh: 081234567890 atau 6281234567890.'
  }

  if (!values.school_name.trim()) {
    nextErrors.school_name = 'Nama institusi wajib diisi.'
  } else if (values.school_name === customSchoolValue && !values.custom_school_name.trim()) {
    nextErrors.custom_school_name = 'Tuliskan nama instansi dengan jelas.'
  }

  if (values.email.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email.trim())) {
    nextErrors.email = 'Format alamat email belum sesuai.'
  }

  if (!values.district_id) {
    nextErrors.district_id = 'Kecamatan belum dipilih.'
  }

  if (!['SD', 'SMP', 'SMA', 'UMUM'].includes(values.education_level)) {
    nextErrors.education_level = 'Jenjang pendidikan belum dipilih.'
  }

  if (!['SEKOLAH', 'MASJID', 'KOMUNITAS', 'UMUM'].includes(values.institution_type)) {
    nextErrors.institution_type = 'Jenis institusi belum dipilih.'
  }

  if (!values.edition || priceBySlug[values.edition] === undefined) {
    nextErrors.edition = 'Kategori pendaftaran tidak tersedia. Silakan muat ulang halaman.'
  }

  if (requiresPersonalDocs(values)) {
    const trimmedNik = values.nik.trim()
    if (!trimmedNik) {
      nextErrors.nik = 'NIK wajib diisi untuk peserta non-sekolah.'
    } else if (!/^\d{16}$/.test(trimmedNik)) {
      nextErrors.nik = 'NIK harus 16 digit angka sesuai KTP.'
    }

    const trimmedAddress = values.address.trim()
    if (!trimmedAddress) {
      nextErrors.address = 'Alamat lengkap wajib diisi.'
    } else if (trimmedAddress.length > 500) {
      nextErrors.address = 'Alamat maksimal 500 karakter.'
    }
  }

  return nextErrors
}

type FieldProps = {
  label: string
  name: keyof FormValues
  value: string
  placeholder: string
  error?: string
  type?: string
  inputMode?: 'text' | 'numeric' | 'tel' | 'email' | 'url' | 'search' | 'decimal' | 'none'
  maxLength?: number
  onChange: (name: keyof FormValues, value: string) => void
}

function Field({ label, name, value, placeholder, error, type = 'text', inputMode, maxLength, onChange }: FieldProps) {
  return (
    <label className="flex flex-col gap-2 font-bold text-[#111111]">
      {label}
      <input
        name={name}
        type={type}
        value={value}
        placeholder={placeholder}
        aria-invalid={Boolean(error)}
        inputMode={inputMode}
        maxLength={maxLength}
        onChange={(event) => onChange(name, event.target.value)}
        className="rounded-full border border-black/15 px-5 py-4 font-semibold outline-none transition focus:border-[#ed3833] focus:ring-4 focus:ring-[#ed3833]/10 aria-[invalid=true]:border-[#ed3833]"
      />
      {error ? <span className="text-sm font-bold text-[#ed3833]">{error}</span> : null}
    </label>
  )
}

type TextareaProps = {
  label: string
  name: keyof FormValues
  value: string
  placeholder: string
  error?: string
  rows?: number
  maxLength?: number
  onChange: (name: keyof FormValues, value: string) => void
}

function Textarea({ label, name, value, placeholder, error, rows = 3, maxLength, onChange }: TextareaProps) {
  return (
    <label className="flex flex-col gap-2 font-bold text-[#111111]">
      {label}
      <textarea
        name={name}
        value={value}
        placeholder={placeholder}
        rows={rows}
        maxLength={maxLength}
        aria-invalid={Boolean(error)}
        onChange={(event) => onChange(name, event.target.value)}
        className="rounded-[24px] border border-black/15 px-5 py-4 font-semibold outline-none transition focus:border-[#ed3833] focus:ring-4 focus:ring-[#ed3833]/10 aria-[invalid=true]:border-[#ed3833]"
      />
      <span className="self-end text-xs font-semibold text-[#111111]/45">
        {value.length}{maxLength ? ` / ${maxLength}` : ''}
      </span>
      {error ? <span className="text-sm font-bold text-[#ed3833]">{error}</span> : null}
    </label>
  )
}

type SelectOption = {
  label: string
  value: string
}

type SelectProps = {
  label: string
  name: keyof FormValues
  value: string
  options: SelectOption[]
  error?: string
  placeholder?: string
  onChange: (name: keyof FormValues, value: string) => void
}

function Select({ label, name, value, options, error, placeholder, onChange }: SelectProps) {
  return (
    <label className="flex flex-col gap-2 font-bold text-[#111111]">
      {label}
      <select
        name={name}
        value={value}
        aria-invalid={Boolean(error)}
        onChange={(event) => onChange(name, event.target.value)}
        className="rounded-full border border-black/15 bg-white px-5 py-4 font-semibold outline-none transition focus:border-[#ed3833] focus:ring-4 focus:ring-[#ed3833]/10 aria-[invalid=true]:border-[#ed3833]"
      >
        {placeholder ? (
          <option value="" disabled>
            {placeholder}
          </option>
        ) : null}
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      {error ? <span className="text-sm font-bold text-[#ed3833]">{error}</span> : null}
    </label>
  )
}