import { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Campaign, Contact, CampaignStats } from '@/types';
import { getCampaignStatusColor, getCampaignStatusLabel } from '@/types/enums';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

interface CampaignsShowProps {
  campaign: Campaign;
  availableContacts: Contact[];
  stats: CampaignStats;
}


const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Campaigns',
    href: '/campaigns',
  },
];


export default function CampaignsShow({ campaign, availableContacts, stats }: CampaignsShowProps) {
  const { data, setData, post, processing } = useForm({
    contacts: [] as number[],
    action: 'attach' as 'attach' | 'detach',
  });

  const [searchTerm, setSearchTerm] = useState('');
  const [selectedContacts, setSelectedContacts] = useState<number[]>([]);
  const page = usePage();
  const route = (page.props as any).route;

  const filteredContacts = availableContacts.filter(contact =>
    contact.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    contact.email.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const getStatusColor = (status: number) => {
    switch (status) {
      case 1: return 'bg-gray-100 text-gray-800'; // pending
      case 2: return 'bg-green-100 text-green-800'; // sent
      case 3: return 'bg-red-100 text-red-800'; // failed
      case 4: return 'bg-blue-100 text-blue-800'; // opened
      case 5: return 'bg-purple-100 text-purple-800'; // clicked
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusLabel = (status: number) => {
    switch (status) {
      case 1: return 'Pending';
      case 2: return 'Sent';
      case 3: return 'Failed';
      case 4: return 'Opened';
      case 5: return 'Clicked';
      default: return 'Unknown';
    }
  };


  const handleContactToggle = (contactId: number, checked: boolean) => {
    if (checked) {
      setSelectedContacts([...selectedContacts, contactId]);
    } else {
      setSelectedContacts(selectedContacts.filter(id => id !== contactId));
    }
  };

  const handleSelectAll = (checked: boolean) => {
    if (checked) {
      setSelectedContacts(filteredContacts.map(contact => contact.id));
    } else {
      setSelectedContacts([]);
    }
  };

  const handleAddContacts = () => {
    setData('contacts', selectedContacts);
    setData('action', 'attach');
    post('/campaigns/' + campaign.id + '/updateContacts');
  };

  const handleRemoveContacts = () => {
    const currentRecipientIds = campaign.recipients?.map(r => r.contact_id) || [];
    const contactsToRemove = selectedContacts.filter(id => currentRecipientIds.includes(id));

    setData('contacts', contactsToRemove);
    setData('action', 'detach');
    post('/campaigns/' + campaign.id + '/updateContacts');
  };

  const handleSendCampaign = () => {
    if (confirm('Are you sure you want to send this campaign?')) {
      post('/campaigns/' + campaign.id + '/send');
    }
  };

  const selectedCount = selectedContacts.length;
  const totalCount = filteredContacts.length;
  const allSelected = selectedCount > 0 && selectedCount === totalCount;

  return (
    <>
      <AppLayout breadcrumbs={breadcrumbs}>
        <Head title={`Campaign: ${campaign.name}`} />
        <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

          <div className="container mx-auto py-6">
            <div className="flex justify-between items-center mb-6">
              <div>
                <h1 className="text-3xl font-bold">{campaign.name}</h1>
                <p className="text-gray-600">Campaign details and performance</p>
              </div>
              <div className="flex space-x-2">
                <Link href={'/campaigns'}>
                  <Button variant="outline">Back to Campaigns</Button>
                </Link>
                {campaign.status === 'draft' && (
                  <Button onClick={handleSendCampaign} disabled={processing}>
                    Send Campaign
                  </Button>
                )}
              </div>
            </div>

            {/* Campaign Overview */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm font-medium">Status</CardTitle>
                </CardHeader>
                <CardContent>
                  <Badge className={getCampaignStatusColor(campaign.status)}>
                    {getCampaignStatusLabel(campaign.status)}
                  </Badge>
                </CardContent>
              </Card>

              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm font-medium">Total Recipients</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">{stats.total}</div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm font-medium">Success Rate</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">{stats.success_rate}%</div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm font-medium">Sent</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">{stats.sent}</div>
                </CardContent>
              </Card>
            </div>

            {/* Stats Breakdown */}
            <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm font-medium">Pending</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-xl font-bold">{stats.pending}</div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm font-medium">Sent</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-xl font-bold text-green-600">{stats.sent}</div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm font-medium">Failed</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-xl font-bold text-red-600">{stats.failed}</div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm font-medium">Opened</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-xl font-bold text-blue-600">{stats.opened}</div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader className="pb-2">
                  <CardTitle className="text-sm font-medium">Clicked</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-xl font-bold text-purple-600">{stats.clicked}</div>
                </CardContent>
              </Card>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Campaign Details */}
              <Card>
                <CardHeader>
                  <CardTitle>Campaign Details</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-3">
                    <div>
                      <label className="text-sm font-medium text-gray-500">Subject</label>
                      <p className="mt-1">{campaign.subject}</p>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-gray-500">Body</label>
                      <div className="mt-1 p-3  text-black-50 rounded-md border border-gray-300">
                        <pre className="whitespace-pre-wrap  text-sm">{campaign.body}</pre>
                      </div>
                    </div>
                    <div>
                      <label className="text-sm font-medium text-gray-500">Created</label>
                      <p className="mt-1">{new Date(campaign.created_at).toLocaleDateString()}</p>
                    </div>
                  </div>
                </CardContent>
              </Card>

              {/* Contact Management */}
              <Card>
                <CardHeader>
                  <CardTitle>Manage Recipients</CardTitle>
                  <CardDescription>
                    Add or remove contacts from this campaign
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    <div className="flex justify-between items-center">
                      <Input
                        type="text"
                        placeholder="Search contacts..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="w-64"
                      />
                      <div className="flex items-center space-x-2">
                        <Checkbox
                          id="select-all"
                          checked={allSelected}
                          onCheckedChange={handleSelectAll}
                        />
                        <Label htmlFor="select-all">Select All</Label>
                        {selectedCount > 0 && (
                          <Badge variant="secondary">
                            {selectedCount} selected
                          </Badge>
                        )}
                      </div>
                    </div>

                    <div className="border rounded-lg max-h-64 overflow-y-auto">
                      {filteredContacts.length === 0 ? (
                        <div className="p-4 text-center text-gray-500">
                          No contacts found
                        </div>
                      ) : (
                        filteredContacts.map((contact) => (
                          <div
                            key={contact.id}
                            className="flex items-center space-x-3 p-3 hover:bg-gray-50 border-b last:border-b-0"
                          >
                            <Checkbox
                              id={`contact-${contact.id}`}
                              checked={selectedContacts.includes(contact.id)}
                              onCheckedChange={(checked) =>
                                handleContactToggle(contact.id, checked as boolean)
                              }
                            />
                            <div className="flex-1">
                              <Label
                                htmlFor={`contact-${contact.id}`}
                                className="text-sm font-medium cursor-pointer"
                              >
                                {contact.name}
                              </Label>
                              <p className="text-xs text-gray-500">{contact.email}</p>
                            </div>
                            <Badge variant="outline" className="text-xs">
                              {contact.status === 1 ? 'Active' : 'Inactive'}
                            </Badge>
                          </div>
                        ))
                      )}
                    </div>

                    <div className="flex justify-end space-x-2">
                      <Button
                        type="button"
                        variant="outline"
                        onClick={handleRemoveContacts}
                        disabled={selectedCount === 0 || processing}
                      >
                        Remove Selected
                      </Button>
                      <Button
                        type="button"
                        onClick={handleAddContacts}
                        disabled={selectedCount === 0 || processing}
                      >
                        Add Selected
                      </Button>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>

            {/* Recipient Status Table */}
            <Card className="mt-6">
              <CardHeader>
                <CardTitle>Recipient Status</CardTitle>
                <CardDescription>
                  View the delivery status for each recipient
                </CardDescription>
              </CardHeader>
              <CardContent>
                {campaign.recipients && campaign.recipients.length > 0 ? (
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Contact</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Sent At</TableHead>
                        <TableHead>Error Message</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {campaign.recipients.map((recipient) => (
                        <TableRow key={recipient.id}>
                          <TableCell className="font-medium">
                            {recipient.contact?.name || 'Unknown'}
                          </TableCell>
                          <TableCell>{recipient.contact?.email || 'Unknown'}</TableCell>
                          <TableCell>
                            <Badge className={getStatusColor(recipient.status)}>
                              {getStatusLabel(recipient.status)}
                            </Badge>
                          </TableCell>
                          <TableCell>
                            {recipient.sent_at
                              ? new Date(recipient.sent_at).toLocaleString()
                              : '-'}
                          </TableCell>
                          <TableCell className="text-red-600">
                            {recipient.error_message || '-'}
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                ) : (
                  <div className="text-center py-8">
                    <p className="text-gray-500">No recipients found for this campaign.</p>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </div>
      </AppLayout >

    </>
  );
}
