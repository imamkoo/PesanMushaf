import { lazy, Suspense } from 'react'
import { Link } from 'react-router-dom'
import { AppNavbar } from '../components/AppNavbar'
import { BatchCard } from '../components/BatchCard'
import { HomeAside } from '../components/HomeAside'
import { HomeHero } from '../components/HomeHero'
import { ArrowRight } from '../components/icons'
import { Reveal } from '../components/motion/Reveal'
import { SealEmblem } from '../components/SealEmblem'
import { StatsPanel } from '../components/StatsPanel'
import { useHut500Data } from '../hooks/useHut500Data'

const DistrictCarousel = lazy(() =>
  import('../components/DistrictCarousel').then((module) => ({ default: module.DistrictCarousel })),
)

const whatsappUrl =
  'https://api.whatsapp.com/send/?phone=6287756877484&text&type=phone_number&app_absent=0'

export function HomePage() {
  const { districts, batches, stats, isLoading, error } = useHut500Data()

  return (
    <>
      <AppNavbar />

      <header className="relative overflow-hidden bg-cream px-5 pb-4 pt-4 sm:pt-5">
        <div
          aria-hidden="true"
          className="pointer-events-none absolute inset-0 bg-[radial-gradient(70%_60%_at_12%_0%,rgba(237,56,51,0.08),transparent_62%),radial-gradient(52%_52%_at_92%_10%,rgba(216,180,90,0.13),transparent_62%)]"
        />
        <section
          id="hero"
          className="relative mx-auto grid min-h-[600px] w-full max-w-[1280px] gap-4 lg:min-h-[680px] lg:grid-cols-[1fr_0.52fr]"
        >
          <HomeHero districtCount={districts.length} />
          <HomeAside />
        </section>
      </header>

      <main className="relative bg-cream">
        <section id="districts" className="relative flex scroll-mt-24 flex-col gap-7 py-16 lg:py-20">
          <div className="mx-auto grid w-full max-w-[1180px] gap-5 px-5 sm:grid-cols-[1fr_auto] sm:items-end">
            <Reveal>
              <h2 className="max-w-[700px] text-balance text-[38px] font-black leading-[0.94] tracking-[-0.04em] text-ink sm:text-[58px]">
                Kecamatan pendaftaran.
              </h2>
              <p className="mt-4 flex items-center gap-2 text-sm font-bold text-ink/60">
                <span className="h-2 w-2 rounded-full bg-brand motion-safe:animate-pulse" />
                {districts.length || 44} wilayah aktif · diperbarui langsung dari lapangan
              </p>
            </Reveal>
            <Reveal delay={0.08}>
              <Link
                to="/districts"
                className="group inline-flex w-fit items-center gap-2 rounded-full border border-white/70 bg-white/65 px-5 py-3 text-sm font-black text-ink shadow-[0_18px_40px_-34px_rgba(0,0,0,0.5)] backdrop-blur-xl transition duration-300 hover:border-gold/60 hover:text-gold-deep focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-gold/30"
              >
                Lihat Seluruh Wilayah
                <ArrowRight className="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
              </Link>
            </Reveal>
          </div>

          {error && (
            <p className="glass-light mx-auto w-full max-w-[1180px] rounded-2xl border-brand/20 px-5 py-4 text-sm font-bold text-brand">
              Data wilayah belum dapat dimuat. {error}
            </p>
          )}

          {districts.length > 0 ? (
            <Suspense fallback={<DistrictSkeleton />}>
              <DistrictCarousel districts={districts} />
            </Suspense>
          ) : isLoading ? (
            <DistrictSkeleton />
          ) : (
            <p className="glass-light mx-auto w-full max-w-[1180px] rounded-2xl px-5 py-4 text-sm font-bold text-ink/60">
              Belum ada wilayah pendaftaran tersedia.
            </p>
          )}
        </section>

        {(batches.length > 0 || isLoading) && <StatsPanel stats={stats} isLoading={isLoading} />}

        <section
          id="batches"
          className="mx-auto grid w-full max-w-[1180px] scroll-mt-24 gap-8 px-5 pb-20 lg:grid-cols-[0.72fr_1.28fr] lg:items-start"
        >
          <Reveal className="static z-10 lg:sticky lg:top-[96px]">
            <div className="relative overflow-hidden rounded-[36px] bg-[linear-gradient(145deg,#ed3833_0%,#dc302c_54%,#b92320_100%)] p-6 text-white shadow-[0_34px_90px_-38px_rgba(237,56,51,0.55)] sm:p-8">
              <div
                aria-hidden="true"
                className="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-[radial-gradient(circle,rgba(216,180,90,0.32),transparent_68%)] blur-2xl"
              />
              <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-x-0 top-0 h-px bg-[linear-gradient(90deg,transparent,rgba(255,255,255,0.5),transparent)]"
              />
              <img
                src="/assets/images/logos/logo-jejaka500.png"
                alt=""
                width={512}
                height={512}
                loading="lazy"
                decoding="async"
                draggable={false}
                className="seal-watermark seal-watermark-engraved pointer-events-none absolute -bottom-24 -right-20 h-72 w-72 rounded-full object-cover opacity-[0.20]"
              />

              <div className="relative flex items-center justify-between gap-4">
                <p className="text-[10px] font-black uppercase tracking-[0.2em] text-white/65">Kuota Partisipasi</p>
                <SealEmblem size={42} />
              </div>
              <h2 className="relative mt-5 text-[42px] font-black leading-[0.94] tracking-[-0.04em] sm:text-[46px]">
                Pilih Jalur.
                <br />
                Ambil Bagian.
              </h2>
              <p className="relative mt-5 max-w-[38ch] text-base font-medium leading-7 text-white/75">
                Pilih kategori yang sesuai dengan profil Anda. Gunakan jalur Reguler untuk umum, atau VIP bagi yang ingin mendapatkan akses eksklusif dan pendampingan khusus.
              </p>
              <Link
                to="/booking"
                className="shine-sweep group relative mt-8 inline-flex items-center gap-3 rounded-full bg-ink px-7 py-4 font-black text-white shadow-[0_22px_50px_-24px_rgba(0,0,0,0.8)] transition duration-300 hover:bg-white hover:text-ink focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-white/30"
              >
                <span className="relative z-10">Mulai Pendaftaran</span>
                <ArrowRight className="relative z-10 h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
              </Link>
            </div>
          </Reveal>

          <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
            {batches.length > 0 ? (
              <>
                {batches.slice(0, 4).map((batch, index) => (
                  <Reveal key={batch.id} delay={Math.min(index * 0.06, 0.24)} className="h-full">
                    <BatchCard batch={batch} />
                  </Reveal>
                ))}
                {batches.length % 2 === 1 ? (
                  <Reveal delay={0.12} className="hidden h-full md:block">
                    <div className="relative flex h-full min-h-[320px] flex-col justify-between overflow-hidden rounded-[28px] border border-dashed border-black/15 bg-white/40 p-6 backdrop-blur-xl">
                      <div
                        aria-hidden="true"
                        className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgba(11,11,15,0.07)_1px,transparent_0)] [background-size:20px_20px] opacity-40"
                      />
                      <div className="relative flex items-start justify-between gap-4">
                        <p className="text-sm font-black uppercase tracking-[0.16em] text-gold-deep">
                          Gelombang berikutnya
                        </p>
                        <SealEmblem size={38} glow={false} />
                      </div>
                      <div className="relative">
                        <p className="max-w-[16ch] text-[28px] font-black leading-[1] tracking-[-0.04em] text-ink">
                          Slot baru akan tampil di sini.
                        </p>
                        <Link
                          to="/districts"
                          className="group mt-6 inline-flex items-center gap-2 text-sm font-black text-brand transition hover:text-brand-dark"
                        >
                          Jelajahi kecamatan
                          <ArrowRight className="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
                        </Link>
                      </div>
                    </div>
                  </Reveal>
                ) : null}
              </>
            ) : isLoading ? (
              Array.from({ length: 4 }).map((_, index) => <BatchCardSkeleton key={index} />)
            ) : (
              <p className="glass-light rounded-2xl px-5 py-4 text-sm font-bold text-ink/60">
                Pendaftaran dibuka. Gelombang pertama dibuat otomatis saat pendaftaran pertama masuk.
              </p>
            )}
          </div>
        </section>
      </main>

      <footer className="border-t border-black/[0.08] bg-cream px-5 py-10">
        <div className="mx-auto flex max-w-[1180px] flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-3">
            <SealEmblem size={48} />
            <div>
              <p className="text-sm font-black tracking-[-0.03em] text-ink">
                JEJAKA<span className="text-gold-deep">500</span>
              </p>
              <p className="mt-1 text-[9px] font-black uppercase tracking-[0.18em] text-ink/55">
                Jejak Jakarta 500 Tahun
              </p>
            </div>
          </div>
          <div className="flex flex-col gap-2 sm:items-end">
            <p className="text-xs font-semibold text-ink/60">Satu Kampung · Satu Jejak Digital</p>
            <a
              href={whatsappUrl}
              target="_blank"
              rel="noreferrer noopener"
              className="group inline-flex w-fit items-center gap-2 text-xs font-black text-brand transition hover:text-brand-dark focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand/20"
            >
              Hubungi Kami
              <ArrowRight className="h-3.5 w-3.5 transition-transform duration-300 group-hover:translate-x-1" />
            </a>
          </div>
        </div>
      </footer>
    </>
  )
}

function DistrictSkeleton() {
  return (
    <div className="mx-auto flex w-full max-w-[1180px] gap-4 overflow-hidden px-5">
      {Array.from({ length: 4 }).map((_, index) => (
        <div
          key={index}
          className="h-[320px] w-[240px] shrink-0 motion-safe:animate-pulse rounded-[28px] border border-white/70 bg-white/55 backdrop-blur-xl"
        />
      ))}
    </div>
  )
}

function BatchCardSkeleton() {
  return (
    <div className="motion-safe:animate-pulse overflow-hidden rounded-[28px] border border-white/70 bg-white/70 backdrop-blur-xl">
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
