import { useEffect, useMemo, useState } from 'react'
import { photos } from '../assets/projectAssets'
import { getBatches, getDistricts, getPriceCategories, type ApiBatch, type ApiDistrict, type ApiPriceCategory } from '../lib/api'
import { getBasePrice } from '../lib/payment'
import type { Batch, District } from '../types/batch'

const defaultPriceBySlug: Record<string, number> = {
  reguler: 12_000,
  vip: 50_000,
}

export type EducationLevelStat = {
  level: string
  capacity: number
  filled: number
  filledPct: number
}

export type Hut500PublicStats = {
  totalCapacity: number
  totalRegistered: number
  totalRemaining: number
  fillPct: number
  fullBatches: number
  byLevel: EducationLevelStat[]
}

type Hut500DataState = {
  districts: District[]
  batches: Batch[]
  priceCategories: ApiPriceCategory[]
  priceBySlug: Record<string, number>
  stats: Hut500PublicStats
  isLoading: boolean
  error: string
}

const orderedLevels = ['SD', 'SMP', 'SMA', 'UMUM'] as const

function computeStats(batches: Batch[]): Hut500PublicStats {
  const totalCapacity = batches.reduce((sum, batch) => sum + batch.maxCapacity, 0)
  const totalRegistered = batches.reduce((sum, batch) => sum + batch.registrationsCount, 0)
  const totalRemaining = Math.max(totalCapacity - totalRegistered, 0)
  const fillPct = totalCapacity > 0 ? Math.min(100, Math.round((totalRegistered / totalCapacity) * 100)) : 0
  const fullBatches = batches.filter((batch) => batch.isFull || batch.status === 'full').length

  const byLevelMap = new Map<string, { capacity: number; filled: number }>()
  for (const batch of batches) {
    const key = batch.educationLevel || 'Lainnya'
    const current = byLevelMap.get(key) ?? { capacity: 0, filled: 0 }
    current.capacity += batch.maxCapacity
    current.filled += batch.registrationsCount
    byLevelMap.set(key, current)
  }

  const orderedKeys: string[] = [
    ...orderedLevels.filter((level) => byLevelMap.has(level)),
    ...Array.from(byLevelMap.keys()).filter((key) => !orderedLevels.includes(key as (typeof orderedLevels)[number])),
  ]

  const byLevel: EducationLevelStat[] = orderedKeys.map((level) => {
    const data = byLevelMap.get(level) ?? { capacity: 0, filled: 0 }
    const pct = data.capacity > 0 ? Math.min(100, Math.round((data.filled / data.capacity) * 100)) : 0

    return {
      level,
      capacity: data.capacity,
      filled: data.filled,
      filledPct: pct,
    }
  })

  return {
    totalCapacity,
    totalRegistered,
    totalRemaining,
    fillPct,
    fullBatches,
    byLevel,
  }
}

const emptyStats: Hut500PublicStats = {
  totalCapacity: 0,
  totalRegistered: 0,
  totalRemaining: 0,
  fillPct: 0,
  fullBatches: 0,
  byLevel: [],
}

function resolveImage(photo: string | null | undefined, fallback: string) {
  if (!photo) {
    return fallback
  }

  if (photo.startsWith('http') || photo.startsWith('/')) {
    return photo
  }

  return `/storage/${photo}`
}

function inferEdition(batch: ApiBatch): 'reguler' | 'vip' {
  return /\bvip\b/i.test(batch.name) ? 'vip' : 'reguler'
}

function mapDistrict(district: ApiDistrict, index: number): District {
  return {
    id: district.id,
    name: district.name,
    slug: district.slug,
    code: district.code ?? undefined,
    totalBatches: `${district.batches_count ?? 0} Gelombang`,
    imageUrl: resolveImage(district.photo, photos.districts[index % photos.districts.length]),
    href: `/districts/${district.slug}`,
  }
}

function mapBatch(batch: ApiBatch, index: number, priceBySlug: Record<string, number>): Batch {
  const edition = inferEdition(batch)
  const registrationsCount = batch.registrations_count ?? 0
  const basePrice = priceBySlug[edition] ?? getBasePrice(edition)

  return {
    id: batch.id,
    slug: batch.slug,
    name: batch.name,
    batchNumber: String(batch.batch_number),
    educationLevel: batch.education_level,
    edition,
    basePrice,
    districtName: batch.district?.name ?? 'Tanpa Kecamatan',
    maxCapacity: batch.max_capacity,
    registrationsCount,
    imageUrl: photos.batches[index % photos.batches.length],
    href: `/batches/${batch.slug}`,
    status: batch.is_full ? 'full' : 'available',
    isFull: batch.is_full,
  }
}

export function useHut500Data(): Hut500DataState {
  const [apiDistricts, setApiDistricts] = useState<District[]>([])
  const [apiBatches, setApiBatches] = useState<Batch[]>([])
  const [priceCategories, setPriceCategories] = useState<ApiPriceCategory[]>([])
  const [priceBySlug, setPriceBySlug] = useState<Record<string, number>>(defaultPriceBySlug)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let isMounted = true

    async function loadData() {
      setIsLoading(true)
      setError('')

      try {
        const [districtResponse, batchResponse, categoryResult] = await Promise.all([
          getDistricts(),
          getBatches(),
          getPriceCategories().catch(() => []),
        ])

        if (!isMounted) {
          return
        }

        const nextPriceMap: Record<string, number> = { ...defaultPriceBySlug }
        for (const c of categoryResult) {
          nextPriceMap[c.slug] = c.amount
        }

        setPriceBySlug(nextPriceMap)
        setPriceCategories(categoryResult)
        setApiDistricts(districtResponse.map(mapDistrict))
        setApiBatches(batchResponse.map((batch, index) => mapBatch(batch, index, nextPriceMap)))
      } catch (loadError) {
        if (!isMounted) {
          return
        }

        setError(loadError instanceof Error ? loadError.message : 'Data tidak bisa dimuat.')
        setApiDistricts([])
        setApiBatches([])
        setPriceCategories([])
        setPriceBySlug(defaultPriceBySlug)
      } finally {
        if (isMounted) {
          setIsLoading(false)
        }
      }
    }

    void loadData()

    return () => {
      isMounted = false
    }
  }, [])

  return useMemo(
    () => ({
      districts: apiDistricts,
      batches: apiBatches,
      priceCategories,
      priceBySlug,
      stats: apiBatches.length > 0 ? computeStats(apiBatches) : emptyStats,
      isLoading,
      error,
    }),
    [apiBatches, apiDistricts, error, isLoading, priceBySlug, priceCategories],
  )
}
