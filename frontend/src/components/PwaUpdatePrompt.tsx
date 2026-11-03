import { useEffect, useState } from 'react'
import { useRegisterSW } from 'virtual:pwa-register/react'

const OFFLINE_TOAST_DURATION_MS = 4000
const UPDATE_SLOW_AFTER_MS = 5000
const UPDATE_STALLED_AFTER_MS = 15000

type UpdateStage = 'idle' | 'updating' | 'slow' | 'stalled' | 'error'

const UPDATE_COPY: Record<UpdateStage, { title: string; description: string }> = {
  idle: {
    title: 'Versi baru tersedia',
    description: 'Muat ulang untuk menggunakan pembaruan.',
  },
  updating: {
    title: 'Memperbarui aplikasi',
    description: 'Mengunduh versi terbaru. Halaman akan dimuat ulang otomatis.',
  },
  slow: {
    title: 'Masih memperbarui',
    description: 'Koneksi lambat. Proses ini bisa memakan waktu lebih lama.',
  },
  stalled: {
    title: 'Pembaruan belum selesai',
    description: 'Muat ulang halaman untuk menyelesaikan pembaruan.',
  },
  error: {
    title: 'Pembaruan gagal dimulai',
    description: 'Coba lagi, atau tutup pesan ini dan muat ulang nanti.',
  },
}

function Spinner() {
  return (
    <svg
      aria-hidden="true"
      className="h-3.5 w-3.5 animate-spin motion-reduce:animate-none"
      viewBox="0 0 24 24"
      fill="none"
    >
      <circle cx="12" cy="12" r="9" stroke="currentColor" strokeWidth="3" opacity="0.3" />
      <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
    </svg>
  )
}

export function PwaUpdatePrompt() {
  const [updateStage, setUpdateStage] = useState<UpdateStage>('idle')
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
    onNeedRefresh() {
      setUpdateStage('idle')
    },
  })

  const isUpdating = updateStage === 'updating' || updateStage === 'slow'
  const isStalled = updateStage === 'stalled'
  const hasError = updateStage === 'error'
  const updateCopy = UPDATE_COPY[updateStage]

  useEffect(() => {
    if (!offlineReady) {
      return
    }
    const timer = window.setTimeout(() => {
      setOfflineReady(false)
    }, OFFLINE_TOAST_DURATION_MS)
    return () => window.clearTimeout(timer)
  }, [offlineReady, setOfflineReady])

  useEffect(() => {
    if (!isUpdating) {
      return
    }

    const slowTimer = window.setTimeout(() => {
      setUpdateStage((stage) => (stage === 'updating' ? 'slow' : stage))
    }, UPDATE_SLOW_AFTER_MS)
    const stalledTimer = window.setTimeout(() => {
      setUpdateStage((stage) => (stage === 'updating' || stage === 'slow' ? 'stalled' : stage))
    }, UPDATE_STALLED_AFTER_MS)

    return () => {
      window.clearTimeout(slowTimer)
      window.clearTimeout(stalledTimer)
    }
  }, [isUpdating])

  if (!needRefresh && !offlineReady) {
    return null
  }

  const startUpdate = () => {
    setUpdateStage('updating')
    void updateServiceWorker(true).catch(() => {
      setUpdateStage('error')
    })
  }

  const dismiss = () => {
    setNeedRefresh(false)
    setUpdateStage('idle')
  }

  const handlePrimaryAction = () => {
    if (isStalled) {
      window.location.reload()
      return
    }
    startUpdate()
  }

  return (
    <div
      role="status"
      aria-live="polite"
      className="pointer-events-none fixed inset-x-0 bottom-4 z-[100] flex justify-center px-4 sm:bottom-6"
    >
      <div
        aria-busy={isUpdating}
        className="pointer-events-auto relative w-full max-w-md overflow-hidden rounded-2xl border border-black/10 bg-[#111111] px-4 py-3 text-white shadow-[0_20px_60px_rgba(0,0,0,0.25)] sm:max-w-lg"
      >
        <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start">
          <div className="flex min-w-0 items-start gap-3">
            <div
              className={`mt-0.5 h-2 w-2 shrink-0 rounded-full bg-[#ed3833] ${
                isUpdating ? 'motion-safe:animate-pulse' : ''
              }`}
              aria-hidden="true"
            />
            <div className="min-w-0 flex-1 text-sm leading-5">
              {needRefresh ? (
                <>
                  <p className="font-black">{updateCopy.title}</p>
                  <p className="text-white/65">{updateCopy.description}</p>
                </>
              ) : (
                <>
                  <p className="font-black">Siap dipakai offline</p>
                  <p className="text-white/65">Konten utama sudah tersimpan di perangkat Anda.</p>
                </>
              )}
            </div>
          </div>
          {needRefresh ? (
            <div className="flex items-center justify-end gap-2 sm:justify-self-end">
              <button
                type="button"
                onClick={dismiss}
                disabled={isUpdating}
                className="rounded-full px-3 py-1.5 text-xs font-bold text-white/70 transition hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
              >
                {isStalled || hasError ? 'Tutup' : 'Nanti'}
              </button>
              <button
                type="button"
                disabled={isUpdating}
                aria-busy={isUpdating}
                onClick={handlePrimaryAction}
                className="inline-flex items-center gap-1.5 rounded-full bg-[#ed3833] px-3 py-1.5 text-xs font-black text-white transition hover:bg-[#d92f2a] disabled:cursor-wait disabled:opacity-70"
              >
                {isUpdating ? <Spinner /> : null}
                {isUpdating
                  ? 'Memperbarui…'
                  : isStalled
                    ? 'Muat ulang'
                    : hasError
                      ? 'Coba lagi'
                      : 'Perbarui'}
              </button>
            </div>
          ) : null}
        </div>
        {!needRefresh ? (
          <button
            type="button"
            onClick={() => setOfflineReady(false)}
            aria-label="Tutup pesan"
            className="absolute right-3 top-3 rounded-full px-2 py-1 text-xs font-bold text-white/65 transition hover:text-white"
          >
            ×
          </button>
        ) : null}
        {isUpdating ? (
          <div
            aria-hidden="true"
            className="absolute inset-x-0 bottom-0 h-0.5 overflow-hidden bg-white/10"
          >
            <span className="pwa-update-progress block h-full w-1/3 rounded-full bg-[#ed3833]" />
          </div>
        ) : null}
      </div>
    </div>
  )
}
