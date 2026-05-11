const apiBaseUrl = (import.meta.env.VITE_API_URL ?? 'http://127.0.0.1:8000/api').replace(/\/$/, '')
const lastRegistrationKey = 'hut500:last-registration'

type EducationLevel = 'SD' | 'SMP' | 'SMA' | 'UMUM'

/**
 * Bentuk respons GET /api/registrations/status (statusLookup): district & batch
 * disajikan sebagai string siap-tampil oleh RegistrationStatusResource di backend.
 */
export type RegistrationStatus = {
  id: number
  registration_code: string
  name: string
  school_name: string
  edition: string
  education_level: string
  page_number: number
  status: 'pending' | 'success' | 'failed'
  total_payment: number
  district?: string
  batch?: string
  created_at?: string
  updated_at?: string
}

export type ApiDistrict = {
  id: number
  name: string
  slug: string
  code?: string | null
  photo?: string | null
  batches_count?: number
}

export type ApiBatch = {
  id: number
  district_id: number
  name: string
  slug: string
  batch_number: string | number
  education_level: string
  max_capacity: number
  is_full: boolean
  district?: ApiDistrict | null
  registrations_count?: number
}

export type ApiUniversity = {
  id: number
  name: string
  type: string
  city: string
}

export type ApiOption = {
  label: string
  value: string
}

export type ApiPriceCategory = {
  slug: string
  name: string
  amount: number
}

export type RegistrationPayload = {
  district_id: number
  education_level: EducationLevel
  edition: string
  name: string
  phone_number: string
  school_name: string
  /** true when the participant chose "Lainnya" / typed a name not from the catalog */
  exclude_from_school_suggestions?: boolean
  email?: string
  /** Required only when education_level is UMUM (regardless of edition). */
  nik?: string
  /** Required only when education_level is UMUM (regardless of edition). */
  address?: string
}

/**
 * Bentuk respons POST /api/register (RegistrationResource): district & batch
 * adalah object (relasi penuh), bukan string. Lihat backend RegistrationResource.
 */
export type Registration = {
  id: number
  registration_code: string
  name: string
  phone_number: string
  email?: string | null
  nik?: string | null
  address?: string | null
  school_name: string
  edition: string
  education_level: string
  page_number: number
  status: 'pending' | 'success' | 'failed'
  total_payment: number
  district?: ApiDistrict | null
  batch?: ApiBatch | null
  financial: {
    base_price: number
    total_payment: number
  }
  created_at?: string
  updated_at?: string
}

export type MidtransSnapTokenData = {
  snap_token: string
  client_key: string
  order_id: string
  is_production: boolean
}

type ApiSuccess<T> = {
  success: true
  message: string
  data: T
}

type ApiFailure = {
  success: false
  message: string
  errors?: Record<string, string[]>
  /** Hanya hadir saat APP_DEBUG=true di backend; berisi pesan exception sesungguhnya. */
  error?: string
}

function getHeaders(hasBody = false) {
  return {
    Accept: 'application/json',
    ...(hasBody ? { 'Content-Type': 'application/json' } : {}),
  }
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return Boolean(value) && typeof value === 'object'
}

function isApiSuccess<T>(value: unknown): value is ApiSuccess<T> {
  return isRecord(value) && value.success === true && 'data' in value
}

function isApiFailure(value: unknown): value is ApiFailure {
  return isRecord(value) && value.success === false && typeof value.message === 'string'
}

function getErrorMessage(payload: unknown, fallback: string) {
  if (isApiFailure(payload)) {
    const validationMessage = payload.errors ? Object.values(payload.errors).flat().join(' ') : ''
    const baseMessage = validationMessage || payload.message || fallback

    if (payload.error && !validationMessage) {
      return `${baseMessage} (debug: ${payload.error})`
    }

    return baseMessage
  }

  if (isRecord(payload) && typeof payload.message === 'string') {
    return payload.message
  }

  return fallback
}

async function apiRequest<T>(path: string, options: RequestInit = {}): Promise<ApiSuccess<T>> {
  let response: Response

  try {
    response = await fetch(`${apiBaseUrl}${path}`, {
      ...options,
      headers: {
        ...getHeaders(Boolean(options.body)),
        ...options.headers,
      },
    })
  } catch {
    // Service worker (PWA) bisa men-serve katalog GET dari cache; namun POST
    // sensitif (register, midtrans) selalu NetworkOnly dan akan jatuh ke sini saat offline.
    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
      throw new Error('Tidak ada koneksi internet. Beberapa data publik mungkin tersedia dari cache, namun aksi ini butuh koneksi aktif. Coba lagi setelah online.')
    }
    throw new Error('Tidak bisa terhubung ke backend. Pastikan Laravel API aktif dan VITE_API_URL benar.')
  }

  let payload: unknown

  try {
    payload = await response.json()
  } catch {
    throw new Error(`Backend merespon ${response.status}, tetapi bukan JSON. Periksa URL API dan route Laravel.`)
  }

  if (!response.ok) {
    throw new Error(getErrorMessage(payload, 'Request ke backend gagal.'))
  }

  if (isApiFailure(payload)) {
    throw new Error(getErrorMessage(payload, 'Request ke backend gagal.'))
  }

  if (isApiSuccess<T>(payload)) {
    return {
      ...payload,
      data: unwrapResource(payload.data) as T,
    }
  }

  return {
    success: true,
    message: '',
    data: unwrapResource(payload) as T,
  }
}

function unwrapResource(data: unknown): unknown {
  if (isRecord(data) && 'data' in data) {
    return unwrapResource(data.data)
  }

  return data
}

export async function createRegistration(payload: RegistrationPayload): Promise<ApiSuccess<Registration>> {
  return apiRequest<Registration>('/register', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function createMidtransSnapToken(registrationCode: string): Promise<ApiSuccess<MidtransSnapTokenData>> {
  const normalized = registrationCode.replace(/\s+/g, '').toUpperCase()

  return apiRequest<MidtransSnapTokenData>('/midtrans/snap-token', {
    method: 'POST',
    body: JSON.stringify({ registration_code: normalized }),
  })
}

export async function getRegistrationStatus(lookup: string): Promise<ApiSuccess<RegistrationStatus[]>> {
  const normalizedLookup = lookup.replace(/\s+/g, '').toUpperCase()
  const params = new URLSearchParams({ lookup: normalizedLookup })

  const response = await apiRequest<RegistrationStatus[] | RegistrationStatus>(
    `/registrations/status?${params.toString()}`,
  )

  const items = Array.isArray(response.data) ? response.data : [response.data]

  return {
    ...response,
    data: items,
  }
}

export async function getDistricts(): Promise<ApiDistrict[]> {
  const response = await apiRequest<ApiDistrict[]>('/districts')

  return response.data
}

export async function getBatches(): Promise<ApiBatch[]> {
  const response = await apiRequest<ApiBatch[]>('/batches')

  return response.data
}

export async function getUniversities(): Promise<ApiUniversity[]> {
  const response = await apiRequest<ApiUniversity[]>('/universities')

  return response.data
}

export async function getPriceCategories(): Promise<ApiPriceCategory[]> {
  const response = await apiRequest<ApiPriceCategory[]>('/price-categories')

  return response.data
}

export type PaymentUiStatus = RegistrationStatus['status']

export async function syncMidtransRegistrationStatus(registrationCode: string): Promise<PaymentUiStatus> {
  const normalized = registrationCode.replace(/\s+/g, '').toUpperCase()

  const { data } = await apiRequest<{ payment_status: string }>('/midtrans/sync-status', {
    method: 'POST',
    body: JSON.stringify({ registration_code: normalized }),
  })

  if (data.payment_status === 'success' || data.payment_status === 'failed' || data.payment_status === 'pending') {
    return data.payment_status
  }

  return 'pending'
}

export async function getSchoolOptions(districtId: string, educationLevel: EducationLevel): Promise<ApiOption[]> {
  const params = new URLSearchParams({ education_level: educationLevel })

  if (districtId) {
    params.set('district_id', districtId)
  }

  const response = await apiRequest<ApiOption[]>(`/school-options?${params.toString()}`)

  return response.data
}

export type SchoolNameMatch = ApiOption & {
  /** Skor similarity 0..1 (untuk debug, jangan ditampilkan ke user). */
  score?: number
}

export async function fetchSchoolNameMatches(params: {
  query: string
  educationLevel: EducationLevel
  districtId?: string
  signal?: AbortSignal
}): Promise<SchoolNameMatch[]> {
  const trimmed = params.query.trim()
  if (trimmed.length < 2) {
    return []
  }

  const search = new URLSearchParams({
    q: trimmed,
    education_level: params.educationLevel,
  })

  if (params.districtId) {
    search.set('district_id', params.districtId)
  }

  const response = await apiRequest<SchoolNameMatch[]>(`/school-options/match?${search.toString()}`, {
    signal: params.signal,
  })

  return response.data
}

export function saveLastRegistration(registration: Registration) {
  sessionStorage.setItem(lastRegistrationKey, JSON.stringify(registration))
}

export function getLastRegistration(): Registration | null {
  const rawRegistration = sessionStorage.getItem(lastRegistrationKey)

  if (!rawRegistration) {
    return null
  }

  try {
    return JSON.parse(rawRegistration) as Registration
  } catch {
    sessionStorage.removeItem(lastRegistrationKey)
    return null
  }
}
