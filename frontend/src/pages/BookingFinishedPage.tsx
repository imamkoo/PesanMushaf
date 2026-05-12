import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { AppNavbar } from '../components/AppNavbar'
import { MidtransPayButton } from '../components/MidtransPayButton'
import { getLastRegistration, getRegistrationStatus, saveLastRegistration, type PaymentUiStatus, type Registration } from '../lib/api'
import { formatRupiah } from '../lib/payment'
import { paymentStatusUi } from '../lib/paymentStatusUi'

const POLL_INTERVAL_MS = 15_000
const POLL_MAX_ATTEMPTS = 20

function normalizeRegistrationCode(code: string) {
  return code.replace(/\s+/g, '').toUpperCase()
}

function maskNik(nik: string) {
  const digits = nik.replace(/\D/g, '')
  if (digits.length < 4) {
    return digits
  }

  const tail = digits.slice(-4)
  const prefix = digits.slice(0, -4).replace(/\d/g, '•')
  return `${prefix.replace(/(.{4})/g, '$1 ').trim()} ${tail}`.trim()
}

export function BookingFinishedPage() {
  const location = useLocation()
  const [registrationVersion, setRegistrationVersion] = useState(0)

  const registration = useMemo(() => {
    void registrationVersion

    return getLastRegistration() ?? (location.state as Registration | null)
  }, [location.state, registrationVersion])

  const applyPaymentStatusToSession = useCallback(
    (status: PaymentUiStatus) => {
      const raw = getLastRegistration() ?? (location.state as Registration | null)
      if (!raw?.registration_code) {
        return
      }
      saveLastRegistration({ ...raw, status })
      setRegistrationVersion((v) => v + 1)
    },
    [location.state],
  )

  const refreshRegistrationStatus = useCallback(async () => {
    const raw = getLastRegistration() ?? (location.state as Registration | null)
    const code = raw?.registration_code
    if (!code) {
      return
    }

    try {
      const res = await getRegistrationStatus(code)
      const row = res.data.find((r) => normalizeRegistrationCode(r.registration_code) === normalizeRegistrationCode(code))
      if (row && raw) {
        // Hanya pakai field2 yang aman dari RegistrationStatus (statusLookup);
        // jangan timpa relasi object (`district`, `batch`) di Registration.
        const merged: Registration = {
          ...raw,
          status: row.status,
          page_number: row.page_number,
          total_payment: row.total_payment,
          updated_at: row.updated_at,
        }
        saveLastRegistration(merged)
        setRegistrationVersion((v) => v + 1)
      }
    } catch {
      /* lacak ulang bisa dilakukan manual */
    }
  }, [location.state])

  // Auto-polling status pembayaran selama pending. Stop kalau tab tidak visible,
  // status berubah ke success/failed, atau kuota 20 putaran (~5 menit) tercapai.
  const pollAttemptsRef = useRef(0)
  useEffect(() => {
    if (registration?.status !== 'pending') {
      pollAttemptsRef.current = 0
      return
    }

    let cancelled = false
    pollAttemptsRef.current = 0

    const intervalId = window.setInterval(() => {
      if (cancelled) {
        return
      }
      if (document.visibilityState !== 'visible') {
        return
      }
      if (pollAttemptsRef.current >= POLL_MAX_ATTEMPTS) {
        window.clearInterval(intervalId)
        return
      }
      pollAttemptsRef.current += 1
      void refreshRegistrationStatus()
    }, POLL_INTERVAL_MS)

    return () => {
      cancelled = true
      window.clearInterval(intervalId)
    }
  }, [registration?.status, refreshRegistrationStatus, registration?.registration_code])

  const batchLabel = registration?.batch?.name
  const districtLabel = registration?.district?.name
  const pageLabel = registration?.page_number ? `Halaman ${registration.page_number}` : null
  const statusUi = registration ? paymentStatusUi[registration.status] : null
  const primaryActionClass = registration?.status === 'success'
    ? 'bg-emerald-600 shadow-[0_18px_48px_rgba(5,150,105,0.18)] hover:bg-emerald-700'
    : registration?.status === 'failed'
      ? 'bg-[#111111] shadow-[0_18px_48px_rgba(17,17,17,0.16)] hover:bg-black'
      : 'bg-[#ed3833] shadow-[0_18px_48px_rgba(237,56,51,0.2)] hover:bg-[#d92f2a]'

  return (
    <>
      <AppNavbar />
      <main className="min-h-[calc(100vh-73px)] bg-[#f4f0ea] px-5 py-8">
        <section className="mx-auto grid w-full max-w-[1040px] gap-6 rounded-[36px] border border-black/6 bg-white/96 p-6 shadow-[0_24px_80px_rgba(17,17,17,0.07)] sm:p-8 lg:p-10">
          <div
            className={`flex h-14 w-14 items-center justify-center rounded-full text-2xl font-black text-white ${registration ? statusUi?.iconClass : 'bg-[#ed3833]'}`}
          >
            ✓
          </div>
          <div>
            <p className="text-sm font-black uppercase tracking-[0.16em] text-[#ed3833]">Pendaftaran tersimpan</p>
            <h1 className="mt-4 max-w-[760px] text-[40px] font-black leading-[0.95] tracking-[-0.06em] text-[#111111] sm:text-[64px]">
              Kode pendaftaran sudah dibuat.
            </h1>
            <p className="mt-5 max-w-[640px] text-base font-medium leading-7 text-[#111111]/62 sm:text-lg sm:leading-8">
              Simpan kode pendaftaran Anda dengan aman. Kode ini digunakan untuk memeriksa status pembayaran kapan saja melalui halaman lacak status.
            </p>
          </div>

          {registration ? (
            <div className="grid gap-5 lg:grid-cols-[1.04fr_0.82fr]">
              <div className="rounded-[30px] border border-black/6 bg-[#faf8f4] p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.6)] sm:p-6">
                <div className="flex flex-col gap-3 border-b border-black/8 pb-5 sm:flex-row sm:items-end sm:justify-between">
                  <div>
                    <p className="text-xs font-black uppercase tracking-[0.16em] text-[#111111]/38">Kode Pendaftaran</p>
                    <p className="mt-3 break-words text-2xl font-black leading-8 tracking-[-0.03em] text-[#111111]">{registration.registration_code}</p>
                  </div>
                  <p className={`w-fit rounded-full px-4 py-2 text-sm font-black ${statusUi?.badgeClass}`}>{statusUi?.label}</p>
                </div>
                <div className="mt-6 grid gap-3 sm:grid-cols-2">
                  <Info label="Nama" value={registration.name} />
                  <Info label="Status" value={statusUi?.label ?? registration.status} />
                  <Info label="Jenjang" value={registration.education_level} />
                  <Info label="Kategori" value={registration.edition} />
                  {districtLabel ? <Info label="Kecamatan" value={districtLabel} /> : null}
                  {batchLabel ? <Info label="Batch / Jilid" value={batchLabel} /> : null}
                  {pageLabel ? <Info label="Nomor Halaman" value={pageLabel} /> : null}
                  {registration.nik ? <Info label="NIK" value={maskNik(registration.nik)} /> : null}
                  {registration.address ? <Info label="Alamat" value={registration.address} /> : null}
                </div>
              </div>
              <div className={`rounded-[30px] p-5 text-white sm:p-6 ${statusUi?.asideClass}`}>
                <p className="text-xs font-black uppercase tracking-[0.16em] text-white/60">{statusUi?.eyebrow}</p>
                <p className="mt-3 text-[38px] font-black tracking-[-0.06em]">{formatRupiah(registration.financial.total_payment)}</p>
                <p className="mt-2 text-lg font-black text-white">{statusUi?.label}</p>
                <p className="mt-4 text-sm font-semibold leading-6 text-white/72">
                  {statusUi?.bodyText}
                </p>
                <div className="mt-6 grid gap-3">
                  <PaymentMeta label="Kategori" value={registration.edition.toUpperCase()} />
                  <PaymentMeta label="Nomor Halaman" value={pageLabel ?? '-'} />
                </div>
                <div className="mt-6 border-t border-white/15 pt-6">
                  <p className="mb-3 text-xs font-black uppercase tracking-[0.14em] text-white/60">Pembayaran</p>
                  {registration.status === 'pending' ? (
                    <MidtransPayButton
                      registrationCode={registration.registration_code}
                      onSyncedPaymentStatus={applyPaymentStatusToSession}
                      onFlowEnd={() => void refreshRegistrationStatus()}
                      label="Bayar sekarang"
                      variant="onDark"
                    />
                  ) : (
                    <StatusAction status={registration.status} label={statusUi?.actionLabel ?? statusUi?.label ?? ''} />
                  )}
                  <p className="mt-3 text-xs font-semibold leading-5 text-white/65">
                    {statusUi?.actionNote}
                  </p>
                </div>
              </div>
            </div>
          ) : (
            <div className="rounded-[28px] border border-[#ed3833]/10 bg-[#fff7f7] p-6 text-[#111111]">
              <p className="font-black">Belum ada data registrasi terbaru di browser ini.</p>
              <p className="mt-2 text-sm font-medium text-[#111111]/60">Silakan daftar terlebih dahulu agar kode backend bisa ditampilkan di sini.</p>
            </div>
          )}

          <div className="flex flex-col gap-3 sm:flex-row">
            <Link
              to={registration ? `/booking/details?code=${encodeURIComponent(registration.registration_code)}` : '/booking/details'}
              className={`rounded-full px-7 py-4 text-base font-black !text-white transition duration-300 hover:!text-white ${primaryActionClass}`}
            >
              Lacak Kode
            </Link>
            <Link
              to="/"
              className="rounded-full border border-black/10 bg-white px-6 py-4 text-center font-black text-[#111111] shadow-[0_16px_42px_rgba(17,17,17,0.06)] transition hover:border-[#ed3833] hover:text-[#ed3833]"
            >
              Kembali Home
            </Link>
          </div>
        </section>
      </main>
    </>
  )
}

type InfoProps = {
  label: string
  value: string
}

function Info({ label, value }: InfoProps) {
  return (
    <div className="rounded-[20px] border border-black/6 bg-white/92 p-4 shadow-[0_10px_24px_rgba(17,17,17,0.04)]">
      <p className="text-xs font-black uppercase tracking-[0.12em] text-[#111111]/40">{label}</p>
      <p className="mt-2 break-words font-black text-[#111111]">{value}</p>
    </div>
  )
}

type PaymentMetaProps = {
  label: string
  value: string
}

function PaymentMeta({ label, value }: PaymentMetaProps) {
  return (
    <div className="rounded-[18px] border border-white/14 bg-white/10 p-4 backdrop-blur-sm">
      <p className="text-[11px] font-black uppercase tracking-[0.14em] text-white/55">{label}</p>
      <p className="mt-2 font-black text-white">{value}</p>
    </div>
  )
}

type StatusActionProps = {
  label: string
  status: Registration['status']
}

function StatusAction({ label, status }: StatusActionProps) {
  const actionClass = paymentStatusUi[status].staticActionClass

  if (! actionClass) {
    return null
  }

  return <div className={`${actionClass} !text-white`}>{label}</div>
}
