import axios, { AxiosError } from 'axios';

export class ApiError extends Error {
    status: number;
    errors: Record<string, string[]>;

    constructor(message: string, status = 500, errors: Record<string, string[]> = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
    }
}

const client = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
});

client.interceptors.request.use((config) => {
    config.headers.set('X-Request-ID', crypto.randomUUID());
    return config;
});

client.interceptors.response.use((response) => response, (error: AxiosError<{ message?: string; errors?: Record<string, string[]> }>) => {
    const response = error.response;
    const firstError = response?.data?.errors ? Object.values(response.data.errors)[0]?.[0] : null;
    throw new ApiError(firstError || response?.data?.message || 'Permintaan tidak dapat diproses. Periksa koneksi lalu coba lagi.', response?.status ?? 0, response?.data?.errors ?? {});
});

export async function csrfCookie(): Promise<void> {
    await axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}

interface ApiEnvelope<T> {
    data: T;
    message?: string;
    errors?: Record<string, string[]> | null;
    meta?: unknown;
}

export async function request<T>(method: 'get' | 'post' | 'put' | 'patch' | 'delete', url: string, data?: unknown): Promise<T> {
    const response = await client.request<ApiEnvelope<T>>({ method, url, data });
    return response.data.data;
}

export function errorMessage(error: unknown): string {
    return error instanceof Error ? error.message : 'Terjadi kesalahan yang tidak diketahui.';
}

export function fieldErrors(error: unknown): Record<string, string[]> {
    return error instanceof ApiError ? error.errors : {};
}

export function formatDate(value?: string | null): string {
    if (!value) return '—';
    return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(value));
}

export function classNames(...values: Array<string | false | null | undefined>): string {
    return values.filter(Boolean).join(' ');
}
