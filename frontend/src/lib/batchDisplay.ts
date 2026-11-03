import type { Batch } from '../types/batch'

export const GLOBAL_BATCH_LOCATION_LABEL = 'VIP Global · Lintas Kecamatan'

export function getBatchLocationLabel(batch: Pick<Batch, 'isGlobal' | 'districtName'>): string {
  return batch.isGlobal ? GLOBAL_BATCH_LOCATION_LABEL : batch.districtName
}
