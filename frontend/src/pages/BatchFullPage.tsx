import { Link } from 'react-router-dom'
import { AppNavbar } from '../components/AppNavbar'
import { useHut500Data } from '../hooks/useHut500Data'

export function BatchFullPage() {
  const { batches, isLoading } = useHut500Data()
  const fullBatch = batches.find((batch) => batch.isFull)

  return (
    <>
      <AppNavbar />
      <main className="min-h-[calc(100vh-73px)] bg-[#f4f0ea] px-5 py-8">
        <section className="mx-auto flex min-h-[620px] w-full max-w-[980px] flex-col justify-between rounded-[38px] bg-[#111111] p-6 text-white sm:p-8 lg:p-10">
          <div className="flex items-center justify-between text-[10px] font-black uppercase tracking-[0.18em] text-white/45">
            <p>Batch</p>
            <p>Penuh</p>
          </div>
          <div>
            <p className="mb-5 w-fit rounded-full bg-[#ed3833] px-4 py-2 text-sm font-black text-white">Kuota habis</p>
            <h1 className="max-w-[760px] text-[48px] font-black leading-[0.94] tracking-[-0.06em] sm:text-[76px]">
              {fullBatch ? `${fullBatch.name} sudah penuh.` : isLoading ? 'Memuat batch dari backend.' : 'Belum ada batch penuh.'}
            </h1>
            <p className="mt-6 max-w-[620px] text-base font-medium leading-7 text-white/62 sm:text-lg sm:leading-8">
              {fullBatch ? `Kuota ${fullBatch.maxCapacity} peserta telah terpenuhi.` : 'Data ini mengikuti endpoint /api/batches.'} Pilih batch aktif lain agar proses pendaftaran tetap lanjut.
            </p>
          </div>
          <Link to="/#batches" className="w-fit rounded-full bg-[#ed3833] px-6 py-4 font-black text-white transition hover:bg-[#d92f2a]">
            Lihat Batch Aktif
          </Link>
        </section>
      </main>
    </>
  )
}
