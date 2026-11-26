import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';
import { request } from '../../lib/api';
import type { CatalogMeta, Category, Skill } from '../../types';

interface CatalogValue {
    meta: CatalogMeta | null;
    categories: Category[];
    skills: Skill[];
    loading: boolean;
    error: string | null;
    reload: () => Promise<void>;
}

const CatalogContext = createContext<CatalogValue | null>(null);

export function CatalogProvider({ children }: { children: ReactNode }) {
    const [meta, setMeta] = useState<CatalogMeta | null>(null);
    const [categories, setCategories] = useState<Category[]>([]);
    const [skills, setSkills] = useState<Skill[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    async function reload() {
        setLoading(true);
        setError(null);
        try {
            const [metaData, categoryData, skillData] = await Promise.all([
                request<CatalogMeta>('get', '/public/meta'),
                request<Category[]>('get', '/public/categories'),
                request<Skill[]>('get', '/public/skills'),
            ]);
            setMeta(metaData);
            setCategories(categoryData);
            setSkills(skillData);
        } catch (cause) {
            setError(cause instanceof Error ? cause.message : 'Katalog tidak dapat dimuat.');
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => { void reload(); }, []);

    return <CatalogContext.Provider value={{ meta, categories, skills, loading, error, reload }}>{children}</CatalogContext.Provider>;
}

export function useCatalog(): CatalogValue {
    const context = useContext(CatalogContext);
    if (!context) throw new Error('CatalogProvider tidak ditemukan.');
    return context;
}
