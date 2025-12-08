import { Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Campaign, PaginatedResponse } from '@/types';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import { Head } from '@inertiajs/react';
import { getCampaignStatusColor, getCampaignStatusLabel } from '@/types/enums';

interface CampaignsIndexProps {
  campaigns: PaginatedResponse<Campaign>;
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: dashboard().url,
  },
  {
    title: 'Campaigns',
    href: '/campaigns',
  },
];
export default function CampaignsIndex({ campaigns }: CampaignsIndexProps) {

  const page = usePage();
  const route = (page.props as any).route;

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Campaigns" />
      <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">


        <div className="container mx-auto py-6">
          <div className="flex justify-between items-center mb-6">
            <div>
              <h1 className="text-3xl font-bold">Campaigns</h1>
              <p className="text-gray-600">Manage your email campaigns</p>
            </div>
            <Link href='/campaigns/create'>
              <Button>Create Campaign</Button>
            </Link>
          </div>

          <div className="grid gap-6">
            {campaigns.data.map((campaign) => (
              <Card key={campaign.id} className="hover:shadow-md transition-shadow">
                <CardHeader>
                  <div className="flex justify-between items-start">
                    <div>
                      <CardTitle className="text-xl">{campaign.name}</CardTitle>
                      <CardDescription className="mt-1">
                        Subject: {campaign.subject}
                      </CardDescription>
                    </div>
                    <Badge className={getCampaignStatusColor(campaign.status)}>
                      {getCampaignStatusLabel(campaign.status)}
                    </Badge>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="flex justify-between items-center">
                    <div className="text-sm text-gray-600">
                      <span>Total Recipients: {campaign.total_recipients}</span>
                      {campaign.sent_count > 0 && (
                        <span className="ml-4">Sent: {campaign.sent_count}</span>
                      )}
                      {campaign.failed_count > 0 && (
                        <span className="ml-4 text-red-600">Failed: {campaign.failed_count}</span>
                      )}
                    </div>
                    <div className="flex gap-2">
                      <Link href={'/campaigns/' + campaign.id + '/show'}>
                        <Button variant="outline" size="sm">
                          View Details
                        </Button>
                      </Link>
                      {campaign.status === 'draft' && (
                        <Link href={'/campaigns/' + campaign.id + '/send'} method="post" as="button">
                          <Button size="sm">Send Campaign</Button>
                        </Link>
                      )}
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>

          {campaigns.data.length === 0 && (
            <div className="text-center py-12">
              <h3 className="text-lg font-medium text-gray-900">No campaigns yet</h3>
              <p className="mt-1 text-gray-500">Get started by creating your first campaign.</p>
              <div className="mt-6">
                <Link href={'/campaigns/create'}>
                  <Button>Create Campaign</Button>
                </Link>
              </div>
            </div>
          )}
        </div>
      </div>
    </AppLayout>
  );
}
