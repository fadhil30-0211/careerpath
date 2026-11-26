import { useEffect, useMemo, useState } from 'react';
import { request } from '../../lib/api';
import type { OccupationCard, Page } from '../../types';

export function useOccupationQuery(params: URLSearchParams) {
    const key = params.toString();
    const [data, setData] = useState<Page<OccupationCard>>({ items: [], meta: { current_page: 1, last_page: 1, total: 0 } });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [revision, setRevision] = useState(0);

    useEffect(() => {
        let active = true;
        setLoading(true);
        setError(null);
        request<Page<OccupationCard>>('get', `/public/occupations?${key}`)
            .then((result) => { if (active) setData(result); })
            .catch((cause) => { if (active) setError(cause instanceof Error ? cause.message : 'Data profesi tidak dapat dimuat.'); })
            .finally(() => { if (active) setLoading(false); });
        return () => { active = false; };
    }, [key, revision]);

    return useMemo(() => ({ ...data, loading, error, reload: () => setRevision((value) => value + 1) }), [data, loading, error]);
}
