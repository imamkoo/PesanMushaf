import { Link } from 'react-router-dom'
import { AppNavbar } from '../components/AppNavbar'

export function BatchClosedPage() {
  return (
    <>
      <AppNavbar />
      <main className="min-h-[calc(100vh-73px)] bg-[#f4f0ea] px-5 py-8">
        <section className="mx-auto flex min-h-[620px] w-full max-w-[980px] flex-col justify-between rounded-[38px] bg-white p-6 shadow-[0_24px_90px_rgba(0,0,0,0.06)] sm:p-8 lg:p-10">
          <div className="flex items-center justify-between text-[10px] font-black uppercase tracking-[0.18em] text-[#111111]/40">
            <p>Batch</p>
            <p>Ditutup</p>
          </div>
          <div>
            <p className="mb-5 w-fit rounded-full bg-[#fff7f7] px-4 py-2 text-sm font-black text-[#ed3833]">Tidak aktif</p>
            <h1 className="max-w-[760px] text-[48px] font-black leading-[0.94] tracking-[-0.06em] text-[#111111] sm:text-[76px]">
              Pendaftaran batch ini sedang ditutup.
            </h1>
            <p className="mt-6 max-w-[620px] text-base font-medium leading-7 text-[#111111]/62 sm:text-lg sm:leading-8">
              Admin dapat menutup batch ketika periode selesai atau kuota sedang diverifikasi. Kembali ke daftar batch aktif untuk memilih opsi lain.
            </p>
          </div>
          <Link to="/#batches" className="w-fit rounded-full bg-[#ed3833] px-6 py-4 font-black text-white transition hover:bg-[#d92f2a]">
            Kembali ke Batch Aktif
          </Link>
        </section>
      </main>
    </>
  )
}
