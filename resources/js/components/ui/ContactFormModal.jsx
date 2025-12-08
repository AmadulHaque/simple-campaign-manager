import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Label } from './label';



export default function ContactFormModal({ open, onOpenChange, contact }) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: contact?.name || '',
        email: contact?.email || '',
        phone: contact?.phone || '',
        address: contact?.address || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();

        const routeName = contact ? 'contacts.update' : 'contacts.store';
        const method = contact ? put : post;

        method(contact ? route('contacts.update', contact.id) : route('contacts.store'), {
            data,
            onSuccess: () => {
                onOpenChange(false);
                reset();
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={(o) => { onOpenChange(o); if (!o) reset(); }}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{contact ? 'Edit' : 'Create'} Contact</DialogTitle>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Label>Name</Label>
                        <Input
                            value={data.name}
                            onChange={e => setData('name', e.target.value)}
                            required
                        />
                        {errors.name && <p className="text-sm text-red-600 mt-1">{errors.name}</p>}
                    </div>

                    <div>
                        <Label>Email</Label>
                        <Input
                            type="email"
                            value={data.email}
                            onChange={e => setData('email', e.target.value)}
                            required
                        />
                        {errors.email && <p className="text-sm text-red-600 mt-1">{errors.email}</p>}
                    </div>

                    <div>
                        <Label>Phone (optional)</Label>
                        <Input
                            value={data.phone}
                            onChange={e => setData('phone', e.target.value)}
                        />
                    </div>

                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {contact ? 'Update' : 'Create'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}