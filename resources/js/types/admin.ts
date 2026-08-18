export type UserRole = 'user' | 'admin';

export interface SelectOption<T extends string | number = string> {
    value: T;
    label: string;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
}

export interface AdminUserSummary {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    roleLabel: string;
    coins: number;
    goldPanda: boolean;
    sheriff: boolean;
    online: boolean;
    socialLevel: number;
    inventoryCount: number;
    statesCount: number;
    relationsCount: number;
    lastLogin: string | null;
    createdAt: string | null;
}

export interface ManagedUser {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    sex: boolean;
    coins: number;
    goldpanda: boolean;
    sheriff: boolean;
    socialLevel: number;
    socialScore: number;
    currentGameServerName: string | null;
    tourFinished: boolean;
    birthday: string | null;
    lastLogin: string | null;
    emailVerified: boolean;
    createdAt: string | null;
    updatedAt: string | null;
}

export interface InventoryEntry {
    id: number;
    itemId: number;
    name: string;
    type: number | null;
    premium: boolean;
    active: boolean;
    bought: boolean;
    x: number;
    y: number;
    rotation: number;
    room: number;
}

export interface PlayerState {
    id: number;
    category: number;
    name: number;
    value: number;
    lastChanged: number | null;
}

export interface UserRelation {
    id: number;
    userId: number;
    name: string;
    email: string | null;
    type: number;
    typeLabel: string;
}

export interface UserSession {
    id: string;
    ipAddress: string | null;
    userAgent: string | null;
    lastActivity: string;
    active: boolean;
    current: boolean;
}

export interface ItemOption {
    id: number;
    name: string | null;
    type: number | null;
    premium: boolean;
}

export interface UserOption {
    id: number;
    name: string;
    email: string;
}
