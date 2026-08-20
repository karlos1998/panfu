export interface NavigationItem {
    label: string;
    href: string;
    variant?: 'primary' | 'secondary';
    active?: boolean;
    children?: NavigationItem[];
    method?: 'get' | 'post';
}

export interface PanfuAssets {
    logo: string;
    heroVideo: string;
    heroVideoSafari: string;
    grasslands: string;
    homeBoard: string;
    headerIsland: string;
}

export interface HeroFeature {
    icon: 'friends' | 'games' | 'pets' | 'style';
    text: string;
}

export interface HeroContent {
    playersOnline: number;
    features: HeroFeature[];
    cta: NavigationItem;
}

export interface NewsContent {
    eyebrow: string;
    title: string;
    excerpt: string;
    button: NavigationItem;
}

export interface AboutContent {
    title: string;
    intro: string;
    points: string[];
    button: NavigationItem;
}

export interface FooterContent {
    copyright: string;
    disclaimer: string;
    links: NavigationItem[];
    legalLinks: NavigationItem[];
}

export interface AccountNavigationContent {
    label: string;
    settings: string;
    logout: string;
    greeting: string;
}

export interface SupportedLanguage {
    code: string;
    id: string;
    label: string;
    active: boolean;
    href: string;
}

export interface PanfuChrome {
    navigation: NavigationItem[];
    footer: FooterContent;
    account: AccountNavigationContent;
}

export interface PanfuLocale {
    current: string;
    languageId: string;
    languages: SupportedLanguage[];
}

export interface MetaContent {
    title: string;
    description: string;
}

export interface AccountSettingsData {
    name: string;
    email: string;
    gender: 'boy' | 'girl';
    coins: number | null;
    goldPanda: boolean;
    socialLevel: number;
    socialScore: number | null;
    createdAt: string | null;
    lastLogin: string | null;
}

export interface FlashClient {
    title: string;
    ruffleScript: string;
    swfUrl: string;
    baseUrl: string;
    informationServerUrl: string;
    serverName: string;
    socketProxyUrl: string;
    locale: string;
    languageId: string;
    flashvars: Record<string, string>;
    flashvarsQuery: string;
}

export interface ShopItem {
    id: number;
    name: string;
    price: number;
    hash: string;
    limited_time: boolean;
}

export interface ShopCategory {
    items: ShopItem[];
    subcategories: Record<string, ShopItem[]> | ShopItem[];
}

export interface ShopCatalogue {
    coins: number;
    items: Record<string, ShopCategory>;
}

export interface PandaAvatarData {
    url: string;
}

export interface TeamMemberData {
    id: number;
    name: string;
    roleLabel: string;
    online: boolean;
    avatar: PandaAvatarData;
}

export interface TeamGroupData {
    key: 'administrators' | 'moderators' | 'sheriffs';
    title: string;
    description: string;
    emptyMessage: string;
    members: TeamMemberData[];
}

export interface TeamInfoCardData {
    title: string;
    paragraphs: string[];
}
