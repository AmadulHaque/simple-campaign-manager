import { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Contact } from '@/types';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/app-layout';

interface CampaignsCreateProps {
  contacts: Contact[];
}
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Campaigns',
    href: '/campaigns',
  },
  {
    title: 'Create',
    href: '/campaigns/create',
  },
];
export default function CampaignsCreate({ contacts }: CampaignsCreateProps) {
  const { data, setData, post, processing, errors } = useForm({
    name: '',
    subject: '',
    body: '',
    contact_ids: [],
    scheduled_at: '',
  });

  const [searchTerm, setSearchTerm] = useState('');
  const page = usePage();
  const route = (page.props as any).route;

  const filteredContacts = contacts.filter(contact =>
    contact.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    contact.email.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const handleContactToggle = (contactId: number, checked: boolean) => {
    if (checked) {
      setData('contact_ids', [...data.contact_ids, contactId]);
    } else {
      setData('contact_ids', data.contact_ids.filter(id => id !== contactId));
    }
  };

  const handleSelectAll = (checked: boolean) => {
    if (checked) {
      setData('contact_ids', filteredContacts.map(contact => contact.id));
    } else {
      setData('contact_ids', []);
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post('/campaigns');
  };

  const selectedCount = data.contact_ids.length;
  const totalCount = filteredContacts.length;
  const allSelected = selectedCount > 0 && selectedCount === totalCount;

  return (
    <>
      <AppLayout breadcrumbs={breadcrumbs}>
        <Head title="Create Campaign" />
        <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
          <div className="container mx-auto py-6">
            <div className="mb-6">
              <h1 className="text-3xl font-bold">Create Campaign</h1>
              <p className="text-gray-600">Create a new email campaign</p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Campaign Details */}
                <Card>
                  <CardHeader>
                    <CardTitle>Campaign Details</CardTitle>
                    <CardDescription>
                      Set the basic information for your campaign
                    </CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div>
                      <Label htmlFor="name">Campaign Name</Label>
                      <Input
                        id="name"
                        type="text"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        className="mt-1"
                        placeholder="My Awesome Campaign"
                      />
                      {errors.name && (
                        <Alert className="mt-2" variant="destructive">
                          <AlertDescription>{errors.name}</AlertDescription>
                        </Alert>
                      )}
                    </div>

                    <div>
                      <Label htmlFor="subject">Email Subject</Label>
                      <Input
                        id="subject"
                        type="text"
                        value={data.subject}
                        onChange={(e) => setData('subject', e.target.value)}
                        className="mt-1"
                        placeholder="Your email subject line"
                      />
                      {errors.subject && (
                        <Alert className="mt-2" variant="destructive">
                          <AlertDescription>{errors.subject}</AlertDescription>
                        </Alert>
                      )}
                    </div>

                    <div>
                      <Label htmlFor="body">Email Body</Label>
                      <Textarea
                        id="body"
                        value={data.body}
                        onChange={(e) => setData('body', e.target.value)}
                        className="mt-1"
                        rows={10}
                        placeholder="Write your email content here..."
                      />
                      {errors.body && (
                        <Alert className="mt-2" variant="destructive">
                          <AlertDescription>{errors.body}</AlertDescription>
                        </Alert>
                      )}
                    </div>

                    <div>
                      <Label htmlFor="scheduled_at">Schedule For (Optional)</Label>
                      <Input
                        id="scheduled_at"
                        type="datetime-local"
                        value={data.scheduled_at}
                        onChange={(e) => setData('scheduled_at', e.target.value)}
                        className="mt-1"
                      />
                      {errors.scheduled_at && (
                        <Alert className="mt-2" variant="destructive">
                          <AlertDescription>{errors.scheduled_at}</AlertDescription>
                        </Alert>
                      )}
                    </div>
                  </CardContent>
                </Card>

                {/* Contact Selection */}
                <Card>
                  <CardHeader>
                    <CardTitle>Recipients</CardTitle>
                    <CardDescription>
                      Select contacts to send this campaign to
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-4">
                      <div className="flex justify-between items-center">
                        <div>
                          <Input
                            type="text"
                            placeholder="Search contacts..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="w-64"
                          />
                        </div>
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

                      {errors.contact_ids && (
                        <Alert variant="destructive">
                          <AlertDescription>{errors.contact_ids}</AlertDescription>
                        </Alert>
                      )}

                      <div className="border rounded-lg max-h-96 overflow-y-auto">
                        {filteredContacts.length === 0 ? (
                          <div className="p-4 text-center text-gray-500">
                            No contacts found
                          </div>
                        ) : (
                          filteredContacts.map((contact) => (
                            <div
                              key={contact.id}
                              className="flex items-center space-x-3 p-3 hover:bg-gray-50 hover:cursor-pointer hover:text-black border-b last:border-b-0"
                            >
                              <Checkbox
                                id={`contact-${contact.id}`}
                                checked={data.contact_ids.includes(contact.id)}
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
                              <Badge variant="outline" className="text-xs hover:text-black">
                                {contact.status === 1 ? 'Active' : 'Inactive'}
                              </Badge>
                            </div>
                          ))
                        )}
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </div>

              {/* Actions */}
              <div className="flex justify-end space-x-4">
                <Link href='/campaigns'>
                  <Button type="button" variant="outline">
                    Cancel
                  </Button>
                </Link>
                <Button
                  type="submit"
                  disabled={processing || selectedCount === 0}
                >
                  {processing ? 'Creating...' : 'Create Campaign'}
                </Button>
              </div>
            </form>
          </div>
        </div>
      </AppLayout>
    </>
  );
}
