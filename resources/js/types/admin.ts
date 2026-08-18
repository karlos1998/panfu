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

export interface RoomClient {
    ruffleScript: string;
    stageWidth: number;
    stageHeight: number;
}

export interface PlayerHomeSummary {
    userId: number;
    name: string;
    email: string;
    backgroundId: number;
    backgroundName: string;
    furnitureCount: number;
    placedFurnitureCount: number;
}

export interface HomeBackground {
    itemId: number;
    name: string;
    swfUrl: string | null;
    active?: boolean;
}

export interface HomeFurniture {
    inventoryId: number;
    itemId: number;
    name: string;
    type: number | null;
    premium: boolean;
    placed: boolean;
    x: number;
    y: number;
    rotation: number;
    room: number;
    iconUrl: string | null;
    modelUrl: string | null;
}

export interface PlayerHomeDetails {
    user: Pick<ManagedUser, 'id' | 'name' | 'email'>;
    activeBackground: HomeBackground;
    backgrounds: HomeBackground[];
    furniture: HomeFurniture[];
    roomNumbers: number[];
    furnitureCount: number;
    placedFurnitureCount: number;
}

export interface RoomSpawn {
    from: string;
    x: number;
    y: number;
    radiusX: number;
    radiusY: number;
    rotation: number | null;
}

export interface PublicRoomSummary {
    id: string;
    number: number;
    key: string;
    label: string;
    allowed: boolean;
    restrictToWalkArea: boolean;
    vehicleArea: boolean;
    jumping: boolean;
    volume: number;
    assetExists: boolean;
    configExists: boolean;
    assetSize: number | null;
    roomSwfUrl: string | null;
    spawns: RoomSpawn[];
}

export interface RoomAsset {
    id: string;
    path: string;
    preload: boolean;
    exists: boolean;
    url: string | null;
}

export interface RoomSound {
    id: string;
    path: string;
    volume: number | null;
    loops: number | null;
    exists: boolean;
}

export interface RoomDate {
    id: string;
    start: string;
    finish: string;
}

export interface RoomElement {
    id: string;
    type: string | null;
    button: boolean;
    visible: boolean;
    messages: string[];
}

export interface RoomHotspot {
    id: string;
    target: string;
    type: string;
    x: number;
    y: number;
    radius: number;
    angle: number | null;
    destination: {
        id: string;
        number: number;
        label: string;
    } | null;
}

export interface RoomTransform {
    a: number;
    b: number;
    c: number;
    d: number;
    tx: number;
    ty: number;
}

export interface RoomDebugFrame {
    url: string;
    x: number;
    y: number;
    width: number;
    height: number;
    transform: RoomTransform;
}

export interface RoomDebugData {
    walkAreaCharacterId: number | null;
    walkAreaFrames: RoomDebugFrame[];
}

export interface PublicRoomDetails extends PublicRoomSummary {
    assets: RoomAsset[];
    sounds: RoomSound[];
    dates: RoomDate[];
    elements: RoomElement[];
    hotspots: RoomHotspot[];
    debug: RoomDebugData;
}
