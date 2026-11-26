import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { csrfCookie, request } from '../../lib/api';
import type { MePayload } from '../../types';

interface AuthValue {
    user: MePayload | null;
    loading: boolean;
    login: (email: string, password: string, remember?: boolean) => Promise<MePayload>;
    register: (name: string, email: string, password: string, confirmation: string) => Promise<MePayload>;
    logout: () => Promise<void>;
    refresh: () => Promise<MePayload | null>;
    update: (data: unknown) => Promise<MePayload>;
}

const AuthContext = createContext<AuthValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
    const [user, setUser] = useState<MePayload | null>(null);
    const [loading, setLoading] = useState(true);

    const refresh = useCallback(async () => {
        try {
            const data = await request<MePayload>('get', '/me');
            setUser(data);
            return data;
        } catch (error) {
            if ((error as { status?: number }).status === 401) {
                setUser(null);
                return null;
            }
            throw error;
        }
    }, []);

    useEffect(() => {
        refresh().catch(() => setUser(null)).finally(() => setLoading(false));
    }, [refresh]);

    const value = useMemo<AuthValue>(() => ({
        user,
        loading,
        refresh,
        login: async (email, password, remember = false) => {
            await csrfCookie();
            const data = await request<MePayload>('post', '/login', { email, password, remember });
            setUser(data);
            return data;
        },
        register: async (name, email, password, confirmation) => {
            await csrfCookie();
            const data = await request<MePayload>('post', '/register', { name, email, password, password_confirmation: confirmation });
            setUser(data);
            return data;
        },
        logout: async () => {
            await csrfCookie();
            await request('post', '/logout');
            setUser(null);
        },
        update: async (data) => {
            const updated = await request<MePayload>('put', '/me', data);
            setUser(updated);
            return updated;
        },
    }), [user, loading, refresh]);

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthValue {
    const context = useContext(AuthContext);
    if (!context) throw new Error('AuthProvider tidak ditemukan.');
    return context;
}
