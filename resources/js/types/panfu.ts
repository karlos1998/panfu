export interface NavigationItem {
    label: string;
    href: string;
    variant?: 'primary' | 'secondary';
}

export interface PanfuAssets {
    logo: string;
    heroVideo: string;
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
}

export interface FooterContent {
    copyright: string;
    links: NavigationItem[];
}

export interface MetaContent {
    title: string;
    description: string;
}

export interface FlashClient {
    title: string;
    ruffleScript: string;
    swfUrl: string;
    baseUrl: string;
    informationServerUrl: string;
    serverName: string;
    flashvars: Record<string, string>;
    flashvarsQuery: string;
}
