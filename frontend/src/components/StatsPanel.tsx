import type { EducationLevelStat, Hut500PublicStats } from '../hooks/useHut500Data'
import { Counter } from './motion/Counter'
import { Reveal } from './motion/Reveal'
import { ProgressBar } from './ProgressBar'
import { SealEmblem } from './SealEmblem'

const numberFormat = new Intl.NumberFormat('id-ID')

type StatsPanelProps = {
  stats: Hut500PublicStats
  isLoading: boolean
}

export function StatsPanel({ stats, isLoading }: StatsPanelProps) {
  return (
    <section id="stats" className="mx-auto w-full max-w-[1180px] scroll-mt-24 px-5 pb-10">
      <div className="grid gap-5 lg:grid-cols-[0.95fr_1.05fr]">
        <Reveal className="h-full">
          <div className="relative h-full overflow-hidden rounded-[28px] border border-white/70 bg-white/70 p-5 shadow-[0_30px_80px_-52px_rgba(0,0,0,0.35)] backdrop-blur-xl sm:p-6">
            <div
              aria-hidden="true"
              className="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-[radial-gradient(circle,rgba(216,180,90,0.2),transparent_68%)] blur-2xl"
            />
            <div className="relative flex items-start justify-between gap-4">
              <div>
                <h3 className="max-w-[18ch] text-[24px] font-black leading-[1.02] tracking-[-0.04em] text-ink">
                  Jejak yang sudah tercatat.
                </h3>
                <p className="mt-2 text-sm font-semibold leading-6 text-ink/65">
                  Ringkasan partisipasi dari gelombang yang sedang berjalan.
                </p>
              </div>
              <span className="flex shrink-0 items-center gap-2 rounded-full border border-brand/15 bg-brand/5 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.16em] text-brand">
                <span className="h-1.5 w-1.5 rounded-full bg-brand motion-safe:animate-pulse" />
                Data Langsung
              </span>
            </div>

            <div className="relative mt-5 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-black/[0.06] bg-black/[0.06]">
              <Metric
                label="Total Pendaftar"
                loading={isLoading}
                value={stats.totalRegistered}
                tone="brand"
              />
              <Metric
                label="Sisa Kuota"
                loading={isLoading}
                value={stats.totalRemaining}
              />
              <Metric
                label="Terisi"
                loading={isLoading}
                value={stats.fillPct}
                format={(value) =>
                  stats.fillPct === 0 && stats.totalRegistered > 0 ? '<1%' : `${value}%`
                }
              />
              <Metric
                label="Gelombang Penuh"
                loading={isLoading}
                value={stats.fullBatches}
              />
            </div>

            <div className="relative mt-4 flex items-center gap-3">
              <SealEmblem size={34} glow={false} />
              <p className="text-[10px] font-black uppercase leading-4 tracking-[0.16em] text-ink/55">
                Jejak Jakarta 500 Tahun
              </p>
            </div>
          </div>
        </Reveal>

        <Reveal delay={0.08} className="h-full">
          <div className="relative flex h-full flex-col overflow-hidden rounded-[28px] border border-white/70 bg-white/70 p-5 shadow-[0_30px_80px_-52px_rgba(0,0,0,0.35)] backdrop-blur-xl sm:p-6">
            <div
              aria-hidden="true"
              className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgba(11,11,15,0.07)_1px,transparent_0)] [background-size:18px_18px] opacity-40"
            />
            <div
              aria-hidden="true"
              className="pointer-events-none absolute -left-20 bottom-0 h-56 w-56 rounded-full bg-[radial-gradient(circle,rgba(237,56,51,0.12),transparent_68%)] blur-2xl"
            />
            <div className="relative flex items-start justify-between gap-4">
              <h3 className="max-w-[24ch] text-[24px] font-black leading-[1.02] tracking-[-0.04em] text-ink">
                Sebaran kuota tiap kategori peserta.
              </h3>
              <span className="shrink-0 rounded-full border border-gold/40 bg-gold/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.16em] text-gold-deep">
                Kapasitas
              </span>
            </div>

            <div className="relative mt-5 grid gap-4">
              {isLoading ? (
                Array.from({ length: 4 }).map((_, index) => <LevelRowSkeleton key={index} />)
              ) : stats.byLevel.length > 0 ? (
                stats.byLevel.map((row) => <LevelRow key={row.level} row={row} />)
              ) : (
                <p className="text-sm font-semibold text-ink/65">Belum ada data jenjang yang aktif.</p>
              )}
              <p className="text-xs font-semibold text-ink/55">Dihitung dari gelombang yang aktif.</p>
            </div>

            <div className="relative mt-auto flex items-end justify-between gap-4 border-t border-black/[0.08] pt-5">
              <div>
                <p className="text-[10px] font-black uppercase tracking-[0.16em] text-ink/55">Total kapasitas</p>
                {isLoading ? (
                  <span className="mt-2 block h-7 w-24 motion-safe:animate-pulse rounded-lg bg-black/[0.07]" />
                ) : (
                  <p className="mt-1 text-[28px] font-black leading-none tracking-[-0.04em] text-ink">
                    {numberFormat.format(stats.totalCapacity)}{' '}
                    <span className="text-sm font-bold text-ink/55">kursi</span>
                  </p>
                )}
              </div>
              <SealEmblem size={36} glow={false} />
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  )
}

type MetricProps = {
  label: string
  value: number
  loading: boolean
  tone?: 'brand' | 'default'
  format?: (value: number) => string
}

function Metric({ label, value, loading, tone = 'default', format }: MetricProps) {
  return (
    <div className="bg-white/80 p-4 sm:p-5">
      <p className="text-[10px] font-black uppercase tracking-[0.16em] text-ink/60">{label}</p>
      {loading ? (
        <span className="mt-3 block h-8 w-20 motion-safe:animate-pulse rounded-lg bg-black/[0.07]" />
      ) : (
        <p
          className={`mt-2 text-[30px] font-black leading-none tracking-[-0.04em] sm:text-[36px] ${
            tone === 'brand' ? 'text-brand' : 'text-ink'
          }`}
        >
          <Counter value={value} format={format} />
        </p>
      )}
    </div>
  )
}

function LevelRow({ row }: { row: EducationLevelStat }) {
  const tone: 'ink' | 'amber' | 'brand' =
    row.filledPct >= 100 ? 'ink' : row.filledPct >= 80 ? 'amber' : 'brand'

  return (
    <div>
      <div className="flex items-baseline justify-between gap-3 text-sm">
        <p className="font-black text-ink">{row.level}</p>
        <p className="font-semibold text-ink/65">
          {numberFormat.format(row.filled)} / {numberFormat.format(row.capacity)} (
          {row.filled > 0 && row.filledPct === 0 ? '<1' : row.filledPct}%)
        </p>
      </div>
      <ProgressBar
        value={row.filledPct}
        tone={tone}
        className="mt-2"
        label={`Kuota ${row.level} terisi ${
          row.filled > 0 && row.filledPct === 0 ? 'kurang dari 1' : row.filledPct
        } persen`}
      />
    </div>
  )
}

function LevelRowSkeleton() {
  return (
    <div className="motion-safe:animate-pulse">
      <div className="flex items-center justify-between">
        <div className="h-3 w-12 rounded-full bg-black/[0.08]" />
        <div className="h-3 w-24 rounded-full bg-black/[0.08]" />
      </div>
      <div className="mt-2 h-2 w-full rounded-full bg-black/[0.06]" />
    </div>
  )
}
