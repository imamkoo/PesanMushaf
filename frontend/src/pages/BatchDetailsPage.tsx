import { Link, useParams } from 'react-router-dom'
import { photos } from '../assets/projectAssets'
import { AppNavbar } from '../components/AppNavbar'
import { useHut500Data } from '../hooks/useHut500Data'
import { formatRupiah } from '../lib/payment'
import type { Batch } from '../types/batch'

const statusConfig: Record<NonNullable<Batch['status']>, { label: string; description: string; tone: string }> = {
  available: {
    label: 'Tersedia',
    description: 'Batch siap dipilih peserta sesuai kecamatan, jenjang, dan kuota tersisa.',
    tone: 'bg-[#ed3833] text-white',
  },
  full: {
    label: 'Penuh',
    description: 'Kuota batch ini sudah terpenuhi. Pilih batch lain yang masih tersedia.',
    tone: 'bg-[#111111] text-white',
  },
  closed: {
    label: 'Ditutup',
    description: 'Pendaftaran batch ini sedang ditutup oleh admin.',
    tone: 'bg-[#fff7f7] text-[#ed3833]',
  },
}

export function BatchDetailsPage() {
  const { slug } = useParams()
  const { batches, isLoading, error } = useHut500Data()
  const batch = batches.find((item) => item.slug === slug)

  if (!batch) {
    return (
      <>
        <AppNavbar />
        <main className="bg-[#f4f0ea] px-5 py-8">
          <section className="mx-auto rounded-[38px] bg-white p-6 shadow-[0_24px_90px_rgba(0,0,0,0.06)] sm:p-8 lg:max-w-[900px] lg:p-10">
            <p className="text-sm font-black uppercase tracking-[0.16em] text-[#ed3833]">Batch tidak tersedia</p>
            <h1 className="mt-4 text-[42px] font-black leading-[0.96] tracking-[-0.06em] text-[#111111] sm:text-[64px]">
              {isLoading ? 'Memuat data...' : 'Data batch tidak ditemukan.'}
            </h1>
            <p className="mt-5 text-base font-medium leading-7 text-[#111111]/62">
              {error || 'Pastikan data batch tersedia dari endpoint /api/batches.'}
            </p>
          </section>
        </main>
      </>
    )
  }

  const remainingSlots = Math.max(batch.maxCapacity - batch.registrationsCount, 0)
  const status = batch.status ?? (batch.isFull ? 'full' : 'available')
  const config = statusConfig[status]
  const canRegister = status === 'available'

  return (
    <>
      <AppNavbar />
      <main className="bg-[#f4f0ea] px-5 py-8">
        <section className="mx-auto grid w-full max-w-[1180px] gap-6 lg:grid-cols-[1.12fr_0.88fr] lg:items-start">
          <div className="grid gap-4 sm:grid-cols-2">
            {[batch.imageUrl, photos.batches[1], photos.batches[2], photos.batches[3]].map((image, index) => (
              <div key={`${image}-${index}`} className={index === 0 ? 'relative min-h-[360px] overflow-hidden rounded-[38px] bg-[#111111] sm:col-span-2' : 'relative min-h-[220px] overflow-hidden rounded-[30px] bg-[#111111]'}>
                <img src={image} className="absolute inset-0 h-full w-full object-cover opacity-90" alt={batch.name} />
              </div>
            ))}
          </div>

          <aside className="sticky top-[96px] flex flex-col gap-6 rounded-[38px] border border-black/10 bg-white p-6 shadow-[0_24px_90px_rgba(0,0,0,0.06)] sm:p-8">
            <p className={`w-fit rounded-full px-4 py-2 text-sm font-black ${config.tone}`}>{config.label}</p>
            <div>
              <p className="text-sm font-black uppercase tracking-[0.16em] text-[#ed3833]">{batch.batchNumber}</p>
              <h1 className="mt-4 text-[38px] font-black leading-[0.98] tracking-[-0.06em] text-[#111111] sm:text-[52px]">
                {isLoading ? 'Memuat batch dari backend.' : batch.name}
              </h1>
              <p className="mt-5 text-base font-medium leading-7 text-[#111111]/62">
                {error ? `Backend API belum bisa dimuat: ${error}.` : config.description}
              </p>
            </div>
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <Info label="Kecamatan" value={batch.districtName} />
              <Info label="Jenjang" value={batch.educationLevel} />
              <Info label="Kuota" value={`${remainingSlots} tersisa`} />
              <Info label="Harga" value={formatRupiah(batch.basePrice)} />
            </div>
            {canRegister ? (
              <Link to="/booking" className="flex items-center justify-center rounded-full bg-[#ed3833] px-6 py-4 font-black text-white transition hover:bg-[#d92f2a]">
                Mulai Pendaftaran
              </Link>
            ) : (
              <Link to="/#batches" className="flex items-center justify-center rounded-full border border-black/10 bg-white px-6 py-4 font-black text-[#111111] transition hover:border-[#ed3833] hover:text-[#ed3833]">
                Lihat Batch Lain
              </Link>
            )}
          </aside>
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
    <div className="rounded-2xl bg-[#f7f7fd] p-4">
      <p className="text-xs font-black uppercase tracking-[0.12em] text-[#111111]/40">{label}</p>
      <p className="mt-2 font-black text-[#111111]">{value}</p>
    </div>
  )
}
