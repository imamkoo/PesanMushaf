import { Link, NavLink } from 'react-router-dom'
import { icons } from '../assets/projectAssets'

const navLinks = [
  { to: '/', label: 'Beranda', end: true },
  { to: '/districts', label: 'Kecamatan' },
  { to: '/booking', label: 'Daftar' },
] as const

export function AppNavbar() {
  return (
    <nav className="sticky top-0 z-50 border-b border-black/5 bg-[#f4f0ea]/85 backdrop-blur-xl">
      <div className="mx-auto flex w-full max-w-[1180px] items-center justify-between gap-3 px-5 py-4">
        <Link to="/" aria-label="HUT500 home" className="group flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-full bg-[#111111] text-xs font-black tracking-[-0.02em] text-white transition group-hover:bg-[#ed3833]">
            H5
          </div>
          <p className="text-sm font-black uppercase tracking-[-0.02em] text-[#111111] sm:text-base">HUT500</p>
        </Link>

        <div className="hidden items-center gap-1 md:flex">
          {navLinks.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              end={'end' in link ? link.end : false}
              className={({ isActive }) =>
                `rounded-full px-4 py-2 text-sm font-bold transition ${
                  isActive
                    ? 'bg-[#ed3833] text-white'
                    : 'text-[#111111] hover:bg-[#ed3833]/5'
                }`
              }
            >
              {link.label}
            </NavLink>
          ))}
        </div>

        <div className="flex items-center gap-2">
          <Link to="/booking/details" className="rounded-full border border-black/10 bg-white px-4 py-2 text-sm font-bold text-[#111111] transition hover:border-[#ed3833] hover:text-[#ed3833]">
            Cek Kode
          </Link>
          <a
            href="https://api.whatsapp.com/send/?phone=6287756877484&text&type=phone_number&app_absent=0"
            target="_blank"
            rel="noreferrer noopener"
            className="hidden items-center gap-2 rounded-full bg-[#ed3833] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#d92f2a] sm:flex"
          >
            <img src={icons.call} className="h-4 w-4 brightness-0 invert" alt="" />
            <span className="text-white">Helpdesk</span>
          </a>
        </div>
      </div>
    </nav>
  )
}
