import { defaultEditionPrices, type Edition } from './editions'

export function getBasePrice(edition: Edition) {
  return defaultEditionPrices[edition]
}

export function formatRupiah(value: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value)
}
