import type { PaymentUiStatus } from './api'

type PaymentStatusUiConfig = {
  label: string
  eyebrow: string
  badgeClass: string
  asideClass: string
  iconClass: string
  bodyText: string
  actionLabel?: string
  actionNote: string
  staticActionClass?: string
}

export const paymentStatusUi: Record<PaymentUiStatus, PaymentStatusUiConfig> = {
  pending: {
    label: 'Menunggu pembayaran',
    eyebrow: 'Selesaikan pembayaran',
    badgeClass: 'bg-[#fff1f0] text-[#ed3833] ring-1 ring-[#ed3833]/12',
    asideClass:
      'border border-white/10 bg-gradient-to-br from-[#ef4444] via-[#ea3b35] to-[#c81e1a] shadow-[0_22px_60px_rgba(237,56,51,0.18)]',
    iconClass: 'bg-[#ed3833] shadow-[0_14px_36px_rgba(237,56,51,0.24)]',
    bodyText:
      'Nominal ini perlu dibayar agar pendaftaran masuk tahap verifikasi panitia. Gunakan tombol di bawah untuk menyelesaikan pembayaran secara online.',
    actionNote: 'Status akan diperiksa otomatis setiap 15 detik selama halaman ini terbuka.',
  },
  success: {
    label: 'Lunas',
    eyebrow: 'Pembayaran terkonfirmasi',
    badgeClass: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
    asideClass:
      'border border-emerald-400/18 bg-gradient-to-br from-emerald-500 via-emerald-600 to-emerald-700 shadow-[0_22px_60px_rgba(5,150,105,0.2)]',
    iconClass: 'bg-emerald-600 shadow-[0_14px_36px_rgba(5,150,105,0.24)]',
    bodyText: 'Pembayaran sudah tercatat. Simpan kode ini dan gunakan halaman lacak untuk memeriksa detail kapan saja.',
    actionLabel: 'Pembayaran Berhasil',
    actionNote: 'Pembayaran Anda sudah dikonfirmasi dan data pendaftaran siap diproses panitia.',
    staticActionClass:
      'rounded-full border border-emerald-300/35 bg-emerald-500 px-5 py-4 text-center font-black !text-white shadow-[0_14px_36px_rgba(16,185,129,0.25)]',
  },
  failed: {
    label: 'Batal',
    eyebrow: 'Pembayaran belum berhasil',
    badgeClass: 'bg-zinc-100 text-zinc-700 ring-1 ring-zinc-200',
    asideClass:
      'border border-zinc-400/12 bg-gradient-to-br from-zinc-700 via-zinc-700 to-zinc-800 shadow-[0_20px_60px_rgba(63,63,70,0.18)]',
    iconClass: 'bg-zinc-700 shadow-[0_14px_36px_rgba(63,63,70,0.22)]',
    bodyText:
      'Transaksi dibatalkan atau belum berhasil. Anda bisa mencoba lagi atau menghubungi panitia bila sudah melakukan transfer.',
    actionLabel: 'Pembayaran Belum Berhasil',
    actionNote: 'Cek ulang status beberapa saat lagi atau hubungi panitia jika pembayaran sudah dilakukan.',
    staticActionClass: 'rounded-full border border-white/14 bg-white/10 px-5 py-4 text-center font-black !text-white',
  },
}
