import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { AppNavbar } from '../components/AppNavbar'
import { MidtransPayButton } from '../components/MidtransPayButton'
import { getLastRegistration, getRegistrationStatus, saveLastRegistration, type PaymentUiStatus, type Registration } from '../lib/api'
import { formatRupiah } from '../lib/payment'

const statusLabels: Record<Registration['status'], string> = {
  pending: 'Menunggu pembayaran',
  success: 'Lunas',
  failed: 'Batal',
}

const paymentAsideStyles: Record<Registration['status'], string> = {
  pending: 'bg-[#ed3833] shadow-[0_24px_90px_rgba(237,56,51,0.22)]',
  success: 'bg-emerald-600 shadow-[0_24px_90px_rgba(5,150,105,0.28)]',
  failed: 'bg-zinc-600 shadow-[0_24px_90px_rgba(63,63,70,0.22)]',
}

const heroIconStyles: Record<Registration['status'], string> = {
  pending: 'bg-[#ed3833]',
  success: 'bg-emerald-600',
  failed: 'bg-zinc-600',
}

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

  return (
    <>
      <AppNavbar />
      <main className="min-h-[calc(100vh-73px)] bg-[#f4f0ea] px-5 py-8">
        <section className="mx-auto grid w-full max-w-[980px] gap-6 rounded-[38px] bg-white p-6 shadow-[0_24px_90px_rgba(0,0,0,0.06)] sm:p-8 lg:p-10">
          <div
            className={`flex h-16 w-16 items-center justify-center rounded-full text-3xl font-black text-white ${registration ? heroIconStyles[registration.status] : 'bg-[#ed3833]'}`}
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
            <div className="grid gap-5 lg:grid-cols-[1fr_0.78fr]">
              <div className="rounded-[30px] bg-[#f7f7fd] p-5 sm:p-6">
                <p className="text-xs font-black uppercase tracking-[0.16em] text-[#111111]/40">Kode Pendaftaran</p>
                <p className="mt-3 break-words text-2xl font-black leading-8 tracking-[-0.03em] text-[#111111]">{registration.registration_code}</p>
                <div className="mt-6 grid gap-3 sm:grid-cols-2">
                  <Info label="Nama" value={registration.name} />
                  <Info label="Status" value={statusLabels[registration.status]} />
                  <Info label="Jenjang" value={registration.education_level} />
                  <Info label="Kategori" value={registration.edition} />
                  {districtLabel ? <Info label="Kecamatan" value={districtLabel} /> : null}
                  {batchLabel ? <Info label="Batch / Jilid" value={batchLabel} /> : null}
                  {pageLabel ? <Info label="Nomor Halaman" value={pageLabel} /> : null}
                  {registration.nik ? <Info label="NIK" value={maskNik(registration.nik)} /> : null}
                  {registration.address ? <Info label="Alamat" value={registration.address} /> : null}
                </div>
              </div>
              <div className={`rounded-[30px] p-5 text-white sm:p-6 ${paymentAsideStyles[registration.status]}`}>
                <p className="text-xs font-black uppercase tracking-[0.16em] text-white/60">Total tagihan</p>
                <p className="mt-3 text-[38px] font-black tracking-[-0.06em]">{formatRupiah(registration.financial.total_payment)}</p>
                <p className="mt-4 text-sm font-semibold leading-6 text-white/70">
                  {registration.status === 'success'
                    ? 'Pembayaran tercatat. Terima kasih — nominal di atas sesuai kategori pendaftaran Anda.'
                    : registration.status === 'failed'
                      ? 'Transaksi dibatalkan atau gagal. Hubungi panitia jika Anda sudah mentransfer dana.'
                      : 'Nominal ini wajib dibayar sesuai kategori. Gunakan tombol di bawah untuk bayar secara online, atau ikuti instruksi transfer manual dari panitia bila tersedia.'}
                </p>
                {registration.status === 'pending' ? (
                  <div className="mt-6 border-t border-white/20 pt-6">
                    <p className="mb-3 text-xs font-black uppercase tracking-[0.14em] text-white/60">Pembayaran</p>
                    <MidtransPayButton
                      registrationCode={registration.registration_code}
                      onSyncedPaymentStatus={applyPaymentStatusToSession}
                      onFlowEnd={() => void refreshRegistrationStatus()}
                      label="Bayar sekarang"
                    />
                    <p className="mt-3 text-xs font-semibold leading-5 text-white/60">
                      Status akan diperiksa otomatis setiap 15 detik selama halaman ini terbuka.
                    </p>
                  </div>
                ) : null}
              </div>
            </div>
          ) : (
            <div className="rounded-[30px] bg-[#fff7f7] p-6 text-[#111111]">
              <p className="font-black">Belum ada data registrasi terbaru di browser ini.</p>
              <p className="mt-2 text-sm font-medium text-[#111111]/60">Silakan daftar terlebih dahulu agar kode backend bisa ditampilkan di sini.</p>
            </div>
          )}

          <div className="flex flex-col gap-3 sm:flex-row">
            <Link to={registration ? `/booking/details?code=${encodeURIComponent(registration.registration_code)}` : '/booking/details'} className="rounded-full bg-[#ed3833] px-7 py-4 text-base font-black text-white shadow-[0_22px_70px_rgba(237,56,51,0.35)] transition duration-300 hover:!text-white hover:bg-[#ed3833]">
              Lacak Kode
            </Link>
            <Link to="/" className="rounded-full border border-black/10 bg-white px-6 py-4 shadow-[0_22px_70px_rgba(237,56,51,0.35)] text-center font-black text-[#111111] transition hover:border-[#ed3833] hover:text-[#ed3833]">
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
    <div className="rounded-2xl bg-white p-4">
      <p className="text-xs font-black uppercase tracking-[0.12em] text-[#111111]/40">{label}</p>
      <p className="mt-2 break-words font-black text-[#111111]">{value}</p>
    </div>
  )
}
