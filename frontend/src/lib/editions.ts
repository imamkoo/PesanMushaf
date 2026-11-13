export type Edition = 'kampung' | 'reguler' | 'vip'

const regulerBenefits = [
  'Surat Yasin Follow the Line',
  'Amplop berisi 1 halaman Al-Qur’an yang akan ditulis (A4 hitam-putih)',
  'Piagam Penghargaan dari Gubernur DKI Jakarta',
  'Pensil, penghapus, dan rautan',
]

export const editionBenefits: Record<Edition, string[]> = {
  kampung: [
    'Satu halaman Al-Qur’an yang akan ditulis (A3 berwarna)',
    'Pensil, penghapus, dan rautan',
    'Cover Al-Qur’an + finishing',
  ],
  reguler: regulerBenefits,
  vip: [...regulerBenefits, 'Papan jalan (clipboard)'],
}

export const editionLabels: Record<Edition, string> = {
  kampung: 'Mushaf Kampung',
  reguler: 'Reguler',
  vip: 'VIP',
}

export const defaultEditionPrices: Record<Edition, number> = {
  kampung: 20_000,
  reguler: 30_000,
  vip: 50_000,
}

export function isEdition(value: unknown): value is Edition {
  return value === 'kampung' || value === 'reguler' || value === 'vip'
}

export function toEdition(value: unknown): Edition {
  return isEdition(value) ? value : 'reguler'
}

export function getEditionLabel(value: string | null | undefined): string {
  if (!value) return '-'
  return isEdition(value) ? editionLabels[value] : value.toUpperCase()
}

export function getEditionBenefits(value: string | null | undefined): string[] {
  return isEdition(value) ? editionBenefits[value] : []
}
