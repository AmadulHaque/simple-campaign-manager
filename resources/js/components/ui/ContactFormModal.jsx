import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { FormEvent, useEffect } from 'react';
import { Label } from './label';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

export default function ContactFormModal({ open, onOpenChange, contact, loadDefault }) {
    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: contact?.name || '',
        email: contact?.email || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();

        if (contact) {
            // UPDATE
            router.put(
                'contacts/' + contact.id,  // ← Wayfinder: returns string
                data,
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        onOpenChange(false);
                        reset();
                        loadDefault();
                        toast.success('Contact updated');
                    },
                    onError: (errors) => {
                        console.log(errors);
                        toast.error('Something went wrong');
                    }
                }
            );
        } else {
            // CREATE
            router.post(
                'contacts',
                data,
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        onOpenChange(false);
                        reset();
                        loadDefault();
                        toast.success('Contact created');
                    },
                    onError: (errors) => {
                        console.log(errors);
                        toast.error('Something went wrong');
                    }
                }
            );
        }
    };

    useEffect(() => {
        if (contact) {
            setData('name', contact.name);
            setData('email', contact.email);
        }
    }, [contact]);

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