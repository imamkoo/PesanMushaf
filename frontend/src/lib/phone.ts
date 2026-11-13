/** Samakan dengan backend IndonesianPhone::normalizeWhatsAppTarget: 08…, +62…, 8… → 62… */
export function normalizeIndonesianPhone(raw: string): string {
  const digits = raw.replace(/\D+/g, '')

  if (digits.startsWith('62')) return digits
  if (digits.startsWith('0')) return `62${digits.slice(1)}`
  if (digits.startsWith('8') && digits.length >= 9) return `62${digits}`

  return digits
}

export function isValidIndonesianPhone(raw: string): boolean {
  return /^628\d{8,12}$/.test(normalizeIndonesianPhone(raw))
}
