import { Link, NavLink } from 'react-router-dom'
import { icons } from '../assets/projectAssets'

const navLinks = [
  { to: '/', label: 'Beranda', end: true },
  { to: '/districts', label: 'Kecamatan' },
  { to: '/booking', label: 'Daftar' },
] as const

const navLinkBaseClass =
  'rounded-full px-4 py-2.5 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[#ed3833]/15'

const actionButtonClass =
  'rounded-full px-4 py-2.5 text-sm font-bold transition focus-visible:outline-none focus-visible:ring-4'

export function AppNavbar() {
  return (
    <nav className="sticky top-0 z-50 border-b border-black/6 bg-[#f6f1e8]/88 shadow-[0_10px_40px_rgba(17,17,17,0.05)] backdrop-blur-2xl">
      <div className="mx-auto flex w-full max-w-[1200px] items-center justify-between gap-4 px-5 py-3.5">
        <Link to="/" aria-label="HUT500 home" className="group flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-full bg-[#111111] text-xs font-black tracking-[-0.02em] text-white transition group-hover:bg-[#ed3833]">
            H5
          </div>
          <p className="text-sm font-black uppercase tracking-[-0.02em] text-[#111111] sm:text-base">HUT500</p>
        </Link>

        <div className="hidden items-center gap-1 rounded-full border border-black/6 bg-white/72 p-1 shadow-[0_8px_24px_rgba(17,17,17,0.04)] md:flex">
          {navLinks.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              end={'end' in link ? link.end : false}
              className={({ isActive }) =>
                `${navLinkBaseClass} ${
                  isActive
                    ? 'bg-[#ed3833] !text-white shadow-[0_12px_28px_rgba(237,56,51,0.24)] hover:!text-white'
                    : 'text-[#111111]/74 hover:bg-white hover:text-[#ed3833]'
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
            className={`${actionButtonClass} border border-black/8 bg-white text-[#111111]/86 shadow-[0_10px_24px_rgba(17,17,17,0.04)] hover:border-[#ed3833]/20 hover:text-[#ed3833] focus-visible:ring-[#ed3833]/15`}
          >
            Cek Kode
          </Link>
          <a
            href="https://api.whatsapp.com/send/?phone=6287756877484&text&type=phone_number&app_absent=0"
            target="_blank"
            rel="noreferrer noopener"
            className={`${actionButtonClass} hidden items-center gap-2 bg-[#ed3833] text-white shadow-[0_12px_28px_rgba(237,56,51,0.24)] hover:bg-[#d92f2a] focus-visible:ring-[#ed3833]/20 sm:flex`}
          >
            <img src={icons.call} className="h-4 w-4 brightness-0 invert" alt="" />
            <span className="text-white">Helpdesk</span>
          </a>
        </div>
      </div>
    </nav>
  )
}
