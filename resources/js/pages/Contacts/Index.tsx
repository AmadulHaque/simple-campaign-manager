import { useState, useEffect, useRef } from 'react';
import { Head, router } from '@inertiajs/react';
import { Plus, Trash2, Upload, Search, Edit } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { toast } from 'sonner';
import AppLayout from '@/layouts/app-layout';
import ContactFormModal from '@/components/ui/ContactFormModal';
import type { Contact, PaginatedCursorData } from '@/types';

interface Props {
  contacts: PaginatedCursorData<Contact>;
  filters: {
    search?: string;
  };
}

export default function Index({ contacts: initialData, filters }: Props) {
  const [data, setData] = useState(initialData);
  const [isLoading, setIsLoading] = useState(false);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [search, setSearch] = useState(filters.search || '');
  const [showCreateEdit, setShowCreateEdit] = useState(false);
  const [editingContact, setEditingContact] = useState<Contact | null>(null);
  const [showImport, setShowImport] = useState(false);
  const [importData, setImportData] = useState<any[]>([]);
  const observer = useRef<IntersectionObserver | null>(null);

  // Load more when last item is visible
  const lastItemRef = useRef<HTMLTableRowElement>(null);

  useEffect(() => {
    if (isLoading || !data.next_cursor) return;

    observer.current = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && data.next_cursor) {
        loadMore();
      }
    });

    if (lastItemRef.current) {
      observer.current.observe(lastItemRef.current);
    }

    return () => observer.current?.disconnect();
  }, [data.next_cursor, isLoading]);

  const loadMore = () => {
    if (!data.next_cursor || isLoading) return;
    setIsLoading(true);

    router.get(
      'contacts',
      {
        search: filters.search,
        cursor: data.next_cursor,
      },
      {
        preserveState: true,
        preserveScroll: true,
        only: ['contacts'],
        onSuccess: (page: any) => {
          setData((prev) => ({
            ...page.props.contacts,
            data: [...prev.data, ...page.props.contacts.data],
          }));
          setIsLoading(false);
        },
        onError: () => setIsLoading(false),
      }
    );
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    router.get('contacts', { search }, { preserveState: true, replace: true });
  };

  const toggleSelect = (id: number) => {
    setSelectedIds((prev) =>
      prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]
    );
  };

  const toggleSelectAll = () => {
    if (selectedIds.length === data.data.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(data.data.map((c) => c.id));
    }
  };

  const handleBulkDelete = () => {
    if (!selectedIds.length || !confirm(`Delete ${selectedIds.length} contacts?`)) return;

    router.post('/contacts/bulk-delete', { ids: selectedIds }, {
      onSuccess: () => {
        toast('Deleted successfully');
        setSelectedIds([]);
        router.reload({ only: ['contacts'] });
      },
    });
  };

  const handleDelete = (id: number) => {
    if (!confirm('Delete this contact?')) return;
    router.delete('contacts/' + id, {
      onSuccess: () => toast('Contact deleted'),
    });
  };

  const openCreate = () => {
    setEditingContact(null);
    setShowCreateEdit(true);
  };

  const openEdit = (contact: Contact) => {
    setEditingContact(contact);
    setShowCreateEdit(true);
  };

  const handleFileImport = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (ev) => {
      try {
        const json = JSON.parse(ev.target?.result as string);
        if (Array.isArray(json)) {
          setImportData(json);
          setShowImport(true);
        }
      } catch {
        toast('Invalid JSON file');
      }
    };
    reader.readAsText(file);
  };

  const confirmImport = () => {
    router.post('/contacts/import', { contacts: importData }, {
      onSuccess: () => {
        toast(`Imported ${importData.length} contacts`);
        setShowImport(false);
        setImportData([]);
        router.reload({ only: ['contacts'] });
      },
    });
  };

  return (
    <AppLayout
      breadcrumbs={[
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Contacts', href: '/contacts' },
      ]}
    >
      <Head title="Contacts" />

      <div className="flex h-full flex-col gap-6 overflow-hidden rounded-xl  p-6 shadow-sm">
        <div className="flex items-center justify-between">
          <h1 className="text-3xl font-bold">Contacts</h1>
          <div className="flex gap-3">
            <Label htmlFor="import-file" className="cursor-pointer">
              <input id="import-file" type="file" accept=".json" className="hidden" onChange={handleFileImport} />
              <Button variant="outline" size="sm">
                <Upload className="mr-2 h-4 w-4" /> Import JSON
              </Button>
            </Label>
            <Button onClick={openCreate}>
              <Plus className="mr-2 h-4 w-4" /> Add Contact
            </Button>
          </div>
        </div>

        <form onSubmit={handleSearch} className="flex gap-4">
          <div className="relative flex-1 max-w-md">
            <Search className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Search by name or email..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="pl-10"
            />
          </div>
          {selectedIds.length > 0 && (
            <Button variant="destructive" onClick={handleBulkDelete}>
              <Trash2 className="mr-2 h-4 w-4" />
              Delete ({selectedIds.length})
            </Button>
          )}
        </form>

        <div className="rounded-md border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-12">
                  <Checkbox
                    checked={selectedIds.length === data.data.length && data.data.length > 0}
                    onCheckedChange={toggleSelectAll}
                  />
                </TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead className="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.data.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={5} className="text-center py-10 text-muted-foreground">
                    No contacts found.
                  </TableCell>
                </TableRow>
              ) : (
                data.data.map((contact, index) => (
                  <TableRow
                    key={contact.id}
                    ref={index === data.data.length - 1 ? lastItemRef : null}
                  >
                    <TableCell>
                      <Checkbox
                        checked={selectedIds.includes(contact.id)}
                        onCheckedChange={() => toggleSelect(contact.id)}
                      />
                    </TableCell>
                    <TableCell className="font-medium">{contact.name}</TableCell>
                    <TableCell>{contact.email}</TableCell>
                    <TableCell>{contact.phone || '-'}</TableCell>
                    <TableCell className="text-right space-x-2">
                      <Button size="sm" variant="ghost" onClick={() => openEdit(contact)}>
                        <Edit className="h-4 w-4" />
                      </Button>
                      <Button size="sm" variant="ghost" onClick={() => handleDelete(contact.id)}>
                        <Trash2 className="h-4 w-4 text-red-600" />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </div>

        {isLoading && (
          <div className="py-4 text-center text-sm text-muted-foreground">
            Loading more...
          </div>
        )}

        {!data.next_cursor && data.data.length > 0 && (
          <div className="py-4 text-center text-sm text-muted-foreground">
            No more contacts
          </div>
        )}
      </div>

      <ContactFormModal
        open={showCreateEdit}
        onOpenChange={setShowCreateEdit}
        contact={editingContact}
      />

      <Dialog open={showImport} onOpenChange={setShowImport}>
        <DialogContent className="max-w-3xl">
          <DialogHeader>
            <DialogTitle>Import Preview ({importData.length} contacts)</DialogTitle>
          </DialogHeader>
          <div className="text-sm text-muted-foreground mb-4">
            Only valid contacts will be imported.
          </div>
          <div className="max-h-96 overflow-auto rounded-md border">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Phone</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {importData.slice(0, 10).map((c, i) => (
                  <TableRow key={i}>
                    <TableCell>{c.name || <span className="text-red-500">Missing</span>}</TableCell>
                    <TableCell>{c.email || <span className="text-red-500">Missing</span>}</TableCell>
                    <TableCell>{c.phone || '-'}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
          <div className="flex justify-end gap-3 mt-6">
            <Button variant="outline" onClick={() => setShowImport(false)}>Cancel</Button>
            <Button onClick={confirmImport}>Import All</Button>
          </div>
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}