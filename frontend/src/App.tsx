import { domAnimation, LazyMotion } from 'framer-motion'
import { Component, lazy, Suspense, type ErrorInfo, type ReactNode } from 'react'
import { Navigate, Route, Routes } from 'react-router-dom'
import { PwaUpdatePrompt } from './components/PwaUpdatePrompt'
import { ScrollProgress } from './components/motion/ScrollProgress'
import { HomePage } from './pages/HomePage'

const BookingPage = lazy(() =>
  import('./pages/BookingPage').then((module) => ({ default: module.BookingPage })),
)
const BookingFinishedPage = lazy(() =>
  import('./pages/BookingFinishedPage').then((module) => ({ default: module.BookingFinishedPage })),
)
const BookingDetailsPage = lazy(() =>
  import('./pages/BookingDetailsPage').then((module) => ({ default: module.BookingDetailsPage })),
)
const DistrictDetailsPage = lazy(() =>
  import('./pages/DistrictDetailsPage').then((module) => ({ default: module.DistrictDetailsPage })),
)
const BatchFullPage = lazy(() =>
  import('./pages/BatchFullPage').then((module) => ({ default: module.BatchFullPage })),
)
const BatchClosedPage = lazy(() =>
  import('./pages/BatchClosedPage').then((module) => ({ default: module.BatchClosedPage })),
)
const BatchDetailsPage = lazy(() =>
  import('./pages/BatchDetailsPage').then((module) => ({ default: module.BatchDetailsPage })),
)

function PageFallback() {
  return (
    <div className="flex min-h-dvh items-center justify-center bg-cream" role="status" aria-live="polite">
      <span className="h-8 w-8 rounded-full border-2 border-ink/15 border-t-brand motion-safe:animate-spin" />
      <span className="sr-only">Memuat halaman</span>
    </div>
  )
}

type AppErrorBoundaryState = {
  hasError: boolean
}

class AppErrorBoundary extends Component<{ children: ReactNode }, AppErrorBoundaryState> {
  state: AppErrorBoundaryState = { hasError: false }

  static getDerivedStateFromError(): AppErrorBoundaryState {
    return { hasError: true }
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    console.error('Route render failed', error, info.componentStack)
  }

  render() {
    if (!this.state.hasError) {
      return this.props.children
    }

    return (
      <main className="flex min-h-dvh items-center justify-center bg-cream px-6 text-center">
        <div className="max-w-sm">
          <p className="text-sm font-black uppercase tracking-[0.18em] text-brand">Terjadi kendala</p>
          <h1 className="mt-3 text-3xl font-black tracking-[-0.04em] text-ink">Halaman gagal dimuat.</h1>
          <p className="mt-3 text-sm font-medium leading-6 text-ink/60">
            Periksa koneksi Anda, lalu muat ulang halaman ini.
          </p>
          <button
            type="button"
            onClick={() => window.location.reload()}
            className="mt-6 rounded-full bg-brand px-6 py-3 text-sm font-black text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand/30"
          >
            Muat ulang
          </button>
        </div>
      </main>
    )
  }
}

function App() {
  return (
    <LazyMotion features={domAnimation} strict>
      <ScrollProgress />
      <AppErrorBoundary>
        <Suspense fallback={<PageFallback />}>
          <Routes>
            <Route path="/" element={<HomePage />} />
            <Route path="/booking" element={<BookingPage />} />
            <Route path="/booking/finished" element={<BookingFinishedPage />} />
            <Route path="/booking/details" element={<BookingDetailsPage />} />
            <Route path="/districts" element={<DistrictDetailsPage />} />
            <Route path="/districts/:slug" element={<DistrictDetailsPage />} />
            <Route path="/batches/full" element={<BatchFullPage />} />
            <Route path="/batches/closed" element={<BatchClosedPage />} />
            <Route path="/batches/:slug" element={<BatchDetailsPage />} />
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </Suspense>
      </AppErrorBoundary>
      <PwaUpdatePrompt />
    </LazyMotion>
  )
}

export default App
