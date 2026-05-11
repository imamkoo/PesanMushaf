import { useState } from 'react'
import { createMidtransSnapToken, syncMidtransRegistrationStatus, type PaymentUiStatus } from '../lib/api'
import { openMidtransSnap } from '../lib/midtransSnap'

const SNAP_SYNC_ATTEMPTS = 14

const delay = (ms: number) => new Promise<void>((resolve) => setTimeout(resolve, ms))

type MidtransPayButtonProps = {
  registrationCode: string
  /** Dipanggil setiap kali sync Midtrans berhasil; gunakan untuk memperbarui UI tanpa menunggu GET /status. */
  onSyncedPaymentStatus?: (status: PaymentUiStatus) => void
  /** Dipanggil sekali setelah rangkaian sync selesai (untuk refresh penuh dari API). */
  onFlowEnd?: () => void
  disabled?: boolean
  label?: string
  className?: string
  variant?: 'primary' | 'light'
}

export function MidtransPayButton({
  registrationCode,
  onSyncedPaymentStatus,
  onFlowEnd,
  disabled = false,
  label = 'Bayar sekarang',
  className = '',
  variant = 'primary',
}: MidtransPayButtonProps) {
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')

  const baseClass =
    variant === 'primary'
      ? 'rounded-full bg-white px-5 py-4 text-center font-black text-[#ed3833] transition hover:bg-[#fff7f7] disabled:cursor-not-allowed disabled:opacity-60'
      : 'rounded-full bg-[#ed3833] px-5 py-4 text-center font-black text-white transition hover:bg-[#d92f2a] disabled:cursor-not-allowed disabled:opacity-60'

  const errorClass = variant === 'primary' ? 'text-[#111111]/80' : 'text-red-100'

  async function handlePay() {
    setError('')
    setBusy(true)

    try {
      const { data } = await createMidtransSnapToken(registrationCode)
      await openMidtransSnap(data.snap_token, data.client_key, data.is_production, {
        onClose: () => {
          void (async () => {
            try {
              /** Midtrans Status API sering telat vs popup Snap — poll sync dan terapkan payment_status dari respons. */
              for (let attempt = 0; attempt < SNAP_SYNC_ATTEMPTS; attempt++) {
                if (attempt > 0) {
                  await delay(500 + attempt * 400)
                }
                try {
                  const paymentStatus = await syncMidtransRegistrationStatus(registrationCode)
                  onSyncedPaymentStatus?.(paymentStatus)
                  if (paymentStatus === 'success' || paymentStatus === 'failed') {
                    break
                  }
                } catch (error) {
                  if (import.meta.env.DEV) {
                    console.warn('[Midtrans] sync-status gagal (akan dicoba lagi):', error)
                  }
                }
              }
            } finally {
              setBusy(false)
              onFlowEnd?.()
            }
          })()
        },
      })
    } catch (err) {
      setBusy(false)
      setError(err instanceof Error ? err.message : 'Tidak bisa memulai pembayaran.')
    }
  }

  return (
    <div className="grid gap-2">
      <button type="button" className={`${baseClass} ${className}`} disabled={disabled || busy} onClick={() => void handlePay()}>
        {busy ? 'Memuat pembayaran...' : label}
      </button>
      {error ? <p className={`text-sm font-bold ${errorClass}`}>{error}</p> : null}
    </div>
  )
}
