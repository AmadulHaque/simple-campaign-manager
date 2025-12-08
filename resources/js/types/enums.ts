export enum ContactStatus {
    ACTIVE = 1,
    UNSUBSCRIBED = 2,
    BOUNCED = 3,
    UNKNOWN = 4,
}

export const ContactStatusLabel: Record<ContactStatus, string> = {
    [ContactStatus.ACTIVE]: 'Active',
    [ContactStatus.UNSUBSCRIBED]: 'Unsubscribed',
    [ContactStatus.BOUNCED]: 'Bounced',
    [ContactStatus.UNKNOWN]: 'Unknown',
};

export const getContactStatusLabel = (status: number | null): string => {
    return ContactStatusLabel[status as ContactStatus] ?? 'Unknown';
};

export const getContactStatusColor = (status: number | null): string => {
    switch (status) {
        case ContactStatus.ACTIVE: return 'bg-green-100 text-green-800';
        case ContactStatus.BOUNCED: return 'bg-red-100 text-red-800';
        case ContactStatus.UNSUBSCRIBED: return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};


export enum CampaignStatus {
    DRAFT = 'draft',
    SCHEDULED = 'scheduled',
    SENDING = 'sending',
    SENT = 'sent',
    CANCELLED = 'cancelled',
}

export const CampaignStatusLabel: Record<CampaignStatus, string> = {
    [CampaignStatus.DRAFT]: 'Draft',
    [CampaignStatus.SCHEDULED]: 'Scheduled',
    [CampaignStatus.SENDING]: 'Sending',
    [CampaignStatus.SENT]: 'Sent',
    [CampaignStatus.CANCELLED]: 'Cancelled',
};


export const getCampaignStatusLabel = (status: string | null): string => {
    return CampaignStatusLabel[status as CampaignStatus] ?? 'Unknown';
};

export const getCampaignStatusColor = (status: string | null): string => {
    switch (status) {
        case CampaignStatus.DRAFT: return 'bg-gray-100 text-gray-800';
        case CampaignStatus.SCHEDULED: return 'bg-gray-100 text-gray-800';
        case CampaignStatus.SENDING: return 'bg-yellow-100 text-yellow-800';
        case CampaignStatus.SENT: return 'bg-green-100 text-green-800';
        case CampaignStatus.CANCELLED: return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};