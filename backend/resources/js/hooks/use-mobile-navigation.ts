import { useCallback } from 'react';
import { useInteractionCleanup } from '@/hooks/use-interaction-recovery';

export type CleanupFn = () => void;

export function useMobileNavigation(): CleanupFn {
    const cleanup = useInteractionCleanup();

    return useCallback(() => {
        cleanup({ removeClosedArtifacts: true });
    }, [cleanup]);
}
