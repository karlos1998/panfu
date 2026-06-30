import type { PanfuChrome, PanfuLocale } from './panfu';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    coins?: number | null;
    goldpanda?: number;
    last_login?: string | null;
    sex?: boolean;
    social_level?: number;
    social_score?: number | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
    };
    panfu: {
        locale: PanfuLocale;
        chrome: PanfuChrome;
    };
};
