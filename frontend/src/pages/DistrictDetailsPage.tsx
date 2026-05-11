import { Link, useParams } from 'react-router-dom'
import { AppNavbar } from '../components/AppNavbar'
import { BatchCard } from '../components/BatchCard'
import { useHut500Data } from '../hooks/useHut500Data'

export function DistrictDetailsPage() {
  const { slug } = useParams()
  const { districts, batches, isLoading, error } = useHut500Data()

  if (!slug) {
    return (
      <>
        <AppNavbar />
        <main className="bg-[#f4f0ea] px-5 py-8">
          <section className="mx-auto w-full max-w-[1180px]">
            <header className="rounded-[38px] bg-[#111111] p-6 text-white sm:p-8 lg:p-10">
              <p className="text-sm font-black uppercase tracking-[0.16em] text-[#ed3833]">Semua Kecamatan</p>
              <h1 className="mt-4 max-w-[820px] text-[48px] font-black leading-[0.94] tracking-[-0.06em] sm:text-[76px]">
                {isLoading ? 'Memuat kecamatan dari backend.' : 'Pilih kecamatan sebelum melihat batch.'}
              </h1>
            </header>
            {error ? (
              <p className="mt-6 rounded-2xl bg-[#fff7f7] px-5 py-4 text-sm font-bold text-[#ed3833]">
                Backend API belum bisa dimuat: {error}.
              </p>
            ) : null}
            <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
              {districts.map((district) => (
                <Link key={district.id} to={district.href} className="group relative min-h-[240px] overflow-hidden rounded-[30px] bg-[#111111] shadow-[0_24px_80px_rgba(0,0,0,0.08)]">
                  <img src={district.imageUrl} className="absolute inset-0 h-full w-full object-cover opacity-80 transition duration-500 group-hover:scale-105" alt={district.name} />
                  <div className="absolute inset-0 bg-[linear-gradient(180deg,_rgba(0,0,0,0)_30%,_rgba(0,0,0,0.84)_100%)]" />
                  <div className="relative z-10 flex h-full min-h-[240px] flex-col justify-between p-5 text-white">
                    <p className="w-fit rounded-full bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-[#111111]">{district.code}</p>
                    <div>
                      <h2 className="text-[24px] font-black leading-[28px] tracking-[-0.04em]">{district.name}</h2>
                      <p className="mt-2 font-semibold text-white/70">{district.totalBatches}</p>
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          </section>
        </main>
      </>
    )
  }

  const district = districts.find((item) => item.slug === slug)

  if (!district) {
    return (
      <>
        <AppNavbar />
        <main className="bg-[#f4f0ea] px-5 py-8">
          <section className="mx-auto rounded-[38px] bg-white p-6 shadow-[0_24px_90px_rgba(0,0,0,0.06)] sm:p-8 lg:max-w-[900px] lg:p-10">
            <p className="text-sm font-black uppercase tracking-[0.16em] text-[#ed3833]">Kecamatan tidak tersedia</p>
            <h1 className="mt-4 text-[42px] font-black leading-[0.96] tracking-[-0.06em] text-[#111111] sm:text-[64px]">
              {isLoading ? 'Memuat data ...' : 'Data kecamatan tidak ditemukan.'}
            </h1>
            <p className="mt-5 text-base font-medium leading-7 text-[#111111]/62">
              {error || 'Pastikan data kecamatan tersedia dari endpoint /api/districts.'}
            </p>
          </section>
        </main>
      </>
    )
  }

  const displayedBatches = batches.filter((batch) => batch.districtName === district.name)

  return (
    <>
      <AppNavbar />
      <main className="bg-[#f4f0ea] px-5 py-8">
        <section className="mx-auto grid w-full max-w-[1180px] gap-6 lg:grid-cols-[0.86fr_1.14fr]">
          <header className="flex min-h-[520px] flex-col justify-between overflow-hidden rounded-[38px] bg-[#111111] p-6 text-white sm:p-8 lg:p-10">
            <div className="flex items-center justify-between text-[10px] font-black uppercase tracking-[0.18em] text-white/45">
              <p>Kecamatan</p>
              <p>{district.code}</p>
            </div>
            <div>
              <p className="mb-5 w-fit rounded-full bg-white/10 px-4 py-2 text-sm font-black text-white">Kecamatan</p>
              <h1 className="text-[52px] font-black leading-[0.9] tracking-[-0.07em] sm:text-[76px]">
                {district.name}
              </h1>
              <p className="mt-6 max-w-[560px] text-base font-medium leading-7 text-white/62 sm:text-lg sm:leading-8">
                Lihat batch yang tersedia untuk wilayah ini, lalu lanjutkan ke form pendaftaran jika kuota masih ada.
              </p>
            </div>
            <Link to="/booking" className="w-fit rounded-full bg-[#ed3833] px-6 py-4 font-black text-white transition hover:bg-[#d92f2a]">
              Mulai Pendaftaran
            </Link>
          </header>

          <div className="flex flex-col gap-6">
            <div className="relative min-h-[300px] overflow-hidden rounded-[38px] bg-[#ed3833]">
              <img src={district.imageUrl} className="absolute inset-0 h-full w-full object-cover mix-blend-multiply opacity-75" alt={district.name} />
              <div className="absolute inset-0 bg-[linear-gradient(180deg,_rgba(237,56,51,0.1),_rgba(17,17,17,0.72))]" />
              <p className="absolute bottom-6 left-6 right-6 text-[34px] font-black leading-[0.96] tracking-[-0.055em] text-white sm:text-[48px]">
                Batch aktif berdasarkan data wilayah.
              </p>
            </div>
            {displayedBatches.length > 0 ? (
              <section className="grid grid-cols-1 gap-5 md:grid-cols-2">
                {displayedBatches.map((batch) => (
                  <BatchCard key={batch.id} batch={batch} />
                ))}
              </section>
            ) : (
              <section className="rounded-[36px] border border-black/10 bg-white p-6 shadow-[0_24px_90px_rgba(0,0,0,0.06)] sm:p-8">
                <p className="text-sm font-black uppercase tracking-[0.16em] text-[#ed3833]">Belum ada batch</p>
                <h2 className="mt-4 text-[34px] font-black leading-[0.98] tracking-[-0.055em] text-[#111111]">
                  Kecamatan {district.name} belum memiliki batch aktif dari backend.
                </h2>
                <p className="mt-4 text-base font-medium leading-7 text-[#111111]/62">
                  Ini lebih aman daripada menampilkan batch dari kecamatan lain. Data akan muncul setelah endpoint backend mengembalikan batch untuk kecamatan ini.
                </p>
              </section>
            )}
          </div>
        </section>
      </main>
    </>
  )
}
