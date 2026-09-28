import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { useSearchParams } from 'react-router-dom'
import { Navigation, Pagination } from 'swiper/modules'
import { Swiper, SwiperSlide } from 'swiper/react'
import { AppNavbar } from '../components/AppNavbar'
import { MidtransPayButton } from '../components/MidtransPayButton'
import { getLastRegistration, getRegistrationStatus, type RegistrationStatus } from '../lib/api'
import { formatRupiah } from '../lib/payment'
import { paymentStatusUi } from '../lib/paymentStatusUi'

import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'

function normalizeLookup(value: string): string {
  return value.replace(/\s+/g, '').toUpperCase()
}

function extractCodeTail(code: string): string {
  return code.split('-').pop() ?? code
}

export function BookingDetailsPage() {
  const [searchParams] = useSearchParams()
  const lastRegistration = getLastRegistration()
  const initialLookup = searchParams.get('code') ?? searchParams.get('phone') ?? lastRegistration?.registration_code ?? lastRegistration?.phone_number ?? ''
  const [lookup, setLookup] = useState(initialLookup)
  const [statuses, setStatuses] = useState<RegistrationStatus[]>([])
  const [message, setMessage] = useState('')
  const [isLoading, setIsLoading] = useState(false)

  useEffect(() => {
    if (initialLookup) {
      void checkStatus(initialLookup)
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  async function checkStatus(value: string) {
    const normalizedLookup = normalizeLookup(value)

    setMessage('')
    setStatuses([])
    setLookup(normalizedLookup)
    setIsLoading(true)

    try {
      const response = await getRegistrationStatus(normalizedLookup)
      setStatuses(response.data)
      setMessage(response.message)
    } catch (error) {
      setMessage(error instanceof Error ? error.message : 'Data pendaftaran tidak ditemukan.')
    } finally {
      setIsLoading(false)
    }
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    if (!lookup.trim()) {
      setStatuses([])
      setMessage('Masukkan kode booking lengkap, kode belakang acak, atau nomor WhatsApp.')
      return
    }

    await checkStatus(lookup)
  }

  return (
    <>
      <AppNavbar />
      <main className="min-h-[calc(100vh-73px)] bg-[#f4f0ea] px-5 py-6 sm:py-8">
        <section className="mx-auto w-full max-w-[1020px]">
          <LookupHero />
          
          {/* Form Pencarian */}
          <LookupForm
            isLoading={isLoading}
            lookup={lookup}
            message={message}
            onChange={setLookup}
            onSubmit={handleSubmit}
          />
        </section>
      </main>

      {statuses.length > 0 ? (
        <StatusModal
          statuses={statuses}
          onClose={() => setStatuses([])}
          onAfterPayment={() => void checkStatus(lookup)}
        />
      ) : null}
    </>
  )
}

type LookupFormProps = {
  isLoading: boolean
  lookup: string
  message: string
  onChange: (value: string) => void
  onSubmit: (event: FormEvent<HTMLFormElement>) => void
}

function LookupHero() {
  return (
    <header className="rounded-[28px] border border-black/8 bg-[#111111] p-5 text-white shadow-[0_18px_60px_rgba(0,0,0,0.16)] sm:p-7">
      <div className="flex items-center justify-between text-[10px] font-black uppercase tracking-[0.18em] text-white/40">
        <p>Lacak Pendaftaran</p>
        <p>HUT500</p>
      </div>
      <div className="mt-10 grid gap-6 md:grid-cols-[0.95fr_1fr] md:items-end">
        <div>
          <p className="mb-4 w-fit rounded-full bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-[0.08em] text-white">
            Status Pembayaran
          </p>
          <h1 className="max-w-[540px] text-[38px] font-black leading-[0.96] tracking-[-0.06em] sm:text-[56px]">
            Lacak dengan kode lengkap, kode belakang, atau WhatsApp.
          </h1>
        </div>
        <p className="max-w-[430px] text-sm font-semibold leading-7 text-white/58 md:justify-self-end md:text-base">
          Masukkan salah satu: kode booking lengkap, 4 huruf/angka terakhir yang acak, atau nomor WhatsApp terdaftar. Jika satu nomor punya beberapa pendaftaran, hasilnya akan tampil sebagai kartu yang bisa digeser.
        </p>
      </div>
    </header>
  )
}

function LookupForm({ isLoading, lookup, message, onChange, onSubmit }: LookupFormProps) {
  return (
    <form
      onSubmit={onSubmit}
      className="mx-auto mt-5 max-w-[1200px] rounded-[28px] border border-black/8 bg-white/95 p-5 shadow-[0_18px_60px_rgba(17,17,17,0.06)] sm:p-7"
    >
      <label className="flex flex-col gap-3 font-black text-[#111111]">
        Kode Booking, Kode Belakang, atau Nomor WhatsApp
        <input
          value={lookup}
          onChange={(event) => onChange(event.target.value)}
          placeholder="Contoh: 3171030-REGULER-SMA..., XBV1, atau 628123456789"
          className="rounded-full border border-black/15 px-5 py-4 font-semibold outline-none transition focus:border-[#ed3833] focus:ring-4 focus:ring-[#ed3833]/10"
        />
      </label>
      <button
        type="submit"
        disabled={isLoading}
        className="mt-5 w-full rounded-full bg-[#ed3833] px-6 py-4 font-black text-white outline-none transition hover:bg-[#d92f2a] focus:ring-4 focus:ring-[#ed3833]/20 disabled:cursor-not-allowed disabled:opacity-60"
      >
        {isLoading ? 'Melacak...' : 'Lacak Pendaftaran'}
      </button>
      {message ? <p className="mt-4 rounded-2xl bg-[#f7f7fd] p-4 text-sm font-bold text-[#111111]/70">{message}</p> : null}
    </form>
  )
}

type StatusModalProps = {
  statuses: RegistrationStatus[]
  onClose: () => void
  onAfterPayment: () => void
}

function StatusModal({ statuses, onClose, onAfterPayment }: StatusModalProps) {
  const primaryName = statuses[0]?.name ?? ''
  const hasMultiple = statuses.length > 1
  const [activeIndex, setActiveIndex] = useState(0)

  return (
    <div className="fixed inset-0 z-[90] flex items-center justify-center bg-[#111111]/62 px-4 py-6 backdrop-blur-md">
      <section role="dialog" aria-modal="true" aria-label="Hasil lacak pendaftaran" className="max-h-[92vh] w-full max-w-[980px] overflow-y-auto rounded-[30px] bg-white/95 p-3 shadow-[0_34px_120px_rgba(0,0,0,0.24)] sm:p-4">
        <div className="overflow-hidden rounded-[26px] border border-black/6 bg-white">
          <div className="flex flex-col gap-4 border-b border-white/10 bg-[#111111] p-5 text-white sm:flex-row sm:items-start sm:justify-between sm:p-6">
            <div>
              <p className="text-xs font-black uppercase tracking-[0.18em] text-white/42">Hasil Pelacakan</p>
              <h2 className="mt-4 max-w-[600px] text-[30px] font-black leading-[1] tracking-[-0.055em] sm:text-[44px]">
                {hasMultiple ? 'Beberapa kode pendaftaran ditemukan.' : primaryName}
              </h2>
              {hasMultiple ? (
                <p className="mt-3 max-w-[560px] text-sm font-semibold leading-6 text-white/58">
                  Ditemukan {statuses.length} kode dari nomor yang sama. Geser kartu atau gunakan panah untuk melihat detail masing-masing.
                </p>
              ) : primaryName ? (
                <p className="mt-3 max-w-[560px] text-sm font-semibold leading-6 text-white/58">
                  Gunakan kode booking lengkap, kode belakang, atau nomor WhatsApp untuk membuka data ini kembali.
                </p>
              ) : null}
            </div>
            <div className="flex items-center gap-3 self-start sm:self-auto">
              {hasMultiple ? (
                <p className="rounded-full bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-[0.14em] text-white">
                  {activeIndex + 1} / {statuses.length}
                </p>
              ) : null}
              <button
                type="button"
                onClick={onClose}
                className="h-11 w-11 shrink-0 rounded-full bg-white/10 text-xl font-black text-white outline-none transition hover:bg-[#ed3833] focus:ring-4 focus:ring-white/20"
                aria-label="Tutup hasil pelacakan"
              >
                X
              </button>
            </div>
          </div>

          <div className="p-4 lg:p-5">
            {hasMultiple ? (
              <Swiper
                modules={[Navigation, Pagination]}
                navigation
                pagination={{ clickable: true }}
                slidesPerView={1}
                spaceBetween={20}
                autoHeight
                onSlideChange={(swiper) => setActiveIndex(swiper.activeIndex)}
                className="hut-lookup-results-swiper"
              >
                {statuses.map((status, index) => (
                  <SwiperSlide key={`${status.registration_code}-${status.id}`} className="!h-auto pb-12">
                    <StatusResultCard
                      status={status}
                      index={index}
                      total={statuses.length}
                      onAfterPayment={onAfterPayment}
                    />
                  </SwiperSlide>
                ))}
              </Swiper>
            ) : (
              <StatusResultCard
                status={statuses[0]}
                index={0}
                total={1}
                onAfterPayment={onAfterPayment}
              />
            )}
          </div>
        </div>
      </section>
    </div>
  )
}

type StatusResultCardProps = {
  status: RegistrationStatus
  index: number
  total: number
  onAfterPayment: () => void
}

function StatusResultCard({ status, index, total, onAfterPayment }: StatusResultCardProps) {
  const statusUi = paymentStatusUi[status.status]
  const codeTail = extractCodeTail(status.registration_code)

  return (
    <article className="overflow-hidden rounded-[28px] border border-black/8 bg-white shadow-[0_18px_60px_rgba(17,17,17,0.08)]">
      {total > 1 ? (
        <div className="flex flex-col gap-2 border-b border-black/8 bg-[#faf8f4] px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
          <p className="text-xs font-black uppercase tracking-[0.14em] text-[#111111]/45">
            Pendaftaran {index + 1} dari {total}
          </p>
          <p className="w-fit rounded-full border border-[#ed3833]/14 bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.14em] text-[#ed3833] shadow-[0_10px_24px_rgba(17,17,17,0.04)]">
            Kode belakang: {codeTail}
          </p>
        </div>
      ) : null}

      <div className="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_320px] lg:p-5">
        <div className="rounded-[24px] border border-black/8 bg-[#faf8f4] p-4 sm:p-5">
          <div className="flex flex-col gap-3 border-b border-black/10 pb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <p className="text-xs font-black uppercase tracking-[0.16em] text-[#ed3833]">Status Pendaftaran</p>
              <p className="mt-2 text-sm font-semibold leading-6 text-[#111111]/58">
                Data ditemukan. Kode belakang <span className="font-black text-[#111111]">{codeTail}</span> juga bisa dipakai untuk pelacakan cepat tanpa menyalin seluruh kode booking.
              </p>
            </div>
            <p className={`w-fit shrink-0 rounded-full px-4 py-2 text-sm font-black ${statusUi.badgeClass}`}>
              {statusUi.label}
            </p>
          </div>

          <div className="mt-5 grid gap-3 sm:grid-cols-2">
            <Info label="Kode Pendaftaran" value={status.registration_code} wide />
            <Info label="Kode Belakang" value={codeTail} />
            <Info label="Sekolah / Instansi" value={status.school_name} />
            <Info label="Kecamatan" value={status.district ?? '-'} />
            <Info label="Batch" value={status.batch ?? '-'} wide />
            <Info label="Jenjang" value={status.education_level} />
            <Info label="Kategori" value={status.edition} />
          </div>
        </div>

        <aside className={`rounded-[24px] p-5 text-white ${statusUi.asideClass}`}>
          <p className="text-xs font-black uppercase tracking-[0.16em] text-white/65">{statusUi.eyebrow}</p>
          <p className="mt-4 text-[34px] font-black leading-none tracking-[-0.06em] sm:text-[42px]">{formatRupiah(status.total_payment)}</p>
          <p className="mt-2 text-lg font-black text-white">{statusUi.label}</p>
          <p className="mt-4 text-sm font-semibold leading-6 text-white/70">
            {statusUi.bodyText}
          </p>
          <div className="mt-7 grid gap-3">
            <PaymentInfo label="Nomor Halaman" value={String(status.page_number)} />
            <PaymentInfo label="Kategori" value={status.edition.toUpperCase()} />
            <PaymentInfo label="Update Terakhir" value={status.updated_at ?? status.created_at ?? '-'} />
          </div>
          <div className="mt-5 border-t border-white/15 pt-5">
            {status.status === 'pending' ? (
              <>
                <p className="mb-3 text-xs font-black uppercase tracking-[0.14em] text-white/60">Pembayaran</p>
                <MidtransPayButton
                  registrationCode={status.registration_code}
                  onFlowEnd={onAfterPayment}
                  label="Bayar sekarang"
                  variant="onDark"
                />
              </>
            ) : (
              <StatusAction status={status.status} label={statusUi.actionLabel ?? statusUi.label} />
            )}
            <p className="mt-3 text-xs font-semibold leading-5 text-white/60">
              {status.status === 'pending'
                ? 'Setelah menyelesaikan pembayaran di jendela yang terbuka, status "Lunas" biasanya muncul dalam beberapa detik. Tutup jendela bila sudah selesai, lalu segarkan halaman atau lacak ulang.'
                : statusUi.actionNote}
            </p>
          </div>
        </aside>
      </div>
    </article>
  )
}

type InfoProps = {
  label: string
  value: string
  wide?: boolean
}

function Info({ label, value, wide = false }: InfoProps) {
  return (
    <div className={`rounded-[20px] border border-black/6 bg-white/92 p-4 shadow-[0_10px_24px_rgba(17,17,17,0.04)] ${wide ? 'sm:col-span-2' : ''}`}>
      <p className="text-xs font-black uppercase tracking-[0.12em] text-[#111111]/40">{label}</p>
      <p className="mt-2 break-words font-black leading-6 text-[#111111]">{value}</p>
    </div>
  )
}

function PaymentInfo({ label, value }: InfoProps) {
  return (
    <div className="rounded-[20px] border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
      <p className="text-xs font-black uppercase tracking-[0.12em] text-white/50">{label}</p>
      <p className="mt-2 break-words font-black leading-6 text-white">{value}</p>
    </div>
  )
}

type StatusActionProps = {
  label: string
  status: RegistrationStatus['status']
}

function StatusAction({ label, status }: StatusActionProps) {
  const actionClass = paymentStatusUi[status].staticActionClass

  if (! actionClass) {
    return null
  }

  return <div className={`${actionClass} !text-white`}>{label}</div>
}