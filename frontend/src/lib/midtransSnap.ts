const SCRIPT_ATTR = 'data-hut500-midtrans-snap'

const SNAP_SANDBOX = 'https://app.sandbox.midtrans.com/snap/snap.js'
const SNAP_PRODUCTION = 'https://app.midtrans.com/snap/snap.js'

export type MidtransSnapPayHandlers = {
  onSuccess?: (result: unknown) => void
  onPending?: (result: unknown) => void
  onError?: (result: unknown) => void
  onClose?: () => void
}

declare global {
  interface Window {
    snap?: {
      pay: (token: string, options: Record<string, unknown>) => void
    }
  }
}

export function loadMidtransSnapScript(clientKey: string, isProduction: boolean): Promise<void> {
  const src = isProduction ? SNAP_PRODUCTION : SNAP_SANDBOX
  const existing = document.querySelector<HTMLScriptElement>(`script[${SCRIPT_ATTR}]`)

  if (existing) {
    const existingKey = existing.getAttribute('data-client-key')
    const sameSrc = existing.src === src || existing.getAttribute('src') === src

    if (existingKey === clientKey && sameSrc) {
      return Promise.resolve()
    }

    existing.remove()
    window.snap = undefined
  }

  return new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = src
    script.async = true
    script.setAttribute('data-client-key', clientKey)
    script.setAttribute(SCRIPT_ATTR, '1')
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('Gagal memuat halaman pembayaran. Periksa koneksi atau pengaturan pemblokir iklan.'))
    document.body.appendChild(script)
  })
}

/**
 * Membuka popup Snap. Callback Midtrans kadang memanggil lebih dari satu event; `onClose` hanya dipanggil sekali di akhir alur.
 */
export function openMidtransSnap(
  snapToken: string,
  clientKey: string,
  isProduction: boolean,
  handlers: MidtransSnapPayHandlers = {},
): Promise<void> {
  return loadMidtransSnapScript(clientKey, isProduction).then(() => {
    if (!window.snap?.pay) {
      throw new Error('Halaman pembayaran tidak tersedia. Muat ulang lalu coba lagi.')
    }

    let settled = false
    const settle = () => {
      if (settled) {
        return
      }
      settled = true
      handlers.onClose?.()
    }

    window.snap.pay(snapToken, {
      onSuccess: (result: unknown) => {
        handlers.onSuccess?.(result)
        settle()
      },
      onPending: (result: unknown) => {
        handlers.onPending?.(result)
        settle()
      },
      onError: (result: unknown) => {
        handlers.onError?.(result)
        settle()
      },
      onClose: () => {
        settle()
      },
    })
  })
}
