import { useEffect, useState } from 'react'
import { useRegisterSW } from 'virtual:pwa-register/react'

const OFFLINE_TOAST_DURATION_MS = 4000

export function PwaUpdatePrompt() {
  const {
    needRefresh: [needRefresh, setNeedRefresh],
    offlineReady: [offlineReady, setOfflineReady],
    updateServiceWorker,
  } = useRegisterSW({
    onRegisteredSW(swUrl) {
      if (import.meta.env.DEV) {
        console.info('[PWA] SW registered at', swUrl)
      }
    },
    onRegisterError(error) {
      if (import.meta.env.DEV) {
        console.warn('[PWA] SW registration failed:', error)
      }
    },
  })

  const [refreshing, setRefreshing] = useState(false)

  useEffect(() => {
    if (!offlineReady) {
      return
    }
    const timer = window.setTimeout(() => {
      setOfflineReady(false)
    }, OFFLINE_TOAST_DURATION_MS)
    return () => window.clearTimeout(timer)
  }, [offlineReady, setOfflineReady])

  if (!needRefresh && !offlineReady) {
    return null
  }

  return (
    <div
      role="status"
      aria-live="polite"
      className="pointer-events-none fixed inset-x-0 bottom-4 z-[100] flex justify-center px-4 sm:bottom-6"
    >
      <div className="pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-2xl border border-black/10 bg-[#111111] px-4 py-3 text-white shadow-[0_20px_60px_rgba(0,0,0,0.25)]">
        <div className="mt-0.5 h-2 w-2 shrink-0 rounded-full bg-[#ed3833]" aria-hidden="true" />
        <div className="flex-1 text-sm leading-5">
          {needRefresh ? (
            <>
              <p className="font-black">Versi baru tersedia</p>
              <p className="text-white/65">Muat ulang untuk menggunakan pembaruan.</p>
            </>
          ) : (
            <>
              <p className="font-black">Siap dipakai offline</p>
              <p className="text-white/65">Konten utama sudah tersimpan di perangkat Anda.</p>
            </>
          )}
        </div>
        {needRefresh ? (
          <div className="flex shrink-0 items-center gap-2">
            <button
              type="button"
              onClick={() => setNeedRefresh(false)}
              className="rounded-full px-3 py-1.5 text-xs font-bold text-white/70 transition hover:text-white"
            >
              Nanti
            </button>
            <button
              type="button"
              disabled={refreshing}
              onClick={() => {
                setRefreshing(true)
                void updateServiceWorker(true)
              }}
              className="rounded-full bg-[#ed3833] px-3 py-1.5 text-xs font-black text-white transition hover:bg-[#d92f2a] disabled:cursor-not-allowed disabled:opacity-60"
            >
              {refreshing ? 'Memperbarui…' : 'Perbarui'}
            </button>
          </div>
        ) : (
          <button
            type="button"
            onClick={() => setOfflineReady(false)}
            aria-label="Tutup pesan"
            className="ml-2 shrink-0 rounded-full px-2 py-1 text-xs font-bold text-white/65 transition hover:text-white"
          >
            ×
          </button>
        )}
      </div>
    </div>
  )
}
