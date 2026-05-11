import { Navigate, Route, Routes } from 'react-router-dom'
import { PwaUpdatePrompt } from './components/PwaUpdatePrompt'
import { BookingDetailsPage } from './pages/BookingDetailsPage'
import { BookingFinishedPage } from './pages/BookingFinishedPage'
import { BookingPage } from './pages/BookingPage'
import { DistrictDetailsPage } from './pages/DistrictDetailsPage'
import { HomePage } from './pages/HomePage'
import { BatchClosedPage } from './pages/BatchClosedPage'
import { BatchDetailsPage } from './pages/BatchDetailsPage'
import { BatchFullPage } from './pages/BatchFullPage'

function App() {
  return (
    <>
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
      <PwaUpdatePrompt />
    </>
  )
}

export default App
