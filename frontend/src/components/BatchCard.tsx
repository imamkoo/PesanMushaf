import { Link } from 'react-router-dom'
import { icons } from '../assets/projectAssets'
import type { Batch } from '../types/batch'
import { ArrowRight } from './icons'
import { ProgressBar } from './ProgressBar'
import { TiltCard } from './motion/TiltCard'

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
  const progressTone: 'ink' | 'amber' | 'brand' =
    filledPct >= 100 ? 'ink' : filledPct >= 80 ? 'amber' : 'brand'
  const category = batch.educationLevel
    ? `${batch.educationLevel} · ${batch.edition.toUpperCase()}`
    : batch.edition.toUpperCase()
  const price = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(batch.basePrice)

  return (
    <Link
      to={batch.href}
      className="card block h-full rounded-[28px] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand/20"
    >
      <TiltCard tilt={3} className="h-full rounded-[28px]" spotlightColor="rgba(237, 56, 51, 0.11)">
        <article className="flex h-full flex-col overflow-hidden rounded-[28px] border border-white/70 bg-white/80 shadow-[0_26px_80px_-54px_rgba(0,0,0,0.4)] backdrop-blur-xl transition duration-500 hover:-translate-y-1 hover:border-brand/20 hover:shadow-[0_34px_90px_-38px_rgba(0,0,0,0.35)]">
          <div className="relative h-[220px] w-full overflow-hidden">
            {status !== 'available' ? (
              <p className="absolute left-5 top-5 z-10 w-fit rounded-full bg-ink px-4 py-1.5 text-sm font-black leading-[21px] text-white shadow-[0_12px_30px_-16px_rgba(0,0,0,0.8)]">
                {statusLabel}
              </p>
            ) : (
              <p className="absolute left-5 top-5 z-10 w-fit rounded-full bg-brand px-4 py-1.5 text-sm font-black leading-[21px] text-white shadow-[0_14px_30px_-16px_rgba(237,56,51,0.8)]">
                {statusLabel}
              </p>
            )}
            <img
              src={batch.imageUrl}
              width={460}
              height={600}
              className="h-full w-full object-cover saturate-[0.88] transition duration-700 group-hover:scale-105 group-hover:saturate-100"
              alt={batch.name}
              loading="lazy"
              decoding="async"
            />
            <div
              aria-hidden="true"
              className="absolute inset-0 bg-[radial-gradient(circle_at_82%_8%,rgba(216,180,90,0.22),transparent_42%)]"
            />
            <div className="absolute inset-0 bg-[linear-gradient(180deg,transparent_45%,rgba(11,11,15,0.42)_100%)]" />
            <span className="absolute bottom-4 right-4 flex h-10 w-10 translate-x-2 items-center justify-center rounded-full border border-white/20 bg-ink/55 text-white opacity-0 backdrop-blur-md transition duration-300 group-hover:translate-x-0 group-hover:opacity-100">
              <ArrowRight className="h-4 w-4" />
            </span>
          </div>

          <div className="flex flex-1 flex-col gap-5 p-6">
            <div>
              <p className="text-xs font-black uppercase tracking-[0.16em] text-brand">{batch.batchNumber}</p>
              <h3 className="mt-3 line-clamp-2 min-h-[64px] text-[24px] font-black leading-[32px] tracking-[-0.04em] text-ink">
                {batch.name}
              </h3>
            </div>

            <div className="grid grid-cols-2 gap-3 text-sm">
              <div className="rounded-2xl border border-black/[0.05] bg-cream/80 p-3">
                <p className="text-ink/55">Kategori</p>
                <p className="mt-1 font-black text-ink">{category}</p>
              </div>
              <div className="rounded-2xl border border-black/[0.05] bg-cream/80 p-3">
                <p className="text-ink/55">Harga</p>
                <p className="mt-1 font-black text-ink">{price}</p>
              </div>
            </div>

            <div className="mt-auto flex flex-col gap-3 border-t border-black/[0.08] pt-5">
              <div className="flex items-center justify-between gap-2 text-[13px]">
                <div className="flex min-w-0 items-center gap-1.5">
                  <img src={icons.location} className="h-4 w-4 shrink-0" alt="" />
                  <p className="truncate font-bold text-ink">{batch.districtName}</p>
                </div>
                <div className="flex shrink-0 items-center justify-end gap-1.5">
                  <p className="whitespace-nowrap font-bold text-ink">{remainingSlots} tersisa</p>
                  <img src={icons.group} className="h-5 w-5" alt="" />
                </div>
              </div>
              <div className="space-y-1">
                <div className="flex items-center justify-between text-xs font-black uppercase tracking-[0.12em] text-ink/55">
                  <span>Terisi</span>
                  <span>{batch.registrationsCount > 0 && filledPct === 0 ? '<1' : filledPct}%</span>
                </div>
                <ProgressBar
                  value={filledPct}
                  tone={progressTone}
                  label={`Kuota terisi ${
                    batch.registrationsCount > 0 && filledPct === 0 ? 'kurang dari 1' : filledPct
                  } persen`}
                />
              </div>
            </div>
          </div>
        </article>
      </TiltCard>
    </Link>
  )
}
