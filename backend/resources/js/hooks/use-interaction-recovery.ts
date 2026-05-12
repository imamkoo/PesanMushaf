import { router } from '@inertiajs/react';
import { useCallback, useEffect } from 'react';
import {
    recoverInteractionState,
    scheduleInteractionRecovery,
} from '@/lib/interaction-recovery';
import type { InteractionRecoveryOptions } from '@/lib/interaction-recovery';

export type InteractionCleanupFn = (
    options?: InteractionRecoveryOptions,
) => void;

export function useInteractionCleanup(): InteractionCleanupFn {
    return useCallback((options?: InteractionRecoveryOptions) => {
        recoverInteractionState({
            removeClosedArtifacts: true,
            ...options,
        });
    }, []);
}

export function useInteractionRecovery(): InteractionCleanupFn {
    const cleanup = useInteractionCleanup();

    useEffect(() => {
        const cancelInitialRecovery = scheduleInteractionRecovery({
            removeClosedArtifacts: true,
        });

        const releaseLocksBeforeNavigation = () => {
            cleanup({ removeClosedArtifacts: false });
        };

        const settleAfterNavigation = () => {
            scheduleInteractionRecovery({ removeClosedArtifacts: true });
        };

        const handlePageShow = () => {
            cleanup({ removeClosedArtifacts: true });
        };

        const handleVisibilityChange = () => {
            if (document.visibilityState === 'visible') {
                cleanup({ removeClosedArtifacts: true });
            }
        };

        const stopNavigationStart = router.on(
            'start',
            releaseLocksBeforeNavigation,
        );
        const stopNavigationFinish = router.on('finish', settleAfterNavigation);

        window.addEventListener('pageshow', handlePageShow);
        window.addEventListener('focus', handlePageShow);
        document.addEventListener('visibilitychange', handleVisibilityChange);

        return () => {
            cancelInitialRecovery();
            stopNavigationStart();
            stopNavigationFinish();
            window.removeEventListener('pageshow', handlePageShow);
            window.removeEventListener('focus', handlePageShow);
            document.removeEventListener(
                'visibilitychange',
                handleVisibilityChange,
            );
            cleanup({ removeClosedArtifacts: true });
        };
    }, [cleanup]);

    return cleanup;
}
