import { Link } from 'react-router-dom'
import { photos } from '../assets/projectAssets'
import { AppNavbar } from '../components/AppNavbar'
import { BatchCard } from '../components/BatchCard'
import { DistrictCarousel } from '../components/DistrictCarousel'
import { useHut500Data, type EducationLevelStat } from '../hooks/useHut500Data'

const numberFormat = new Intl.NumberFormat('id-ID')

export function HomePage() {
  const { districts, batches, stats, isLoading, error } = useHut500Data()

  return (
    <>
      <AppNavbar />
      <header className="overflow-hidden bg-[#f4f0ea] px-5 py-4 sm:py-5">
        <section id="hero" className="mx-auto grid min-h-[calc(100vh-96px)] w-full max-w-[1280px] gap-4 lg:grid-cols-[1fr_0.52fr]">
          {/* Main Hero Card */}
          <div className="relative overflow-hidden rounded-[34px] bg-[#111111] p-6 text-white sm:p-8">
            <p className="absolute -left-4 top-3 text-[110px] font-black leading-none tracking-[-0.09em] text-white/[0.035] sm:text-[170px] lg:text-[220px]">
              500
            </p>
            <div className="relative z-10 flex h-full min-h-[520px] flex-col justify-between">
              <div className="flex items-center justify-between text-[10px] font-black uppercase tracking-[0.18em] text-white/45">
                <p>Distribusi Wilayah Jakarta</p>
                <p>2027</p>
              </div>

              <div className="max-w-[780px]">
                <div className="mb-5 flex w-fit items-center gap-3 rounded-full border border-white/10 bg-white/10 px-4 py-2 backdrop-blur">
                  <span className="h-2 w-2 rounded-full bg-[#ed3833]" />
                  <span className="text-sm font-black uppercase tracking-[-0.01em] text-white">
                    {districts.length || 44} Kecamatan Terakomodasi
                  </span>
                </div>
                <h1 className="text-[48px] font-black leading-[0.9] tracking-[-0.075em] sm:text-[76px] lg:text-[98px]">
                  Langkah Nyata untuk Jakarta.
                </h1>
                <div className="mt-6 grid max-w-[740px] gap-5 border-t border-white/10 pt-5 sm:grid-cols-[1fr_auto] sm:items-end">
                  <p className="text-base font-medium leading-7 text-white/62 sm:text-lg sm:leading-8">
                    Tentukan titik partisipasi Anda, amankan kuota pendaftaran yang tersedia, dan pantau status kontribusi Anda secara real-time.
                  </p>
                  <Link
                    to="/booking"
                    className="group flex min-w-[190px] items-center justify-center rounded-full bg-[#ed3833] px-7 py-4 text-base font-black text-white shadow-[0_22px_70px_rgba(237,56,51,0.35)] transition duration-300 hover:bg-white hover:!text-[#ed3833]"
                  >
                    Daftar Sekarang
                    <span className="ml-3 transition group-hover:translate-x-1">→</span>
                  </Link>
                </div>
              </div>

              <div className="grid gap-3 text-sm sm:grid-cols-3">
                <HeroMetric label="Cakupan Area" value="DKI Jakarta" />
                <HeroMetric label="Gelombang" value={isLoading ? 'Memuat...' : `${batches.length} Aktif`} />
                <HeroMetric label="Infrastruktur" value="✅ Terverifikasi" />
              </div>
            </div>
          </div>

          {/* Side Cards */}
          <div className="grid gap-4">
            <div className="relative min-h-[320px] overflow-hidden rounded-[34px] bg-[#ed3833] lg:min-h-0">
              <img src={photos.hero} className="absolute inset-0 h-full w-full object-cover mix-blend-multiply opacity-75" alt="HUT500 Jakarta" />
              <div className="absolute inset-0 bg-[linear-gradient(180deg,_rgba(237,56,51,0.05)_0%,_rgba(17,17,17,0.82)_100%)]" />
              <div className="relative z-10 flex h-full min-h-[240px] flex-col justify-between p-6 text-white">
                <p className="w-fit rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-[#111111]">
                  Visi Kami
                </p>
                <div>
                  <p className="text-sm font-black uppercase tracking-[0.16em] text-white/55">Filosofi Program</p>
                  <h2 className="mt-3 text-[36px] font-black leading-[0.95] tracking-[-0.06em] sm:text-[52px]">
                    Satu mushaf, satu doa.
                  </h2>
                </div>
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
              <Link to="/booking/details" className="group rounded-[34px] border border-black/10 bg-white p-6 shadow-[0_24px_80px_rgba(0,0,0,0.05)] transition hover:-translate-y-1 hover:bg-[#ed3833] hover:text-white">
                <p className="text-sm font-black uppercase tracking-[0.16em] text-[#ed3833] transition group-hover:text-white/65">Pusat Informasi</p>
                <p className="mt-5 text-[32px] font-black leading-[0.98] tracking-[-0.055em]">Lacak progres pendaftaran Anda.</p>
                <span className="mt-8 inline-flex h-12 w-12 items-center justify-center rounded-full bg-[#111111] text-white transition group-hover:bg-white group-hover:text-[#ed3833]">→</span>
              </Link>
              <a href="#districts" className="group rounded-[34px] border border-black/10 bg-white p-6 shadow-[0_24px_80px_rgba(0,0,0,0.05)] transition hover:-translate-y-1 hover:bg-[#ed3833] hover:text-white">
                <p className="text-sm font-black uppercase tracking-[0.16em] text-[#ed3833] transition group-hover:text-white/65">Eksplorasi Wilayah</p>
                <p className="mt-5 text-[32px] font-black leading-[0.98] tracking-[-0.055em] text-[#111111]">Temukan gelombang pendaftaran.</p>
                <span className="mt-8 inline-flex h-12 w-12 items-center justify-center rounded-full bg-[#ed3833] text-white transition group-hover:bg-white group-hover:text-[#ed3833]">↓</span>
              </a>
            </div>
          </div>
        </section>
      </header>

      <main className="bg-[#f7f7fd]">
        {/* District Carousel Section */}
        <section id="districts" className="flex flex-col gap-7 py-16 lg:py-20">
          <div className="mx-auto grid w-full max-w-[1180px] gap-5 px-5 sm:grid-cols-[1fr_auto] sm:items-end">
            <div>
              <p className="text-sm font-black uppercase tracking-[0.16em] text-[#ed3833]">Distribusi Aktif</p>
              <h2 className="mt-3 max-w-[700px] text-[38px] font-black leading-[0.94] tracking-[-0.06em] text-[#111111] sm:text-[58px]">
                Kecamatan pendaftaran.
              </h2>
            </div>
            <Link to="/districts" className="w-fit rounded-full border border-black/10 bg-white px-5 py-3 text-sm font-black text-[#111111] transition hover:border-[#ed3833] hover:text-[#ed3833]">
              Lihat Seluruh Wilayah
            </Link>
          </div>
          {error && (
            <p className="mx-auto w-full max-w-[1180px] rounded-2xl bg-[#fff7f7] px-5 py-4 text-sm font-bold text-[#ed3833]">
              Gagal menyinkronkan data: {error}.
            </p>
          )}
          {districts.length > 0 ? (
            <DistrictCarousel districts={districts} />
          ) : isLoading ? (
            <DistrictSkeleton />
          ) : (
            <p className="mx-auto w-full max-w-[1180px] rounded-2xl bg-white px-5 py-4 text-sm font-bold text-[#111111]/60">
              Belum ada wilayah pendaftaran tersedia.
            </p>
          )}
        </section>

        {/* Public stats overview */}
        {(batches.length > 0 || isLoading) && (
          <section id="stats" className="mx-auto w-full max-w-[1180px] px-5 pb-10">
            <div className="grid gap-5 lg:grid-cols-[1fr_1.2fr]">
              <div className="grid gap-3 sm:grid-cols-2">
                <StatCard
                  label="Total Pendaftar"
                  value={isLoading ? '…' : numberFormat.format(stats.totalRegistered)}
                  tone="primary"
                />
                <StatCard
                  label="Sisa Kuota"
                  value={isLoading ? '…' : numberFormat.format(stats.totalRemaining)}
                  tone="dark"
                />
                <StatCard
                  label="Terisi"
                  value={isLoading ? '…' : `${stats.fillPct}%`}
                  tone="muted"
                />
                <StatCard
                  label="Batch Penuh"
                  value={isLoading ? '…' : numberFormat.format(stats.fullBatches)}
                  tone="muted"
                />
              </div>

              <div className="rounded-[28px] border border-black/10 bg-white p-5 shadow-[0_20px_70px_rgba(0,0,0,0.04)] sm:p-7">
                <p className="text-xs font-black uppercase tracking-[0.16em] text-[#ed3833]">Kapasitas per Jenjang</p>
                <h3 className="mt-2 text-[24px] font-black leading-[1.05] tracking-[-0.03em] text-[#111111]">
                  Distribusi kuota antar kategori peserta.
                </h3>
                <div className="mt-5 grid gap-4">
                  {isLoading
                    ? Array.from({ length: 4 }).map((_, idx) => <LevelRowSkeleton key={idx} />)
                    : stats.byLevel.length > 0
                      ? stats.byLevel.map((row) => <LevelRow key={row.level} row={row} />)
                      : (
                        <p className="text-sm font-semibold text-[#111111]/55">
                          Belum ada data jenjang yang aktif.
                        </p>
                      )}
                </div>
              </div>
            </div>
          </section>
        )}

        {/* Batches Section */}
        <section id="batches" className="mx-auto grid w-full max-w-[1180px] gap-8 px-5 pb-20 lg:grid-cols-[0.72fr_1.28fr] lg:items-start">
          <div className="static lg:sticky lg:top-[96px] z-10 rounded-[36px] bg-[#ed3833] p-6 text-white shadow-[0_30px_90px_rgba(237,56,51,0.22)] sm:p-8">
            <p className="text-sm font-black uppercase tracking-[0.16em] text-white/65">Kuota Partisipasi</p>
            <h2 className="mt-4 text-[42px] font-black leading-[0.94] tracking-[-0.06em] sm:text-[58px]">
              Pilih Jalur. Ambil Bagian.
            </h2>
            <p className="mt-5 text-base font-medium leading-7 text-white/75">
              Pilih kategori yang sesuai dengan profil Anda. Gunakan jalur Reguler untuk umum, atau VIP bagi yang ingin mendapatkan akses eksklusif dan pendampingan khusus.
            </p>
            <Link to="/booking" className="mt-8 inline-flex rounded-full bg-black px-7 py-4 font-black text-[#ed3833] transition duration-300 hover:bg-white hover:!text-[#ed3833]">
              Mulai Pendaftaran
            </Link>
          </div>
          <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
            {batches.length > 0
              ? batches.slice(0, 4).map((batch) => (
                  <BatchCard key={batch.id} batch={batch} />
                ))
              : isLoading
                ? Array.from({ length: 4 }).map((_, idx) => <BatchCardSkeleton key={idx} />)
                : (
                    <p className="rounded-2xl bg-white p-5 text-sm font-bold text-[#111111]/60">
                      Belum ada gelombang pendaftaran yang dibuka.
                    </p>
                  )}
          </div>
        </section>
      </main>
    </>
  )
}

function HeroMetric({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
      <p className="text-xs font-black uppercase tracking-[0.16em] text-white/35">{label}</p>
      <p className="mt-2 font-black text-white">{value}</p>
    </div>
  )
}

type StatTone = 'primary' | 'dark' | 'muted'

const statToneStyles: Record<StatTone, string> = {
  primary: 'bg-[#ed3833] text-white shadow-[0_22px_70px_rgba(237,56,51,0.22)]',
  dark: 'bg-[#111111] text-white shadow-[0_22px_70px_rgba(0,0,0,0.18)]',
  muted: 'bg-white text-[#111111] border border-black/10 shadow-[0_20px_70px_rgba(0,0,0,0.04)]',
}

function StatCard({ label, value, tone }: { label: string; value: string; tone: StatTone }) {
  const labelTone =
    tone === 'muted' ? 'text-[#ed3833]' : 'text-white/70'

  return (
    <div className={`rounded-[24px] p-5 sm:p-6 ${statToneStyles[tone]}`}>
      <p className={`text-[11px] font-black uppercase tracking-[0.16em] ${labelTone}`}>{label}</p>
      <p className="mt-3 text-[34px] font-black leading-none tracking-[-0.04em] sm:text-[38px]">
        {value}
      </p>
    </div>
  )
}

function LevelRow({ row }: { row: EducationLevelStat }) {
  return (
    <div>
      <div className="flex items-baseline justify-between gap-3 text-sm">
        <p className="font-black text-[#111111]">{row.level}</p>
        <p className="font-semibold text-[#111111]/60">
          {numberFormat.format(row.filled)} / {numberFormat.format(row.capacity)} ({row.filledPct}%)
        </p>
      </div>
      <div
        role="progressbar"
        aria-valuenow={row.filledPct}
        aria-valuemin={0}
        aria-valuemax={100}
        aria-label={`Kuota ${row.level} terisi ${row.filledPct} persen`}
        className="mt-2 h-2 w-full overflow-hidden rounded-full bg-black/[0.06]"
      >
        <div
          className={`h-full transition-[width] duration-500 ${
            row.filledPct >= 100 ? 'bg-[#111111]' : row.filledPct >= 80 ? 'bg-amber-500' : 'bg-[#ed3833]'
          }`}
          style={{ width: `${row.filledPct}%` }}
        />
      </div>
    </div>
  )
}

function LevelRowSkeleton() {
  return (
    <div className="animate-pulse">
      <div className="flex items-center justify-between">
        <div className="h-3 w-12 rounded-full bg-black/[0.08]" />
        <div className="h-3 w-24 rounded-full bg-black/[0.08]" />
      </div>
      <div className="mt-2 h-2 w-full rounded-full bg-black/[0.06]" />
    </div>
  )
}

function DistrictSkeleton() {
  return (
    <div className="mx-auto flex w-full max-w-[1180px] gap-4 overflow-hidden px-5">
      {Array.from({ length: 4 }).map((_, idx) => (
        <div
          key={idx}
          className="h-[320px] w-[240px] shrink-0 animate-pulse rounded-[28px] bg-black/[0.06]"
        />
      ))}
    </div>
  )
}

function BatchCardSkeleton() {
  return (
    <div className="animate-pulse overflow-hidden rounded-[28px] border border-black/10 bg-white">
      <div className="h-[220px] w-full bg-black/[0.06]" />
      <div className="flex flex-col gap-4 p-6">
        <div className="h-3 w-24 rounded-full bg-black/[0.08]" />
        <div className="h-6 w-3/4 rounded-full bg-black/[0.08]" />
        <div className="grid grid-cols-2 gap-3">
          <div className="h-14 rounded-2xl bg-black/[0.05]" />
          <div className="h-14 rounded-2xl bg-black/[0.05]" />
        </div>
        <div className="h-1.5 w-full rounded-full bg-black/[0.05]" />
      </div>
    </div>
  )
}
