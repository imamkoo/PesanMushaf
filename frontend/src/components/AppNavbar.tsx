import { useMotionValueEvent, useScroll } from 'framer-motion'
import { useState } from 'react'
import { Link, NavLink } from 'react-router-dom'
import { icons } from '../assets/projectAssets'
import { SealEmblem } from './SealEmblem'

const navLinks = [
  { to: '/', label: 'Beranda', end: true },
  { to: '/districts', label: 'Kecamatan' },
  { to: '/booking', label: 'Daftar' },
] as const

const navLinkBaseClass =
  'rounded-full px-4 py-2.5 text-sm font-bold transition duration-300 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand/20'

const actionButtonClass =
  'rounded-full px-4 py-2.5 text-sm font-bold transition duration-300 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand/20'

export function AppNavbar() {
  const { scrollY } = useScroll()
  const [scrolled, setScrolled] = useState(false)

  useMotionValueEvent(scrollY, 'change', (latest) => {
    setScrolled(latest > 18)
  })

  return (
    <nav
      className={`sticky top-0 z-50 border-b border-black/[0.06] backdrop-blur-2xl transition-[background-color,box-shadow,padding] duration-500 ${
        scrolled
          ? 'bg-cream/90 shadow-[0_18px_50px_-30px_rgba(17,17,17,0.5)]'
          : 'bg-cream/75 shadow-[0_10px_40px_-34px_rgba(17,17,17,0.4)]'
      }`}
    >
      <div
        className={`mx-auto flex w-full max-w-[1200px] items-center justify-between gap-4 px-5 transition-[padding] duration-500 ${
          scrolled ? 'py-2.5' : 'py-3.5'
        }`}
      >
        <Link
          to="/"
          aria-label="JAKARTA500 — Beranda"
          className="group flex items-center gap-3 rounded-full focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand/20"
        >
          <SealEmblem
            size={40}
            eager
            className="transition-transform duration-500 group-hover:-rotate-3 group-hover:scale-[1.04]"
          />
          <span className="flex flex-col">
            <span className="text-[15px] font-black leading-none tracking-[-0.03em] text-ink sm:text-base">
              JAKARTA<span className="text-gold-deep">500</span>
            </span>
            <span className="mt-1 hidden text-[8px] font-black uppercase leading-none tracking-[0.2em] text-ink/55 sm:block">
              Arsip Abadi · 500 Tahun
            </span>
          </span>
        </Link>

        <div className="hidden items-center gap-1 rounded-full border border-white/70 bg-white/50 p-1 shadow-[0_12px_30px_-24px_rgba(17,17,17,0.45)] backdrop-blur-xl lg:flex">
          {navLinks.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              end={'end' in link ? link.end : false}
              className={({ isActive }) =>
                `${navLinkBaseClass} ${
                  isActive
                    ? 'bg-brand !text-white shadow-[0_14px_32px_-16px_rgba(237,56,51,0.8)] hover:!text-white'
                    : 'text-ink/70 hover:bg-white hover:text-brand'
                }`
              }
            >
              {link.label}
            </NavLink>
          ))}
        </div>

        <div className="flex items-center gap-2">
          <Link
            to="/booking/details"
            className={`${actionButtonClass} border border-black/[0.08] bg-white/70 text-ink/80 shadow-[0_10px_24px_-22px_rgba(17,17,17,0.5)] backdrop-blur-xl hover:border-gold/60 hover:text-gold-deep`}
          >
            Cek Kode
          </Link>
          <a
            href="https://api.whatsapp.com/send/?phone=6287756877484&text&type=phone_number&app_absent=0"
            target="_blank"
            rel="noreferrer noopener"
            className={`${actionButtonClass} shine-sweep hidden items-center gap-2 bg-brand text-white shadow-[0_16px_36px_-18px_rgba(237,56,51,0.9)] hover:bg-brand-dark sm:flex`}
          >
            <img src={icons.call} className="h-4 w-4 brightness-0 invert" alt="" />
            <span className="relative z-10 text-white">Bantuan</span>
          </a>
        </div>
      </div>
    </nav>
  )
}
