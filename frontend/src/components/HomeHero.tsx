import {
  m,
  useMotionTemplate,
  useMotionValue,
  useReducedMotion,
  useScroll,
  useSpring,
  useTransform,
  type Variants,
} from 'framer-motion'
import { useRef } from 'react'
import type { PointerEvent } from 'react'
import { Link } from 'react-router-dom'
import { useMediaQuery } from '../hooks/useMediaQuery'
import { ArrowRight, CheckIcon } from './icons'
import { Magnetic } from './motion/Magnetic'
import { SealEmblem } from './SealEmblem'

type HomeHeroProps = {
  districtCount: number
  batchCount: number
  isLoading: boolean
}

const ease: [number, number, number, number] = [0.16, 1, 0.3, 1]

export function HomeHero({ districtCount, batchCount, isLoading }: HomeHeroProps) {
  const reduce = useReducedMotion()
  const canHover = useMediaQuery('(hover: hover) and (pointer: fine)')
  const interactive = canHover && !reduce
  const cardRef = useRef<HTMLDivElement>(null)

  const { scrollYProgress } = useScroll({
    target: cardRef,
    offset: ['start start', 'end start'],
  })
  const sealY = useTransform(scrollYProgress, [0, 1], [0, -70])
  const glowY = useTransform(scrollYProgress, [0, 1], [0, 55])

  const rotateX = useMotionValue(0)
  const rotateY = useMotionValue(0)
  const springX = useSpring(rotateX, { stiffness: 150, damping: 22, mass: 0.5 })
  const springY = useSpring(rotateY, { stiffness: 150, damping: 22, mass: 0.5 })
  const pointerX = useMotionValue(50)
  const pointerY = useMotionValue(50)
  const spotlight = useMotionTemplate`radial-gradient(540px circle at ${pointerX}% ${pointerY}%, rgba(216, 180, 90, 0.15), transparent 46%)`

  function handlePointerMove(event: PointerEvent<HTMLDivElement>) {
    if (!interactive) {
      return
    }

    const bounds = event.currentTarget.getBoundingClientRect()
    const x = (event.clientX - bounds.left) / bounds.width
    const y = (event.clientY - bounds.top) / bounds.height

    rotateY.set((x - 0.5) * 5)
    rotateX.set((0.5 - y) * 5)
    pointerX.set(x * 100)
    pointerY.set(y * 100)
  }

  function handlePointerLeave() {
    rotateX.set(0)
    rotateY.set(0)
    pointerX.set(50)
    pointerY.set(50)
  }

  const fadeUp: Variants = reduce
    ? { hidden: { opacity: 1 }, show: { opacity: 1 } }
    : {
        hidden: { opacity: 0, y: 20 },
        show: { opacity: 1, y: 0, transition: { duration: 0.75, ease } },
      }

  const lineReveal: Variants = reduce
    ? { hidden: { y: 0 }, show: { y: 0 } }
    : {
        hidden: { y: '112%' },
        show: { y: '0%', transition: { duration: 0.9, ease } },
      }

  const stagger = {
    hidden: {},
    show: { transition: { staggerChildren: 0.08, delayChildren: 0.16 } },
  }

  return (
    <m.div
      ref={cardRef}
      initial={reduce ? false : { opacity: 0, y: 26 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.85, ease }}
      className="relative h-full [perspective:1400px]"
    >
      <m.div
        onPointerMove={handlePointerMove}
        onPointerLeave={handlePointerLeave}
        style={{
          rotateX: springX,
          rotateY: springY,
          transformPerspective: 1200,
          transformStyle: 'preserve-3d',
        }}
        className="relative flex h-full min-h-[560px] flex-col overflow-hidden rounded-[34px] bg-ink p-6 text-white shadow-[0_50px_120px_-60px_rgba(0,0,0,0.85)] sm:p-8"
      >
        <div
          aria-hidden="true"
          className="pointer-events-none absolute inset-x-0 top-0 h-px bg-[linear-gradient(90deg,transparent,rgba(255,255,255,0.28),transparent)]"
        />

        <m.div
          aria-hidden="true"
          className="pointer-events-none absolute inset-0 z-0"
          style={{ background: spotlight }}
        />

        <m.div
          aria-hidden="true"
          style={{ y: reduce ? 0 : glowY }}
          className="pointer-events-none absolute -right-28 -top-28 h-[380px] w-[380px] rounded-full bg-[radial-gradient(circle,rgba(216,180,90,0.24),rgba(216,180,90,0.06)_46%,transparent_70%)] blur-2xl"
        />
        <div
          aria-hidden="true"
          className="pointer-events-none absolute -bottom-32 -left-24 h-[340px] w-[340px] rounded-full bg-[radial-gradient(circle,rgba(237,56,51,0.2),transparent_68%)] blur-3xl"
        />

        <m.div
          aria-hidden="true"
          style={{ y: reduce ? 0 : sealY }}
          className="pointer-events-none absolute -right-16 -top-20 z-0 h-[240px] w-[240px] sm:-right-10 sm:-top-16 sm:h-[360px] sm:w-[360px]"
        >
          <span className="absolute inset-0 rounded-full border border-gold/20" />
          <span className="absolute inset-6 rounded-full border border-dashed border-gold/15 motion-safe:animate-[spin_70s_linear_infinite]" />
          <img
            src="/icons/icon-512.png"
            alt=""
            width={512}
            height={512}
            loading="eager"
            decoding="async"
            draggable={false}
            className="seal-watermark absolute inset-0 h-full w-full rounded-full object-cover opacity-[0.08]"
          />
        </m.div>

        <m.div
          variants={fadeUp}
          initial="hidden"
          animate="show"
          className="relative z-10 flex items-center justify-between gap-4 text-[10px] font-black uppercase tracking-[0.18em] text-white/45"
        >
          <p>Distribusi Wilayah Jakarta</p>
          <p className="flex items-center gap-2">
            <span className="h-1.5 w-1.5 rounded-full bg-gold" />
            2027
          </p>
        </m.div>

        <m.div
          variants={stagger}
          initial="hidden"
          animate="show"
          className="relative z-10 flex flex-1 flex-col justify-center py-8 sm:py-10"
        >
          <m.div
            variants={fadeUp}
            className="mb-6 flex w-fit items-center gap-3 rounded-full border border-white/10 bg-white/[0.06] px-4 py-2 backdrop-blur-xl"
          >
            <span className="relative flex h-2 w-2">
              <span className="absolute inline-flex h-full w-full rounded-full bg-brand opacity-70 motion-safe:animate-ping" />
              <span className="relative inline-flex h-2 w-2 rounded-full bg-brand" />
            </span>
            <span className="text-[11px] font-black uppercase tracking-[0.16em] text-white/80">
              {districtCount || 44} Kecamatan Terakomodasi
            </span>
          </m.div>

          <h1
            aria-label="Langkah Nyata untuk Jakarta."
            className="max-w-[790px] text-[clamp(2.25rem,8.6vw,6.1rem)] font-black leading-[0.92] tracking-[-0.04em] text-white"
          >
            <span className="block overflow-hidden pb-1">
              <m.span variants={lineReveal} className="block">
                Langkah Nyata
              </m.span>
            </span>
            <span className="block overflow-hidden pb-1">
              <m.span variants={lineReveal} className="block">
                untuk Jakarta.
              </m.span>
            </span>
          </h1>

          <m.div
            variants={fadeUp}
            className="mt-7 grid max-w-[770px] gap-6 border-t border-white/10 pt-6 sm:grid-cols-[1fr_auto] sm:items-end"
          >
            <p className="max-w-[52ch] text-base font-medium leading-7 text-white/60 sm:text-lg sm:leading-8">
              Tentukan titik partisipasi Anda, amankan kuota pendaftaran yang tersedia, dan pantau status kontribusi Anda secara real-time.
            </p>
            <Magnetic>
              <Link
                to="/booking"
                className="shine-sweep group relative flex min-w-[190px] items-center justify-center gap-3 rounded-full bg-brand px-7 py-4 text-base font-black text-white shadow-[0_26px_60px_-24px_rgba(237,56,51,0.9)] transition duration-300 hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand/30"
              >
                <span className="relative z-10">Daftar Sekarang</span>
                <ArrowRight className="relative z-10 h-5 w-5 transition-transform duration-300 group-hover:translate-x-1" />
              </Link>
            </Magnetic>
          </m.div>
        </m.div>

        <m.div variants={fadeUp} initial="hidden" animate="show" className="relative z-10 space-y-4 border-t border-white/10 pt-5">
          <div className="flex items-center gap-3">
            <SealEmblem size={56} eager />
            <div className="min-w-0">
              <p className="text-[9px] font-black uppercase tracking-[0.2em] text-gold sm:text-[10px]">
                Arsip Abadi Jakarta 500 Tahun
              </p>
              <p className="mt-1 text-xs font-semibold leading-5 text-white/50">
                Satu Kampung · Satu Jejak Digital
              </p>
            </div>
          </div>

          <div className="grid divide-y divide-white/10 overflow-hidden rounded-2xl border border-white/10 bg-white/[0.04] backdrop-blur-xl sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <HeroMetric label="Cakupan Area" value="DKI Jakarta" />
            <HeroMetric label="Gelombang" value={isLoading ? 'Memuat...' : `${batchCount} Aktif`} />
            <HeroMetric label="Infrastruktur" value="Terverifikasi" verified />
          </div>
        </m.div>
      </m.div>
    </m.div>
  )
}

function HeroMetric({
  label,
  value,
  verified = false,
}: {
  label: string
  value: string
  verified?: boolean
}) {
  return (
    <div className="px-4 py-3">
      <p className="text-[9px] font-black uppercase tracking-[0.18em] text-white/35">{label}</p>
      <p className="mt-1.5 flex items-center gap-1.5 text-sm font-black text-white [word-spacing:0.14em]">
        {verified ? (
          <span className="flex h-4 w-4 items-center justify-center rounded-full bg-gold/15 text-gold">
            <CheckIcon className="h-3 w-3" />
          </span>
        ) : null}
        {value}
      </p>
    </div>
  )
}
