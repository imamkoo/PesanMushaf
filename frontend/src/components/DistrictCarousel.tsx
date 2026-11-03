import { useId } from 'react'
import { Link } from 'react-router-dom'
import { Navigation } from 'swiper/modules'
import { Swiper, SwiperSlide } from 'swiper/react'
import type { District } from '../types/batch'
import { ArrowLeft, ArrowRight } from './icons'
import { TiltCard } from './motion/TiltCard'

import 'swiper/css'

type DistrictCarouselProps = {
  districts: District[]
}

export function DistrictCarousel({ districts }: DistrictCarouselProps) {
  const navigationId = useId().replace(/[^a-zA-Z0-9_-]/g, '')
  const prevSelector = `.hut-district-prev-${navigationId}`
  const nextSelector = `.hut-district-next-${navigationId}`

  return (
    <div className="relative">
      <Swiper
        modules={[Navigation]}
        navigation={{
          prevEl: prevSelector,
          nextEl: nextSelector,
        }}
        slidesPerView="auto"
        spaceBetween={24}
        slidesOffsetAfter={20}
        slidesOffsetBefore={20}
        breakpoints={{
          1024: {
            slidesOffsetAfter: 120,
            slidesOffsetBefore: 120,
          },
        }}
        className="hut-district-carousel"
      >
        {districts.map((district) => (
          <SwiperSlide key={district.id} className="!w-fit">
            <Link
              to={district.href}
              className="card block rounded-[28px] focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-gold/40"
            >
              <TiltCard tilt={4} className="h-[320px] w-[240px] rounded-[28px]" spotlightColor="rgba(216, 180, 90, 0.16)">
                <article className="relative flex h-full w-full overflow-hidden rounded-[28px] bg-ink shadow-[0_30px_80px_-46px_rgba(0,0,0,0.7)]">
                  <img
                    src={district.imageUrl}
                    className="absolute h-full w-full object-cover opacity-55 saturate-[0.5] transition duration-700 group-hover:scale-105 group-hover:opacity-70 group-hover:saturate-100"
                    alt={district.name}
                    loading="lazy"
                    decoding="async"
                  />
                  <div className="absolute inset-0 bg-[linear-gradient(180deg,_rgba(12,27,58,0.28)_0%,_rgba(11,11,15,0.5)_44%,_rgba(11,11,15,0.96)_100%)]" />
                  <div
                    aria-hidden="true"
                    className="absolute inset-0 bg-[radial-gradient(circle_at_78%_12%,rgba(216,180,90,0.18),transparent_44%)]"
                  />
                  <span
                    aria-hidden="true"
                    className="absolute -right-12 -top-12 h-40 w-40 rounded-full border border-gold/20"
                  />
                  <span
                    aria-hidden="true"
                    className="absolute -right-8 -top-8 h-32 w-32 rounded-full border border-dashed border-gold/15"
                  />
                  <div className="absolute inset-0 border border-white/10" />
                  <div className="absolute left-4 top-4 z-10 rounded-full bg-white/90 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-ink shadow-[0_8px_20px_-14px_rgba(0,0,0,0.7)] backdrop-blur-md">
                    {district.code}
                  </div>
                  <span className="absolute right-4 top-4 z-10 flex h-9 w-9 translate-x-2 items-center justify-center rounded-full border border-white/20 bg-ink/40 text-white opacity-0 backdrop-blur-md transition duration-300 group-hover:translate-x-0 group-hover:opacity-100">
                    <ArrowRight className="h-4 w-4" />
                  </span>
                  <div className="relative z-10 mt-auto flex w-full flex-col justify-end gap-1 p-5">
                    <p className="text-[10px] font-black uppercase tracking-[0.18em] text-gold">Wilayah</p>
                    <h3 className="text-[24px] font-black leading-[28px] tracking-[-0.04em] text-white">{district.name}</h3>
                    <p className="font-semibold text-white/70">{district.totalBatches}</p>
                  </div>
                </article>
              </TiltCard>
            </Link>
          </SwiperSlide>
        ))}
      </Swiper>

      <div className="mt-5 hidden items-center justify-end gap-2 pr-5 md:flex">
        <button
          type="button"
          aria-label="Wilayah sebelumnya"
          className={`hut-district-prev hut-district-prev-${navigationId} flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-ink text-white transition duration-300 hover:bg-gold hover:text-ink focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-gold/40`}
        >
          <ArrowLeft className="h-4 w-4" />
        </button>
        <button
          type="button"
          aria-label="Wilayah berikutnya"
          className={`hut-district-next hut-district-next-${navigationId} flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-ink text-white transition duration-300 hover:bg-gold hover:text-ink focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-gold/40`}
        >
          <ArrowRight className="h-4 w-4" />
        </button>
      </div>
    </div>
  )
}
