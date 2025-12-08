<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Pagination\CursorPaginator;

class ContactService
{
    public function getPaginatedContacts(): CursorPaginator
    {
        $perPage = 20;
        $search  = request('search', null);
        $status  = request('status', null);

        return Contact::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('id', 'desc')->cursorPaginate($perPage);
    }

    public function createContact(array $data): Contact
    {
        return Contact::create($data);
    }

    public function updateContact(Contact $contact, array $data): bool
    {
        return $contact->update($data);
    }

    public function deleteContact(Contact $contact): bool
    {
        return $contact->delete();
    }

    public function importContacts(array $contacts): int
    {
        $validContacts = array_filter($contacts, function ($contact) {
            return ! empty($contact['name']) && filter_var($contact['email'], FILTER_VALIDATE_EMAIL);
        });

        $data = array_map(function ($contact) {
            return [
                'name'       => trim($contact['name']),
                'email'      => strtolower(trim($contact['email'])),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $validContacts);

        return Contact::insert($data);
    }

    public function bulkDeleteContacts(array $ids): int
    {
        return Contact::whereIn('id', $ids)->delete();
    }
}
