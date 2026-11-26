export type Role = 'user' | 'admin';
export type PublicationStatus = 'draft' | 'review' | 'published' | 'archived';
export type SkillStatus = 'unknown' | 'possessed' | 'not_yet';
export type GapStatus = 'aligned' | 'needs_improvement' | 'not_yet_possessed' | 'unknown';

export interface PageMeta {
    current_page: number;
    last_page: number;
    per_page?: number;
    total: number;
}

export interface Page<T> {
    items: T[];
    meta: PageMeta;
}

export interface CatalogMeta {
    activities: Array<{ value: string; label: string; description: string }>;
    education_levels: string[];
    age_ranges: string[];
    experience_types: Record<string, string>;
    skill_levels: Record<string, string>;
}

export interface GroupNode { id: number; name: string }
export interface SubcategoryNode extends GroupNode { groups: GroupNode[] }
export interface Category {
    id: number;
    code: string;
    slug: string;
    name: string;
    description: string | null;
    icon: string;
    color: string;
    profession_count: number;
    sample_professions: string[];
    subcategories: SubcategoryNode[];
}

export interface Skill { id: number; name: string; category: string; description: string | null }

export interface OccupationCard {
    id: number;
    code: string;
    slug: string;
    name: string;
    summary: string;
    category: string | null;
    category_id: number | null;
    subcategory: string | null;
    group: string | null;
    work_preferences: string[];
    is_demo: boolean;
    skill_preview: string[];
}

export interface OccupationRequirement {
    id: number;
    name: string;
    category?: string;
    description?: string | null;
    target_level?: number;
    requirement_type: string;
    importance?: number;
    relationship_type?: string;
    issuer?: string | null;
    credential_type?: string;
    validity_notes?: string | null;
    education_level?: string | null;
    program_type?: string | null;
}

export interface Workplace {
    id: number;
    name: string;
    description: string | null;
    industry: string;
    relevance_description: string | null;
}

export interface RoadmapStage {
    id: number;
    code?: string;
    sequence: number;
    title: string;
    objective: string;
    action: string;
    resource_title: string | null;
    resource_url: string | null;
    estimated_weeks: number | null;
    progress_status?: string;
}

export interface PublicRoadmap {
    id: number;
    name: string;
    description: string | null;
    occupation_id: number;
    stages: RoadmapStage[];
}

export interface OccupationDetail extends OccupationCard {
    description: string;
    main_activities: string[];
    responsibilities: string | null;
    work_environment: string | null;
    alternative_careers: string | null;
    verified_at: string | null;
    version: number;
    source: { name: string; publisher: string | null; url: string | null; verified_at: string | null; notes: string | null } | null;
    skills: OccupationRequirement[];
    educations: OccupationRequirement[];
    certifications: OccupationRequirement[];
    workplaces: Workplace[];
    roadmaps: PublicRoadmap[];
}

export interface UserProfile {
    id?: number;
    display_name: string;
    age_range: string | null;
    region: string | null;
    education_level: string | null;
    field_of_study: string | null;
    institution: string | null;
    bio: string | null;
    interests: number[];
    work_preferences: string[];
    experiences_reviewed: boolean;
    certifications_reviewed: boolean;
    onboarding_completed: boolean;
}

export interface UserSkill { id?: number; skill_id: number; name?: string; level: number | null; possession_status: SkillStatus; self_reported?: boolean }
export interface UserExperience { id?: number; type: string; title: string; organization: string | null; description: string | null; start_date: string | null; end_date: string | null }
export interface UserCertification { id?: number; certification_id: number | null; custom_name: string | null; issuer: string | null; valid_until: string | null }

export interface MePayload {
    id: number;
    name: string;
    email: string;
    role: Role;
    profile: UserProfile | null;
    skills: UserSkill[];
    experiences: UserExperience[];
    certifications: UserCertification[];
}

export interface SkillGap {
    skill_id: number;
    name: string;
    category: string;
    user_level: number | null;
    target_level: number;
    requirement_type: string;
    status: GapStatus;
    status_label: string;
    action: string;
}

export interface MandatoryRequirement { type: string; id: number; name: string; status: 'satisfied' | 'not_met' | 'unknown'; status_label: string }
export interface MatchingFactor { factor: string; weight: number; value: number | null; known: boolean }
export interface Readiness { status: string; label: string }

export interface Analysis {
    profession: OccupationCard & { readiness?: Readiness };
    score: number | null;
    coverage: number;
    breakdown: MatchingFactor[];
    skill_gaps: SkillGap[];
    mandatory_requirements: MandatoryRequirement[];
    reasons: string[];
    unknown_fields: string[];
    readiness: Readiness;
    algorithm_version?: string;
    disclaimer?: string;
}

export interface MatchingRun {
    id: number;
    type: 'matching' | 'goal';
    algorithm_version: string;
    created_at: string | null;
    candidate_count: number;
    input_snapshot: {
        education_level: string | null;
        field_of_study: string | null;
        interests: number[];
        work_preferences: string[];
        experiences_reviewed: boolean;
        certifications_reviewed: boolean;
        skills: Array<{ skill_id: number; level: number | null; possession_status: SkillStatus }>;
        experiences: UserExperience[];
        certifications: UserCertification[];
    };
    policy_snapshot: { version: string; weights: Record<string, number>; skill_scale: number[]; top_results: number; primary_results: number };
    results: Array<Analysis & { id: number; rank: number }>;
}

export interface SavedCareer { id: number; occupation_id: number; saved_at: string | null; profession: OccupationCard }

export interface UserRoadmap {
    id: number;
    name: string;
    status: 'active' | 'completed' | 'archived';
    occupation: { id: number; name: string; category: string };
    roadmap: { id: number; name: string; description: string | null };
    stages: RoadmapStage[];
}

export interface AdminRecord {
    id: number;
    name?: string;
    code?: string;
    email?: string;
    status?: PublicationStatus | 'active' | 'suspended';
    role?: Role;
    description?: string | null;
    source?: { name?: string } | null;
    occupation?: { name?: string } | null;
    [key: string]: unknown;
}
