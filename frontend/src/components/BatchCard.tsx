import { Link } from 'react-router-dom'
import { icons } from '../assets/projectAssets'
import type { Batch } from '../types/batch'

type BatchCardProps = {
  batch: Batch
}

export function BatchCard({ batch }: BatchCardProps) {
  const remainingSlots = Math.max(batch.maxCapacity - batch.registrationsCount, 0)
  const status = batch.status ?? (batch.isFull ? 'full' : 'available')
  const statusLabel = status === 'full' ? 'Penuh' : status === 'closed' ? 'Ditutup' : 'Tersedia'
  const filledPct =
    batch.maxCapacity > 0
      ? Math.min(100, Math.round((batch.registrationsCount / batch.maxCapacity) * 100))
      : 0
  const progressTone =
    filledPct >= 100
      ? 'bg-[#111111]'
      : filledPct >= 80
        ? 'bg-amber-500'
        : 'bg-[#ed3833]'
  const price = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(batch.basePrice)

  return (
    <Link to={batch.href} className="card">
      <article className="group flex h-full flex-col overflow-hidden rounded-[28px] border border-black/10 bg-white shadow-[0_20px_70px_rgba(0,0,0,0.05)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_28px_90px_rgba(0,0,0,0.10)]">
        <div className="relative h-[220px] w-full overflow-hidden">
          {status !== 'available' ? (
            <p className="absolute left-5 top-5 z-10 w-fit rounded-full bg-[#111111] p-[6px_16px] text-sm font-black leading-[21px] text-white">
              {statusLabel}
            </p>
          ) : (
            <p className="absolute left-5 top-5 z-10 w-fit rounded-full bg-[#ed3833] p-[6px_16px] text-sm font-black leading-[21px] text-white">
              {statusLabel}
            </p>
          )}
          <img src={batch.imageUrl} className="h-full w-full object-cover transition duration-500 group-hover:scale-105" alt={batch.name} />
        </div>
        <div className="flex flex-1 flex-col gap-5 p-6">
          <div>
            <p className="text-xs font-black uppercase tracking-[0.16em] text-[#ed3833]">{batch.batchNumber}</p>
            <h3 className="mt-3 line-clamp-2 min-h-[64px] text-[24px] font-black leading-[32px] tracking-[-0.04em] text-[#111111]">{batch.name}</h3>
          </div>
          <div className="grid grid-cols-2 gap-3 text-sm">
            <div className="rounded-2xl bg-[#f7f7fd] p-3">
              <p className="text-[#111111]/45">Jenjang</p>
              <p className="mt-1 font-black">{batch.educationLevel} · {batch.edition.toUpperCase()}</p>
            </div>
            <div className="rounded-2xl bg-[#f7f7fd] p-3">
              <p className="text-[#111111]/45">Harga</p>
              <p className="mt-1 font-black">{price}</p>
            </div>
          </div>
          <div className="mt-auto flex flex-col gap-3 border-t border-black/10 pt-5">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-[6px]">
                <img src={icons.location} className="h-5 w-5" alt="" />
                <p className="font-bold">{batch.districtName}</p>
              </div>
              <div className="flex items-center justify-end gap-[6px]">
                <p className="font-bold">{remainingSlots} tersisa</p>
                <img src={icons.clock} className="h-6 w-6" alt="" />
              </div>
            </div>
            <div className="space-y-1">
              <div className="flex items-center justify-between text-xs font-black uppercase tracking-[0.12em] text-[#111111]/45">
                <span>Terisi</span>
                <span>{filledPct}%</span>
              </div>
              <div
                role="progressbar"
                aria-valuenow={filledPct}
                aria-valuemin={0}
                aria-valuemax={100}
                aria-label={`Kuota terisi ${filledPct} persen`}
                className="h-1.5 w-full overflow-hidden rounded-full bg-black/[0.06]"
              >
                <div
                  className={`h-full ${progressTone} transition-[width] duration-500`}
                  style={{ width: `${filledPct}%` }}
                />
              </div>
            </div>
          </div>
        </div>
      </article>
    </Link>
  )
}
