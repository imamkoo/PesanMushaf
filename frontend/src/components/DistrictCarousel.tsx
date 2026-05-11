import { Link } from 'react-router-dom'
import { Navigation } from 'swiper/modules'
import { Swiper, SwiperSlide } from 'swiper/react'
import type { District } from '../types/batch'

import 'swiper/css'
import 'swiper/css/navigation'

type DistrictCarouselProps = {
  districts: District[]
}

export function DistrictCarousel({ districts }: DistrictCarouselProps) {
  return (
    <div className="relative">
      <Swiper
        modules={[Navigation]}
        navigation
        slidesPerView="auto"
        spaceBetween={30}
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
            <Link to={district.href} className="card block">
              <article className="group relative flex h-[320px] w-[240px] shrink-0 overflow-hidden rounded-[28px] bg-[#111111] shadow-[0_24px_80px_rgba(0,0,0,0.08)]">
                <img src={district.imageUrl} className="absolute h-full w-full object-cover opacity-90 transition duration-500 group-hover:scale-105" alt={district.name} />
                <div className="absolute inset-0 bg-[linear-gradient(180deg,_rgba(0,0,0,0)_38%,_rgba(0,0,0,0.88)_100%)]" />
                <div className="absolute left-4 top-4 rounded-full bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-[#111111]">
                  {district.code}
                </div>
                <div className="relative z-10 mt-auto flex w-full flex-col justify-end gap-1 p-5">
                  <h3 className="text-[24px] font-black leading-[28px] tracking-[-0.04em] text-white">{district.name}</h3>
                  <p className="font-semibold text-white/70">{district.totalBatches}</p>
                </div>
              </article>
            </Link>
          </SwiperSlide>
        ))}
      </Swiper>
    </div>
  )
}
