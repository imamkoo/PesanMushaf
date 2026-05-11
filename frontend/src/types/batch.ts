export type District = {
  id: number
  name: string
  slug: string
  totalBatches: string
  imageUrl: string
  href: string
  code?: string
}

export type Benefit = {
  title: string
  description: string
  iconUrl: string
}

export type Batch = {
  id: number
  slug: string
  name: string
  batchNumber: string
  educationLevel: string
  edition: 'reguler' | 'vip'
  basePrice: number
  districtName: string
  maxCapacity: number
  registrationsCount: number
  imageUrl: string
  href: string
  status?: 'available' | 'full' | 'closed'
  isFull?: boolean
}
