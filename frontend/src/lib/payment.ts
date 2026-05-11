export function getBasePrice(edition: 'reguler' | 'vip') {
  return edition === 'vip' ? 50000 : 12000
}

export function formatRupiah(value: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value)
}
