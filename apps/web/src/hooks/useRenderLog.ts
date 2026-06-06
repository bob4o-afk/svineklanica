import { useEffect } from 'react';
import { logger } from '@/lib/logger';

/** Logs a page/route mount (dev-only via logger). Put at the top of each page body. */
export function useRenderLog(name: string): void {
  useEffect(() => {
    logger.debug(`render: ${name}`);
  }, [name]);
}
