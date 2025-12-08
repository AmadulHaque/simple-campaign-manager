import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Contact {
    id: number;
    name: string;
    email: string;
    subscribed_at: string | null;
    status: 1 | 2 | 3 | 4;
    created_at: string;
    updated_at: string;
}


export interface Campaign {
    id: number;
    name: string;
    body: string;
    subject: string;
    subject: string;
    content: string;
    sent_count: number;
    failed_count: number;
    total_recipients: number;
    status: 1 | 2 | 3 | 4 | 5;
    scheduled_for: string | null;
    sent_at: string | null;
    user_id: number;
    created_at: string;
    updated_at: string;
    contacts?: Contact[];
    stats?: CampaignStats;
}


export interface CampaignStats {
    total: number;
    pending: number;
    sent: number;
    failed: number;
    opened: number;
    clicked: number;
}

export interface CampaignContact {
    id: number;
    campaign_id: number;
    contact_id: number;
    status: 1 | 2 | 3 | 4 | 5;
    error_message: string | null;
    sent_at: string | null;
    opened_at: string | null;
    clicked_at: string | null;
    contact: Contact;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface PaginatedCursorData<T> {
    data: T[];
    next_cursor: string | null;
    prev_cursor: string | null;
    total?: number;
    path: string;
    per_page: number;
}