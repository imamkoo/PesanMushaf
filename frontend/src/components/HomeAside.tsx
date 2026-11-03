import { Link } from 'react-router-dom'
import { photos } from '../assets/projectAssets'
import { ArrowDown, ArrowRight } from './icons'
import { Reveal } from './motion/Reveal'
import { TiltCard } from './motion/TiltCard'
import { SealEmblem } from './SealEmblem'

export function HomeAside() {
  return (
    <div className="grid gap-4">
      <Reveal className="min-h-[280px] lg:min-h-0">
        <TiltCard
          tilt={3}
          spotlightColor="rgba(255, 255, 255, 0.18)"
          className="relative h-full min-h-[280px] overflow-hidden rounded-[34px] bg-brand"
        >
          <img
            src={photos.hero}
            width={1200}
            height={960}
            className="absolute inset-0 h-full w-full object-cover opacity-80 mix-blend-multiply transition duration-700 group-hover:scale-[1.06]"
            alt="JAKARTA500 — Arsip Abadi Jakarta 500 Tahun"
            loading="lazy"
            decoding="async"
          />
          <div className="absolute inset-0 bg-[linear-gradient(180deg,_rgba(237,56,51,0.05)_0%,_rgba(17,17,17,0.88)_100%)]" />
          <div
            aria-hidden="true"
            className="absolute inset-0 bg-[radial-gradient(circle_at_82%_18%,rgba(216,180,90,0.24),transparent_46%)]"
          />
          <div className="relative z-10 flex h-full min-h-[220px] flex-col justify-between p-5 text-white">
            <div className="flex items-center justify-between gap-3">
              <p className="w-fit rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-ink shadow-[0_10px_30px_-16px_rgba(0,0,0,0.7)]">
                Visi Kami
              </p>
              <SealEmblem size={38} />
            </div>
            <div>
              <p className="text-sm font-black uppercase tracking-[0.16em] text-white/55">Filosofi Program</p>
              <h2 className="mt-3 max-w-[12ch] text-[32px] font-black leading-[0.95] tracking-[-0.04em] sm:text-[44px]">
                Satu mushaf, satu doa.
              </h2>
            </div>
          </div>
        </TiltCard>
      </Reveal>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
        <Reveal delay={0.06}>
          <TiltCard tilt={2} spotlightColor="rgba(237, 56, 51, 0.12)" className="rounded-[34px]">
            <Link
              to="/booking/details"
              className="group relative flex h-full flex-col rounded-[34px] border border-white/70 bg-white/70 p-5 shadow-[0_24px_80px_-50px_rgba(0,0,0,0.35)] backdrop-blur-xl transition duration-300 hover:-translate-y-1 hover:border-brand/20 hover:bg-brand hover:text-white hover:shadow-[0_30px_80px_-34px_rgba(237,56,51,0.55)] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand/20"
            >
              <p className="text-sm font-black uppercase tracking-[0.16em] text-brand transition group-hover:text-white/65">
                Pusat Informasi
              </p>
              <p className="mt-4 max-w-[18ch] text-[28px] font-black leading-[1] tracking-[-0.04em] text-ink transition group-hover:text-white">
                Lacak progres pendaftaran Anda.
              </p>
              <span className="mt-6 inline-flex h-11 w-11 items-center justify-center rounded-full bg-ink text-white transition duration-300 group-hover:bg-white group-hover:text-brand">
                <ArrowRight />
              </span>
            </Link>
          </TiltCard>
        </Reveal>

        <Reveal delay={0.12}>
          <TiltCard tilt={2} spotlightColor="rgba(216, 180, 90, 0.18)" className="rounded-[34px]">
            <a
              href="#districts"
              className="group relative flex h-full flex-col rounded-[34px] border border-white/70 bg-white/70 p-5 shadow-[0_24px_80px_-50px_rgba(0,0,0,0.35)] backdrop-blur-xl transition duration-300 hover:-translate-y-1 hover:border-gold/50 hover:bg-ink hover:text-white hover:shadow-[0_30px_80px_-34px_rgba(0,0,0,0.6)] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-gold/30"
            >
              <p className="text-sm font-black uppercase tracking-[0.16em] text-gold-deep transition group-hover:text-gold">
                Eksplorasi Wilayah
              </p>
              <p className="mt-4 max-w-[18ch] text-[28px] font-black leading-[1] tracking-[-0.04em] text-ink transition group-hover:text-white">
                Temukan gelombang pendaftaran.
              </p>
              <span className="mt-6 inline-flex h-11 w-11 items-center justify-center rounded-full bg-brand text-white transition duration-300 group-hover:bg-gold group-hover:text-ink">
                <ArrowDown />
              </span>
            </a>
          </TiltCard>
        </Reveal>
      </div>
    </div>
  )
}
